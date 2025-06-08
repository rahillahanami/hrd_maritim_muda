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

class EvaluatePerformance extends Page implements HasForms
{
    use InteractsWithForms;

    public array $evaluatedResults = [];
    public array $results = [];

    public $history = [];
    public $evaluation_id;

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
    }

    public function evaluate()
    {
        $criteria = EvaluationCriteria::all();
        $employees = Employee::all();

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

        // ===== NORMALISASI SAW =====
        $normalized = [];
        
        // Inisialisasi struktur normalized
        foreach ($scoresMatrix as $empId => $data) {
            $normalized[$empId] = [
                'employee_id' => $empId,
                'name' => $data['name'],
                'normalized_scores' => [],
                'weighted_scores' => [],
                'total' => 0
            ];
        }

        // Normalisasi per kriteria
        foreach ($criteria as $criterion) {
            // Ambil semua nilai untuk kriteria ini
            $values = [];
            foreach ($scoresMatrix as $empId => $data) {
                $values[] = $data['scores'][$criterion->id];
            }

            // Hitung min dan max
            $maxValue = max($values);
            $minValue = min($values);

            // Normalisasi setiap nilai untuk kriteria ini
            foreach ($scoresMatrix as $empId => $data) {
                $originalValue = $data['scores'][$criterion->id];
                $normalizedValue = 0;

                if ($criterion->type === 'benefit') {
                    // Untuk kriteria benefit (semakin tinggi semakin baik)
                    // Formula: r_ij = x_ij / max(x_ij)
                    $normalizedValue = $maxValue > 0 ? $originalValue / $maxValue : 0;
                } else {
                    // Untuk kriteria cost (semakin rendah semakin baik)
                    // Formula: r_ij = min(x_ij) / x_ij
                    if ($originalValue > 0) {
                        $normalizedValue = $minValue / $originalValue;
                    } else {
                        // Jika nilai asli = 0, berikan nilai normalisasi tertinggi (1)
                        // karena 0 adalah nilai terbaik untuk kriteria cost
                        $normalizedValue = 1;
                    }
                }

                // Simpan nilai normalisasi
                $normalized[$empId]['normalized_scores'][$criterion->id] = $normalizedValue;
                
                // Hitung nilai terbobot dan tambahkan ke total
                $weightedScore = $normalizedValue * ($criterion->weight); // Asumsi weight dalam persen
                $normalized[$empId]['weighted_scores'][$criterion->id] = $weightedScore;
                $normalized[$empId]['total'] += $weightedScore;
            }
        }

        // Urutkan berdasarkan total skor (descending)
        $sorted = collect($normalized)
            ->sortByDesc('total')
            ->values()
            ->toArray();

        // Buat hasil akhir dengan ranking
        $this->results = [];
        $this->evaluatedResults = [];

        foreach ($sorted as $index => $item) {
            $finalScore = round($item['total'], 4);
            $rank = $index + 1;

            $this->results[] = [
                'employee_id' => $item['employee_id'],
                'name' => $item['name'],
                'total' => $finalScore,
                'rank' => $rank,
                'normalized_scores' => $item['normalized_scores'],
                'weighted_scores' => $item['weighted_scores']
            ];

            $this->evaluatedResults[] = [
                'employee_id' => $item['employee_id'],
                'final_score' => $finalScore,
                'rank' => $rank,
                'name' => $item['name'],
            ];
        }

        Notification::make()
            ->title('Evaluasi berhasil dihitung')
            ->body('Hasil evaluasi kinerja telah berhasil dihitung menggunakan metode SAW.')
            ->success()
            ->send();
    }

    public function updatedEvaluationId($value)
    {
        $this->loadHistory($value);
    }

    public function loadHistory($evaluationId)
    {
        $this->history = PerformanceResult::with('employee')
            ->where('evaluation_id', $evaluationId)
            ->orderBy('score', 'desc')
            ->get()
            ->map(function ($result, $index) {
                return [
                    'rank' => $index + 1,
                    'name' => $result->employee->name,
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
                ->afterStateUpdated(fn ($state) => $this->updatedEvaluationId($state)),

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