<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Division;
use App\Models\Employee;

class StatsDashboard extends BaseWidget
{
    protected function getStats(): array


    {

        $countDivisi = Division::count();
        $countPegawai = Employee::count();

        return [
            Stat::make('Jumlah Divisi', $countDivisi . ' Divisi'),
            Stat::make('Jumlah Pegawai', $countPegawai . ' Pegawai'),
            Stat::make('Average time on page', '3:12'),
        ];
    }
}
