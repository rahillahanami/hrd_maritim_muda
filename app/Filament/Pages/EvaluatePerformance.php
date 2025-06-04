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
    protected static ?string $title = 'Evaluasi Kinerja Karyawan';
    protected static ?string $navigationGroup = 'Sistem Pengambilan Keputusan'; // <<< NAMA GRUP
    protected static ?int $navigationSort = 4; // <<< URUTAN KETIGA DI GRUP INI


    public function mount(): void
    {
        // Kosong, user harus pilih evaluasi dulu
    }

    public function evaluate()
    {
        $criteria = EvaluationCriteria::all();
        $employees = Employee::all();

        $scoresMatrix = [];

        // Ambil skor tiap karyawan per kriteria
        foreach ($employees as $employee) {
            foreach ($criteria as $c) {
                $score = EmployeeScore::where('employee_id', $employee->id)
                    ->where('evaluation_id', $this->evaluation_id)
                    ->where('evaluation_criteria_id', $c->id)
                    ->value('score');

                $scoresMatrix[$employee->id]['employee_id'] = $employee->id; // simpan id employee juga
                $scoresMatrix[$employee->id]['name'] = $employee->name;
                $scoresMatrix[$employee->id]['scores'][$c->id] = $score ?? 0;
            }
        }

        // Normalisasi
        $normalized = [];
        foreach ($criteria as $c) {
            $column = collect($scoresMatrix)->pluck("scores.{$c->id}");
            $max = $column->max();
            $min = $column->min();

            foreach ($scoresMatrix as $empId => $data) {
                $val = $data['scores'][$c->id];
                $norm = 0;

                if ($c->type === 'benefit') {
                    $norm = $max > 0 ? $val / $max : 0;
                } else {
                    $norm = $val > 0 ? $min / $val : 0;
                }

                $normalized[$empId]['employee_id'] = $empId;
                $normalized[$empId]['name'] = $data['name'];
                $normalized[$empId]['total'] = ($normalized[$empId]['total'] ?? 0) + ($norm * $c->weight);
            }
        }

        // Urutkan dan beri ranking
        $sorted = collect($normalized)
            ->sortByDesc('total')
            ->values()
            ->toArray();

        // Map ke format hasil akhir dan isi evaluatedResults buat simpan
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
            ];

            $this->evaluatedResults[] = [
                'employee_id' => $item['employee_id'],
                'final_score' => $finalScore,
                'rank' => $rank,
                'name' => $item['name'],
            ];
        }
    }

    public function updatedEvaluationId($value)
    {
        $this->loadHistory($value);
    }

    public function loadHistory($evaluationId)
    {
        $this->history = PerformanceResult::with('employee')
            ->where('evaluation_id', $evaluationId)
            ->get()
            ->map(function ($result) {
                return [
                    'name' => $result->employee->name,
                    'score' => $result->score,
                ];
            })->toArray();
    }

    public function submit()
    {
        // Cek dulu apakah sudah ada data hasil evaluasi untuk evaluation_id ini
        $exists = PerformanceResult::where('evaluation_id', $this->evaluation_id)->exists();

        if ($exists) {
            Notification::make()
                ->title('Data sudah pernah disimpan')
                ->body('Evaluasi untuk periode ini sudah pernah disimpan sebelumnya.')
                ->warning()
                ->send();
            return;
        }

        // Kalau belum ada, lanjut simpan
        foreach ($this->evaluatedResults as $result) {
            PerformanceResult::updateOrCreate(
                [
                    'employee_id' => $result['employee_id'],
                    'evaluation_id' => $this->evaluation_id,
                ],
                [
                    'score' => $result['final_score'],
                ]
            );
        }

        Notification::make()
            ->title('Nilai evaluasi berhasil disimpan')
            ->body('Hasil evaluasi kinerja telah berhasil disimpan.')
            ->success()
            ->send();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('evaluation_id')
                ->label('Pilih Periode Evaluasi')
                ->options(Evaluation::all()->pluck('period', 'id'))
                ->required(),
        ];
    }

    protected function getFormModel(): string
    {
        return static::class;
    }
}
