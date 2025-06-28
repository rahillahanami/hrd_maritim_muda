<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Models\Attendance;
use Illuminate\Support\Carbon;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        // // Only calculate if check_in is provided
        // if (isset($data['check_in']) && $data['check_in']) {
        //     $checkInTime = Carbon::parse($data['check_in']);
            
        //     // Use the same base check-in time as user check-ins
        //     $baseCheckIn = $checkInTime->copy()
        //                               ->setHour(ListAttendances::STANDARD_CHECK_IN_HOUR)
        //                               ->setMinute(ListAttendances::STANDARD_CHECK_IN_MINUTE)
        //                               ->setSecond(0);

        //     // Calculate late/early minutes
        //     $times = $this->calculateAttendanceTimes($checkInTime, $baseCheckIn);
            
        //     // Assign calculated values to the data array
        //     $data['early_minutes'] = $times['early_minutes'];
        //     $data['late_minutes'] = $times['late_minutes'];
        // } else {
        //     // If no check_in time, set defaults
        //     $data['early_minutes'] = 0;
        //     $data['late_minutes'] = 0;
        // }

        return $data;
    }

    /**
     * Calculate attendance times (early/late minutes)
     * Same logic as in ListAttendances for consistency
     */
    private function calculateAttendanceTimes($checkInTime, $baseCheckIn)
    {
        $earlyMinutes = 0;
        $lateMinutes = 0;

        if ($checkInTime->lessThan($baseCheckIn)) {
            // Check-in is early
            $earlyMinutes = $checkInTime->diffInMinutes($baseCheckIn);
        } elseif ($checkInTime->greaterThan($baseCheckIn)) {
            // Check-in is late - AUTOMATIC CALCULATION
            $lateMinutes = $checkInTime->diffInMinutes($baseCheckIn);
        }

        return [
            'early_minutes' => max(0, (int) round($earlyMinutes)),
            'late_minutes' => max(0, (int) round($lateMinutes)),
        ];
    }
}