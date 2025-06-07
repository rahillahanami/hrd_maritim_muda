<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Models\Attendance;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['check_in'])) {
            $checkInTime = \Illuminate\Support\Carbon::parse($data['check_in']);
            $baseCheckIn = now()->setHour(8)->setMinute(0)->setSecond(0);

            // Define tolerance bounds
            $standardCheckInEarlyBound = (clone $baseCheckIn)->subMinutes(ListAttendances::EARLY_TOLERANCE_MINUTES);
            $standardCheckInLateBound = (clone $baseCheckIn)->addMinutes(ListAttendances::LATE_TOLERANCE_MINUTES);

            // Initialize early and late minutes
            $earlyMinutes = 0;
            $lateMinutes = 0;

            // Check if check-in is early
            if ($checkInTime->lessThan($baseCheckIn)) {
                $earlyMinutes = (int) round($checkInTime->diffInMinutes($baseCheckIn));
            }

            // Check if check-in is late
            if ($checkInTime->greaterThan($baseCheckIn)) {
                $lateMinutes = (int) round($checkInTime->diffInMinutes($baseCheckIn));
            }

            // Ensure values are non-negative
            $earlyMinutes = max(0, $earlyMinutes);
            $lateMinutes = max(0, $lateMinutes);

            // Assign calculated values to the data array
            $data['early_minutes'] = $earlyMinutes;
            $data['late_minutes'] = $lateMinutes;
        }

        $times = Attendance::calculateAttendanceTimes($checkInTime, $baseCheckIn);
        $data['early_minutes'] = $times['early_minutes'];
        $data['late_minutes'] = $times['late_minutes'];

        return $data;
    }
}
