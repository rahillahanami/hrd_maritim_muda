<?php

// namespace App\Filament\Widgets;

// use Filament\Widgets\StatsOverviewWidget as BaseWidget;
// use Filament\Widgets\StatsOverviewWidget\Stat;
// use App\Models\Employee;
// use Filament\Facades\Filament;
// use App\Filament\Resources\EmployeeResource;

// class ActiveEmployeesOverview extends BaseWidget
// {

//     public function getColumns(): int
//     {
//         return 4;
//     }

//     // Kondisional untuk widget ini (hanya terlihat oleh Admin)
//     public static function canView(): bool
//     {
//         return \App\Filament\Resources\EmployeeResource::isCurrentUserAdmin();
//     }


//     protected function getStats(): array
//     {
//         $activeEmployeesCount = Employee::query()->withoutTrashed()->count();

//         return [
//             Stat::make('Jumlah Pegawai Aktif', $activeEmployeesCount)
//                 ->description('Total pegawai yang aktif')
//                 ->descriptionIcon('heroicon-m-arrow-trending-up')
//                 ->color('success'),
//         ];
//     }
// }
