<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DivisiModel;
use App\Models\PegawaiModel;

class StatsDashboard extends BaseWidget
{
    protected function getStats(): array

    
    
    {

        $countDivisi = DivisiModel::count();
        $countPegawai = PegawaiModel::count();

        return [
            Stat::make('Jumlah Divisi', $countDivisi . ' Divisi'),
            Stat::make('Jumlah Pegawai', $countPegawai . ' Pegawai'),
            Stat::make('Average time on page', '3:12'),
        ];
    }
}
