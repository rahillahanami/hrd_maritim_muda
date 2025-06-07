<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\WorkPlan; // Import model WorkPlan
use App\Models\Division; // Import model Division (jika diperlukan untuk helper)
use Filament\Facades\Filament; // Import Filament Facade
use App\Filament\Resources\WorkPlanResource; // Import WorkPlanResource untuk helper
use Illuminate\Database\Eloquent\Builder; // <<< PASTIKAN INI ADA (untuk Builder di closure)


class MyDivisionWorkPlansOverview extends BaseWidget
{
    // Opsional: mengatur columnSpan untuk widget ini (ambil 1 kolom)
    protected int | string | array $columnSpan = 1;

    // Widget ini hanya terlihat oleh user biasa (bukan Admin)
    public static function canView(): bool
    {
        // Panggil helper isCurrentUserAdmin dari WorkPlanResource dengan namespace lengkap
        return !\App\Filament\Resources\WorkPlanResource::isCurrentUserAdmin();
    }

    protected function getStats(): array
    {
        $currentUser = Filament::auth()->user();
        $userDivisionId = null; // Inisialisasi dengan null

        // Dapatkan ID divisi dari user yang login (melalui employee->division)
        // Pastikan user punya objek employee, dan employee punya objek division
        if ($currentUser && $currentUser->employee && $currentUser->employee->division) {
            $userDivisionId = $currentUser->employee->division->id;
        }

        $uncompletedWorkPlansCount = 0; // Default count jika tidak ada divisi atau error

        // Hanya hitung jika user terhubung ke sebuah divisi
        if ($userDivisionId) {
            // Hitung Work Plan yang ditujukan untuk divisi user
            // ATAU Work Plan yang bersifat GLOBAL (division_id IS NULL)
            // dan statusnya BUKAN 'Completed'
            $uncompletedWorkPlansCount = WorkPlan::where(function (\Illuminate\Database\Eloquent\Builder $query) use ($userDivisionId) { // <<< PERBAIKAN DI SINI
                                            $query->where('division_id', $userDivisionId)
                                                  ->orWhereNull('division_id'); // Termasuk WorkPlan global
                                        })
                                        ->where('status', '!=', 'Completed') // Status bukan Completed
                                        ->count();
        }

        return [
            Stat::make('Work Plan Divisi Belum Selesai', $uncompletedWorkPlansCount)
                ->description('Target yang masih harus diselesaikan divisi Anda')
                ->descriptionIcon('heroicon-m-clipboard')
                ->color('warning'),
        ];
    }
}