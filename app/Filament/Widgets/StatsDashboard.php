<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use App\Models\Employee;
use App\Models\Resignation;
use App\Models\Leave;
use App\Models\Division;
use App\Filament\Resources\EmployeeResource; // Untuk helper isCurrentUserAdmin


class StatsDashboard extends BaseWidget
{
    // Opsional: atur columnSpan untuk widget ini (jika ingin mengambil lebar penuh)
    // protected int | string | array $columnSpan = 'full';

    // Helper isCurrentUserAdmin (bisa diulang atau diakses dari Resource lain)
    protected function isCurrentUserAdmin(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin'); // Sesuaikan peran admin
    }

    protected function getStats(): array
    {
        $stats = [];
        $isAdmin = $this->isCurrentUserAdmin(); // Ambil status admin di sini

        // 1. Statistik untuk ADMIN SAJA
        if ($isAdmin) {
            // Jumlah Pegawai Aktif
            $activeEmployeesCount = Employee::query()->withoutTrashed()->count();
            $stats[] = Stat::make('Jumlah Pegawai Aktif', $activeEmployeesCount)
                ->description('Total pegawai yang aktif')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success');

            // Jumlah Pengajuan Pengunduran Diri Pending
            $pendingResignationsCount = Resignation::where('status', 'Pending')->count();
            $stats[] = Stat::make('Pengajuan Pengunduran Diri Pending', $pendingResignationsCount)
                ->description('Menunggu persetujuan HR')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning');

            // Jumlah Pengajuan Cuti Pending
            $pendingLeavesCount = Leave::where('status', 'pending')->count();
            $stats[] = Stat::make('Pengajuan Cuti Pending', $pendingLeavesCount)
                ->description('Menunggu persetujuan HR')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info');

            // Jumlah Divisi
            $divisionCount = Division::count();
            $stats[] = Stat::make('Jumlah Divisi', $divisionCount)
                ->description('Total divisi yang terdaftar')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color('primary');
        }

        if (!$isAdmin) {
        }

        return $stats;
    }
}
