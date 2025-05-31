<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Facades\Filament;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Employee; // <<< Import Employee model
use Illuminate\Support\Carbon;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = $currentUser && $currentUser->hasRole('admin');

        // Jika user adalah admin, mereka bisa membuat record attendance manual
        if ($isAdmin) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        // --- Logika untuk user biasa (non-admin) ---

        $employeeId = null;
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
        if ($employeeId && !$todayAttendance) { // Muncul jika user punya employee_id dan belum check-in hari ini
            $actions[] = Actions\Action::make('check_in')
                ->label('Check In')
                ->color('success')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->requiresConfirmation()
                ->action(function () use ($employeeId) {
                    Attendance::create([
                        'employee_id' => $employeeId,
                        'date' => Carbon::today(),
                        'check_in' => Carbon::now(), // <<< Sesuaikan ke 'check_in'
                        'early_minutes' => 0, // <<< Tambahkan default
                        'late_minutes' => 0,  // <<< Tambahkan default
                        // check_out akan null saat check-in
                        // Jika ada kolom status, atur juga di sini. Contoh: 'status' => 'Present'
                    ]);

                    // Filament::notify('success', 'Berhasil Check In!');
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
                        'check_out' => Carbon::now(), // <<< Sesuaikan ke 'check_out'
                        // Jika ada kolom status, atur juga di sini. Contoh: 'status' => 'Completed'
                    ]);

                    // Filament::notify('success', 'Berhasil Check Out!');
                });
        }

        return $actions;
    }
}