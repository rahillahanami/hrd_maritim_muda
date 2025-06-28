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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EvaluatePerformance extends Page implements HasForms
{
    use InteractsWithForms;

    public array $evaluatedResults = [];
    public array $results = [];

    public $history = [];
    public $evaluation_id;
    public $lambda = 0.5;

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
        // Kosong
    }

    public function evaluate()
    {
        Log::info('DEBUG SPK: Evaluasi dimulai.');
        Log::info('DEBUG SPK: Evaluation ID selected: ' . ($this->evaluation_id ?? 'NULL'));
        Log::info('DEBUG SPK: Lambda parameter: ' . $this->lambda);

        $criteria = EvaluationCriteria::all();
        $employees = Employee::all();

        $lambda = $this->lambda ?? 0.5;

        if ($criteria->isEmpty() || $employees->isEmpty()) {
            Notification::make()
                ->title('Data tidak lengkap')
                ->body('Pastikan sudah ada data kriteria dan karyawan.')
                ->warning()
                ->send();
            return;
        }

        $scoresMatrix = [];

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

        $normalized = [];
        foreach ($scoresMatrix as $empId => $data) {
            $normalized[$empId] = [
                'employee_id' => $empId,
                'name' => $data['name'],
                'normalized_scores' => [],
                'saw_sum' => 0,
                'wp_product' => 1.0,
                'waspas_total' => 0
            ];
        }

        foreach ($criteria as $criterion) {
            $values = [];
            foreach ($scoresMatrix as $data) {
                $values[] = (float) $data['scores'][$criterion->id];
            }

            $maxValue = max($values);
            $minValue = min($values);

            foreach ($scoresMatrix as $empId => $data) {
                $originalValue = (float) $data['scores'][$criterion->id];
                $normalizedValue = 0.0;

                if ($criterion->type === 'benefit') {
                    $normalizedValue = $maxValue > 0 ? $originalValue / $maxValue : 0.0;
                } else {
                    $normalizedValue = $originalValue > 0 ? $minValue / $originalValue : 1.0;
                }

                $normalized[$empId]['normalized_scores'][$criterion->id] = $normalizedValue;
            }
        }

        foreach ($normalized as $empId => &$data) {
            $sawSum = 0.0;
            $wpProduct = 1.0;

            foreach ($criteria as $criterion) {
                $normalizedValue = $data['normalized_scores'][$criterion->id];
                $weight = (float) $criterion->weight;

                $sawSum += $weight * $normalizedValue;

                if ($normalizedValue > 0) {
                    $wpProduct *= pow($normalizedValue, $weight);
                } else {
                    $wpProduct = 0.0;
                    break;
                }
            }

            $data['saw_sum'] = $sawSum;
            $data['wp_product'] = $wpProduct;
            $data['waspas_total'] = ($lambda * $sawSum) + ((1 - $lambda) * $wpProduct);
        }

        $sorted = collect($normalized)->sortByDesc('waspas_total')->values()->toArray();

        $this->results = [];
        $this->evaluatedResults = [];

        foreach ($sorted as $index => $item) {
            $finalScore = round($item['waspas_total'], 4);
            $rank = $index + 1;

            $this->results[] = [
                'employee_id' => $item['employee_id'],
                'name' => $item['name'],
                'total' => $finalScore,
                'rank' => $rank,
                'normalized_scores' => $item['normalized_scores'],
                'saw_scores_sum' => round($item['saw_sum'], 4),
                'wp_scores_product' => round($item['wp_product'], 4),
                'lambda' => $lambda,
            ];

            $this->evaluatedResults[] = [
                'employee_id' => $item['employee_id'],
                'final_score' => $finalScore,
                'rank' => $rank,
                'name' => $item['name'],
                'evaluation_id' => $this->evaluation_id,
            ];
        }

        Notification::make()
            ->title('Evaluasi berhasil dihitung')
            ->body('Hasil evaluasi kinerja telah berhasil dihitung menggunakan metode WASPAS.')
            ->success()
            ->send();
    }

    public function updatedEvaluationId($value)
    {
        $this->loadHistory($value);
    }

    public function loadHistory($evaluationId)
    {
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
                    'name' => $result->employee->name ?? 'N/A',
                    'score' => round($result->score, 4),
                ];
            })->toArray();
    }

    public function submit()
    {
        if (empty($this->evaluatedResults)) {
            Notification::make()
                ->title('Belum ada hasil evaluasi')
                ->body('Silakan lakukan evaluasi terlebih dahulu sebelum menyimpan.')
                ->warning()
                ->send();
            return;
        }

        $exists = PerformanceResult::where('evaluation_id', $this->evaluation_id)->exists();

        if ($exists) {
            Notification::make()
                ->title('Data sudah pernah disimpan')
                ->body('Evaluasi untuk periode ini sudah pernah disimpan sebelumnya.')
                ->warning()
                ->send();
            return;
        }

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

        $this->results = [];
        $this->evaluatedResults = [];
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('evaluation_id')
                ->label('Pilih Periode Evaluasi')
                ->options(function () {
                    return Evaluation::all()->mapWithKeys(function ($eval) {
                        try {
                            $date = Carbon::createFromFormat('Y-m', $eval->period);
                            $englishMonth = $date->format('F');
                            $year = $date->format('Y');
                            $localized = convertEnglishMonthToIndonesian($englishMonth);
                            return [$eval->id => $localized . ' ' . $year];
                        } catch (\Exception $e) {
                            Log::error('Invalid date format in Evaluation: ' . $eval->period);
                            return [$eval->id => $eval->period];
                        }
                    });
                })
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

            Forms\Components\Placeholder::make('')->content('')->extraAttributes(['class' => 'block h-2']),
        ];
    }

    protected function getFormModel(): string
    {
        return static::class;
    }
}
