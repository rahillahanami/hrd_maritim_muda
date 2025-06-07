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
                    $checkInTime = now(); // Waktu check-in aktual
                    $baseCheckIn = now()->setHour(8)->setMinute(0)->setSecond(0);

                    // Hitung keterlambatan
                    $earlyMinutes = 0;
                    $lateMinutes = 0;

                    if ($checkInTime->lessThan($baseCheckIn)) {
                        // Jika check-in lebih awal
                        $earlyMinutes = $checkInTime->diffInMinutes($baseCheckIn);
                    } elseif ($checkInTime->greaterThan($baseCheckIn)) {
                        // Jika check-in terlambat
                        $lateMinutes = $checkInTime->diffInMinutes($baseCheckIn);
                    }

                    // Pastikan nilai tidak negatif
                    $earlyMinutes = max(0, $earlyMinutes);
                    $lateMinutes = max(0, $lateMinutes);

                    $times = Attendance::calculateAttendanceTimes($checkInTime, $baseCheckIn);

                    // Simpan data ke database
                    Attendance::create([
                        'employee_id' => $employeeId,
                        'date' => now()->toDateString(),
                        'check_in' => $checkInTime,
                        'early_minutes' => $earlyMinutes,
                        'late_minutes' => $lateMinutes,
                    ]);


                    Notification::make()
                        ->title('Berhasil Check In!')
                        ->body("Anda telah berhasil Check In. Awal: {$times['early_minutes']} menit, Terlambat: {$times['late_minutes']} menit.")
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
