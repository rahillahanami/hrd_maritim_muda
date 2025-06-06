<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Facades\Filament;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification; // <<< PASTIKAN INI ADA UNTUK NOTIFIKASI

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    // *** KONSTANTA UNTUK JAM KERJA STANDAR & TOLERANSI ***
    const STANDARD_CHECK_IN_HOUR = 8; // Jam masuk standar (08:00)
    const STANDARD_CHECK_IN_MINUTE = 0; // Menit masuk standar (08:00)
    const LATE_TOLERANCE_MINUTES = 5; // Toleransi keterlambatan dalam menit (misal: 5 menit)
    const EARLY_TOLERANCE_MINUTES = 0; // Toleransi datang terlalu cepat (0 berarti tidak ada toleransi)


    protected function getHeaderActions(): array
    {
        $currentUser = Filament::auth()->user();
        // Peran admin disesuaikan dengan 'super_admin' dari Filament Shield
        $isAdmin = $currentUser && $currentUser->hasRole('super_admin');

        // Jika user adalah admin, mereka bisa membuat record attendance manual
        if ($isAdmin) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        // --- Logika untuk user biasa (non-admin) ---

        $employeeId = null;
        // Pastikan user punya relasi ke Employee (employee()) di model User
        if ($currentUser && $currentUser->employee) {
            $employeeId = $currentUser->employee->id;
        }

        // Cari attendance hari ini untuk employee yang login
        $todayAttendance = null;
        if ($employeeId) { // Hanya cari jika employeeId ditemukan
            $todayAttendance = Attendance::where('employee_id', $employeeId)
                ->whereDate('date', Carbon::today())
                ->first();
        }

        $actions = [];

        // Tombol Check In
        if ($employeeId && !$todayAttendance) {
            $actions[] = Actions\Action::make('check_in')
                ->label('Check In')
                ->color('success')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->requiresConfirmation()
                ->action(function () use ($employeeId) {
                    $checkInTime = Carbon::now(); // Waktu check-in aktual

                    $baseCheckIn = Carbon::today()->setHour(self::STANDARD_CHECK_IN_HOUR)->setMinute(self::STANDARD_CHECK_IN_MINUTE)->setSecond(0);

                    $earlyMinutes = 0;
                    $lateMinutes = 0;

                    // Gunakan copy() untuk menghindari modifikasi objek standardCheckIn
                    $standardCheckInEarlyBound = (clone $baseCheckIn)->subMinutes(self::EARLY_TOLERANCE_MINUTES);
                    $standardCheckInLateBound = (clone $baseCheckIn)->addMinutes(self::LATE_TOLERANCE_MINUTES);

                    // --- LOGIKA PERHITUNGAN BARU ---
                    if ($checkInTime->lessThan($standardCheckInEarlyBound)) {
                        $earlyMinutes = $checkInTime->diffInMinutes($baseCheckIn);
                    } elseif ($checkInTime->greaterThan($standardCheckInLateBound)) {
                        $lateMinutes = $checkInTime->diffInMinutes($baseCheckIn);
                    }

                    // Pastikan nilai tidak negatif (tetap jaga ini sebagai safety net)
                    $earlyMinutes = max(0, $earlyMinutes);
                    $lateMinutes = max(0, $lateMinutes);

                    Attendance::create([
                        'employee_id' => $employeeId,
                        'date' => Carbon::today(),
                        'check_in' => $checkInTime,
                        'early_minutes' => $earlyMinutes,
                        'late_minutes' => $lateMinutes,
                    ]);

                    Notification::make()
                        ->title('Berhasil Check In!')
                        ->body("Anda telah berhasil Check In. Awal: {$earlyMinutes} menit, Terlambat: {$lateMinutes} menit.")
                        ->success()
                        ->send();
                });
        }

        // Tombol Check Out
        if ($todayAttendance && !$todayAttendance->check_out) { // Muncul jika sudah check-in dan belum check-out
            $actions[] = Actions\Action::make('check_out')
                ->label('Check Out')
                ->color('danger')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->requiresConfirmation()
                ->action(function () use ($todayAttendance) {
                    $todayAttendance->update([
                        'check_out' => Carbon::now(), // Kolom di DB adalah 'check_out'
                        // Jika ada kolom status di tabel attendance, atur juga di sini. Contoh: 'status' => 'Completed'
                    ]);

                    Notification::make() // Menggunakan Notifikasi Filament
                        ->title('Berhasil Check Out!')
                        ->body('Anda telah berhasil melakukan Check Out untuk hari ini.')
                        ->success() // Atau info jika notifnya tidak selalu "sukses"
                        ->send();
                });
        }

        return $actions;
    }
}
