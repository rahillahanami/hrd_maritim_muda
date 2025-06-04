<?php

namespace App\Filament\Widgets;

use App\Models\PerformanceResult;
use App\Models\Employee;
use Filament\Widgets\ChartWidget;

class PerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Grafik Evaluasi Karyawan';

    protected function getData(): array
    {
        $results = PerformanceResult::with('employee')
            ->orderByDesc('score')
            ->limit(10) // ambil 10 karyawan teratas
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Nilai Evaluasi',
                    'data' => $results->pluck('score'),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.7)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $results->pluck('employee.name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // sesuai pilihan sebelumnya
    }
}
