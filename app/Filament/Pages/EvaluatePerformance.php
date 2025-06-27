<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EmployeeScore;
use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\PerformanceResult;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection; // Pastikan ini di-import jika menggunakan Collection
use Illuminate\Support\Facades\Log; // Pastikan ini di-import jika menggunakan Log

class EvaluatePerformance extends Page implements HasForms
{
    use InteractsWithForms;

    public array $evaluatedResults = [];
    public array $results = [];

    public $history = [];
    public $evaluation_id;
    public $lambda = 0.5; // Properti publik untuk lambda, default 0.5

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static string $view = 'filament.pages.evaluate-performance';
    protected static ?string $navigationLabel = 'Evaluasi Kinerja';
    protected static ?string $title = 'Evaluasi Kinerja Karyawan';
    protected static ?string $navigationGroup = 'Sistem Pengambilan Keputusan';
    protected static ?int $navigationSort = 4;

    protected static function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }

    public static function canAccess(): bool
    {
        return static::isCurrentUserAdmin();
    }

    public function mount(): void
    {
        // Kosong, user harus pilih evaluasi dulu
        // Data history akan dimuat ketika evaluation_id dipilih via updatedEvaluationId
    }

    public function evaluate()
    {
        Log::info('DEBUG SPK: Evaluasi dimulai.');
        Log::info('DEBUG SPK: Evaluation ID selected: ' . ($this->evaluation_id ?? 'NULL'));
        Log::info('DEBUG SPK: Lambda parameter: ' . $this->lambda);

        $criteria = EvaluationCriteria::all();
        $employees = Employee::all();

        // Ambil lambda dari properti publik
        $lambda = $this->lambda ?? 0.5; // Pastikan lambda antara 0-1 (validasi di getFormSchema)

        // Validasi: pastikan ada kriteria dan karyawan
        if ($criteria->isEmpty() || $employees->isEmpty()) {
            Notification::make()
                ->title('Data tidak lengkap')
                ->body('Pastikan sudah ada data kriteria dan karyawan.')
                ->warning()
                ->send();
            return;
        }

        $scoresMatrix = [];

        // Ambil skor tiap karyawan per kriteria
        foreach ($employees as $employee) {
            $scoresMatrix[$employee->id] = [
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'scores' => []
            ];

            foreach ($criteria as $criterion) {
                $score = EmployeeScore::where('employee_id', $employee->id)
                    ->where('evaluation_id', $this->evaluation_id)
                    ->where('evaluation_criteria_id', $criterion->id)
                    ->value('score');

                $scoresMatrix[$employee->id]['scores'][$criterion->id] = $score ?? 0;
            }
            Log::info('DEBUG SPK: Scores collected for Employee ' . $employee->name . ' (ID: ' . $employee->id . '): ' . json_encode($scoresMatrix[$employee->id]['scores']));
        }

        // Validasi: pastikan ada skor untuk evaluasi yang dipilih
        $hasScores = false;
        foreach ($scoresMatrix as $empData) {
            if (array_sum($empData['scores']) > 0) {
                $hasScores = true;
                break;
            }
        }

        if (!$hasScores) {
            Notification::make()
                ->title('Tidak ada data skor')
                ->body('Tidak ada skor karyawan untuk periode evaluasi yang dipilih.')
                ->warning()
                ->send();
            return;
        }

        // ===== NORMALISASI (Metode yang sama untuk SAW/WPM) =====
        $normalized = [];

        // Inisialisasi struktur normalized
        foreach ($scoresMatrix as $empId => $data) {
            $normalized[$empId] = [
                'employee_id' => $empId,
                'name' => $data['name'],
                'normalized_scores' => [],
                'saw_sum' => 0,     // Total WSM untuk karyawan ini
                'wp_product' => 1.0,  // Total WPM untuk karyawan ini
                'waspas_total' => 0 // Final WASPAS score
            ];
        }

        // Normalisasi per kriteria
        foreach ($criteria as $criterion) {
            Log::info('DEBUG SPK: Kriteria ' . $criterion->name . ' (ID: ' . $criterion->id . ') - Bobot: ' . $criterion->weight . ', Tipe: ' . $criterion->type);
            $values = [];
            foreach ($scoresMatrix as $empId => $data) {
                $values[] = (float) $data['scores'][$criterion->id]; // Pastikan nilai adalah float
            }

            $maxValue = max($values);
            $minValue = min($values);

            foreach ($scoresMatrix as $empId => $data) {
                $originalValue = (float) $data['scores'][$criterion->id]; // Pastikan nilai adalah float
                $normalizedValue = 0.0; // Gunakan float

                if ($criterion->type === 'benefit') {
                    // Untuk kriteria benefit (semakin tinggi semakin baik)
                    // Formula: r_ij = x_ij / max(x_ij)
                    $normalizedValue = $maxValue > 0 ? $originalValue / $maxValue : 0.0;
                } else { // Cost (semakin rendah semakin baik)
                    // Formula: r_ij = min(x_ij) / x_ij
                    if ($originalValue > 0) {
                        $normalizedValue = $minValue / $originalValue;
                    } else {
                        $normalizedValue = 1.0; // Jika nilai cost 0, dianggap terbaik (normalisasi 1)
                    }
                }

                $normalized[$empId]['normalized_scores'][$criterion->id] = $normalizedValue;
            }
        }

        // ===== PERHITUNGAN WASPAS =====
        foreach ($normalized as $empId => &$data) { // Pakai '&' untuk modifikasi langsung
            $sawSum = 0.0;      // Komponen SAW (penjumlahan)
            $wpProduct = 1.0; // Komponen WP (perkalian), pakai float

            foreach ($criteria as $criterion) {
                $normalizedValue = $data['normalized_scores'][$criterion->id];
                $weight = (float) $criterion->weight; // HAPUS PEMBAGIAN 100

                Log::info('DEBUG SPK: Employee ' . $data['name'] . ' - Kriteria ' . $criterion->name . ' (ID: ' . $criterion->id . ') - Norm: ' . $normalizedValue . ', Weight: ' . $weight);

                // Bagian WSM (SAW): w_j × r_ij
                $sawComponent = $weight * $normalizedValue;
                $sawSum += $sawComponent;

                // Bagian WPM: r_ij^w_j
                if ($normalizedValue > 0) {
                    $wpComponent = pow($normalizedValue, $weight);
                    $wpProduct *= $wpComponent;
                } else {
                    $wpProduct = 0.0; // Jika ada nilai normalisasi 0, hasil perkalian jadi 0
                    break; // Hentikan loop WP untuk kriteria ini
                }
            }

            $data['saw_sum'] = $sawSum;
            $data['wp_product'] = $wpProduct;

            // WASPAS Final Score: λ × SAW + (1-λ) × WP
            $data['waspas_total'] = ($lambda * $sawSum) + ((1 - $lambda) * $wpProduct);
            Log::info('DEBUG SPK: Employee ' . $data['name'] . ' - SAW Sum: ' . $sawSum . ', WP Product: ' . $wpProduct . ', WASPAS Total: ' . $data['waspas_total']);
        }

        // Urutkan berdasarkan WASPAS total skor (descending)
        $sorted = collect($normalized)
            ->sortByDesc('waspas_total')
            ->values()
            ->toArray();

        // Buat hasil akhir dengan ranking
        $this->results = [];
        $this->evaluatedResults = [];

        foreach ($sorted as $index => $item) {
            $finalScore = round($item['waspas_total'], 4); // Pembulatan hasil akhir ke 4 desimal
            $rank = $index + 1;

            $this->results[] = [
                'employee_id' => $item['employee_id'],
                'name' => $item['name'],
                'total' => $finalScore,
                'rank' => $rank,
                'normalized_scores' => $item['normalized_scores'],
                'saw_scores_sum' => round($item['saw_sum'], 4), // Tampilkan total SAW
                'wp_scores_product' => round($item['wp_product'], 4), // Tampilkan total WPM
                'lambda' => $lambda,
            ];

            $this->evaluatedResults[] = [
                'employee_id' => $item['employee_id'],
                'final_score' => $finalScore,
                'rank' => $rank,
                'name' => $item['name'],
                'evaluation_id' => $this->evaluation_id, // Tambahkan evaluation_id untuk penyimpanan
            ];
        }

        Notification::make()
            ->title('Evaluasi berhasil dihitung')
            ->body('Hasil evaluasi kinerja telah berhasil dihitung menggunakan metode WASPAS.') // Notifikasi WASPAS
            ->success()
            ->send();
    }

    public function updatedEvaluationId($value)
    {
        $this->loadHistory($value);
    }

    public function loadHistory($evaluationId)
    {
        // Validasi sederhana jika evaluationId null
        if (is_null($evaluationId)) {
            $this->history = [];
            return;
        }

        $this->history = PerformanceResult::with('employee')
            ->where('evaluation_id', $evaluationId)
            ->orderBy('score', 'desc')
            ->get()
            ->map(function ($result, $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $result->employee->name ?? 'N/A', // Tambah null coalescing
                    'score' => round($result->score, 4),
                ];
            })->toArray();
    }

    public function submit()
    {
        // Validasi: pastikan sudah ada hasil evaluasi
        if (empty($this->evaluatedResults)) {
            Notification::make()
                ->title('Belum ada hasil evaluasi')
                ->body('Silakan lakukan evaluasi terlebih dahulu sebelum menyimpan.')
                ->warning()
                ->send();
            return;
        }

        // Cek apakah sudah ada data hasil evaluasi untuk evaluation_id ini
        $exists = PerformanceResult::where('evaluation_id', $this->evaluation_id)->exists();

        if ($exists) {
            Notification::make()
                ->title('Data sudah pernah disimpan')
                ->body('Evaluasi untuk periode ini sudah pernah disimpan sebelumnya.')
                ->warning()
                ->send();
            return;
        }

        // Simpan hasil evaluasi
        foreach ($this->evaluatedResults as $result) {
            PerformanceResult::create([
                'employee_id' => $result['employee_id'],
                'evaluation_id' => $this->evaluation_id,
                'score' => $result['final_score'],
            ]);
        }

        Notification::make()
            ->title('Hasil evaluasi berhasil disimpan')
            ->body('Hasil evaluasi kinerja telah berhasil disimpan ke database.')
            ->success()
            ->send();

        // Reset hasil setelah disimpan
        $this->results = [];
        $this->evaluatedResults = [];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('evaluation_id')
                ->label('Pilih Periode Evaluasi')
                ->options(Evaluation::all()->pluck('period', 'id'))
                ->required()
                ->reactive()
                ->afterStateUpdated(fn($state) => $this->updatedEvaluationId($state)),

            Forms\Components\TextInput::make('lambda')
                ->label('Parameter Lambda (0-1)')
                ->numeric()
                ->default(0.5)
                ->minValue(0)
                ->maxValue(1)
                ->step(0.1)
                ->helperText('0 = 100% WP, 0.5 = 50% SAW + 50% WP, 1 = 100% SAW'),

            Forms\Components\Placeholder::make('')
                ->content('')
                ->extraAttributes(['class' => 'block h-2']),
        ];
    }

    protected function getFormModel(): string
    {
        return static::class;
    }
}
