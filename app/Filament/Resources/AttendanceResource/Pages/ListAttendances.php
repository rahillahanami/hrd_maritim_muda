<?php

// 1. UPDATED ListAttendances.php - User Check-in Logic
namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Facades\Filament;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Container\Attributes\Log;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    // *** CONSTANTS FOR STANDARD WORK HOURS & TOLERANCE ***
    const STANDARD_CHECK_IN_HOUR = 8;
    const STANDARD_CHECK_IN_MINUTE = 0;
    const LATE_TOLERANCE_MINUTES = 5;
    const EARLY_TOLERANCE_MINUTES = 0;

    protected function getHeaderActions(): array
    {
        $currentUser = Filament::auth()->user();
        $isAdmin = $currentUser && $currentUser->hasRole('super_admin');

        if ($isAdmin) {
            return [
                Actions\CreateAction::make(),
            ];
        }

        // --- Logic for regular users (non-admin) ---
        $employeeId = null;
        if ($currentUser && $currentUser->employee) {
            $employeeId = $currentUser->employee->id;
        }

        $todayAttendance = null;
        if ($employeeId) {
            $todayAttendance = Attendance::where('employee_id', $employeeId)
                ->whereDate('date', Carbon::today())
                ->first();
        }

        $actions = [];

        // Check In Button
        if ($employeeId && !$todayAttendance) {
            $actions[] = Actions\Action::make('check_in')
                ->label('Check In')
                ->color('success')
                ->icon('heroicon-o-arrow-left-on-rectangle')
                ->requiresConfirmation()
                ->action(function () use ($employeeId) {
                    $checkInTime = now();

                    // FIXED: Create base check-in time using the SAME DATE as check-in
                    $baseCheckIn = $checkInTime->copy() // Use copy() to avoid modifying original
                        ->setHour(self::STANDARD_CHECK_IN_HOUR)
                        ->setMinute(self::STANDARD_CHECK_IN_MINUTE)
                        ->setSecond(0);


                    // Calculate late/early minutes using the centralized method
                    $times = $this->calculateAttendanceTimes($checkInTime, $baseCheckIn);

                    // Save to database
                    Attendance::create([
                        'employee_id' => $employeeId,
                        'date' => now()->toDateString(),
                        'check_in' => $checkInTime,
                        'early_minutes' => $times['early_minutes'],
                        'late_minutes' => $times['late_minutes'],
                    ]);

                    // Show notification with status
                    $status = $times['late_minutes'] > 0 ? 'TERLAMBAT' : ($times['early_minutes'] > 0 ? 'LEBIH AWAL' : 'TEPAT WAKTU');

                    Notification::make()
                        ->title('Berhasil Check In!')
                        ->body("Status: {$status}. Awal: {$times['early_minutes']} menit, Terlambat: {$times['late_minutes']} menit.")
                        ->success()
                        ->send();
                });
        }

        // Check Out Button
        if ($todayAttendance && !$todayAttendance->check_out) {
            $actions[] = Actions\Action::make('check_out')
                ->label('Check Out')
                ->color('danger')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->requiresConfirmation()
                ->action(function () use ($todayAttendance) {
                    $todayAttendance->update([
                        'check_out' => Carbon::now(),
                    ]);

                    Notification::make()
                        ->title('Berhasil Check Out!')
                        ->body('Anda telah berhasil melakukan Check Out untuk hari ini.')
                        ->success()
                        ->send();
                });
        }

        return $actions;
    }

    /**
     * Calculate attendance times (early/late minutes)
     */
    private function calculateAttendanceTimes($checkInTime, $baseCheckIn)
    {
        // FIXED: Calculate difference in seconds, then convert to minutes
        // Positive = late (check-in after base), Negative = early (check-in before base)
        $diffSeconds = $checkInTime->timestamp - $baseCheckIn->timestamp;
        $diffMinutes = $diffSeconds / 60;

        // Alternative approach that's more explicit:
        // $diffMinutes = $checkInTime->timestamp - $baseCheckIn->timestamp;
        // $diffMinutes = $diffMinutes / 60; // Convert seconds to minutes

        // // DEBUG: Log the calculation
        // \Log::info('Calculate Times Debug:', [
        //     'check_in' => $checkInTime->format('H:i:s'),
        //     'base_check_in' => $baseCheckIn->format('H:i:s'),
        //     'signed_diff' => $diffMinutes,
        //     'is_late' => $diffMinutes > 0,
        // ]);


        // Remove this dd() after testing
        
        $earlyMinutes = 0;
        $lateMinutes = 0;
        
        if ($diffMinutes < 0) {
            // Negative difference means early (check-in before base time)
            $earlyMinutes = abs($diffMinutes);
        } elseif ($diffMinutes > 0) {
            // Positive difference means late (check-in after base time)
            $lateMinutes = $diffMinutes;
        }
        // If $diffMinutes == 0, then exactly on time (both stay 0)
        
        return [
            'early_minutes' => (int) round($earlyMinutes),
            'late_minutes' => (int) round($lateMinutes),
        ];
        dd("Check-in: {$checkInTime->format('H:i:s')}, Base: {$baseCheckIn->format('H:i:s')}, Diff: {$diffMinutes} minutes, Is Late: " . ($diffMinutes > 0 ? 'Yes' : 'No'));
    }
}
