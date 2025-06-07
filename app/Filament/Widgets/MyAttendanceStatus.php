<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Filament\Facades\Filament;
use App\Filament\Resources\EmployeeResource; // Untuk helper isCurrentUserAdmin


class MyAttendanceStatus extends Widget
{
    protected static string $view = 'filament.widgets.my-attendance-status';

    // Hanya terlihat oleh user biasa (bukan Admin)
    public static function canView(): bool
    {
        return !EmployeeResource::isCurrentUserAdmin();
    }

    // Opsional: mengatur columnSpan untuk widget ini
    protected int | string | array $columnSpan = 1; // Ambil 1 kolom

    public Attendance $todayAttendance;
    public ?Carbon $checkInTime = null;
    public ?Carbon $checkOutTime = null;
    public ?int $earlyMinutes = null;
    public ?int $lateMinutes = null;
    public string $statusToday = 'Belum Check In';
    public string $statusColor = 'warning';

    public function mount(): void
    {
        $this->loadAttendanceStatus();
    }

    protected function loadAttendanceStatus(): void
    {
        $currentUser = Filament::auth()->user();
        $employeeId = $currentUser->employee?->id;

        if ($employeeId) {
            $todayAttendanceRecord = Attendance::where('employee_id', $employeeId)
                                               ->whereDate('date', Carbon::today())
                                               ->first();

            if ($todayAttendanceRecord) {
                $this->todayAttendance = $todayAttendanceRecord;
                $this->checkInTime = $todayAttendanceRecord->check_in;
                $this->checkOutTime = $todayAttendanceRecord->check_out;
                $this->earlyMinutes = $todayAttendanceRecord->early_minutes;
                $this->lateMinutes = $todayAttendanceRecord->late_minutes;

                if ($this->checkOutTime) {
                    $this->statusToday = 'Sudah Check Out';
                    $this->statusColor = 'success';
                } elseif ($this->checkInTime) {
                    $this->statusToday = 'Sudah Check In';
                    $this->statusColor = 'info';
                }
            }
        }
    }
    // Mungkin perlu method refresh data jika ada aksi di halaman ini
    // public function refreshData(): void
    // {
    //     $this->loadAttendanceStatus();
    // }
}