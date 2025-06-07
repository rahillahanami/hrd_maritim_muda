<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',          // <<< Sesuaikan
        'check_in',      // <<< Sesuaikan dari check_in_time
        'check_out',     // <<< Sesuaikan dari check_out_time
        'early_minutes', // <<< Tambahkan
        'late_minutes',  // <<< Tambahkan
        // ... kolom lain yang mungkin ada ...
    ];

    protected $casts = [
        'date' => 'date',           // <<< Sesuaikan
        'check_in' => 'datetime',   // <<< Sesuaikan dari check_in_time
        'check_out' => 'datetime',  // <<< Sesuaikan dari check_out_time
        'early_minutes' => 'integer',
        'late_minutes' => 'integer',
    ];

    // Relasi ke Employee (sudah benar)
    public function employee()
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public static function calculateAttendanceTimes($checkInTime, $baseCheckIn)
    {
        $earlyMinutes = 0;
        $lateMinutes = 0;

        if ($checkInTime->lessThan($baseCheckIn)) {
            $earlyMinutes = $checkInTime->diffInMinutes($baseCheckIn);
        } elseif ($checkInTime->greaterThan($baseCheckIn)) {
            $lateMinutes = $checkInTime->diffInMinutes($baseCheckIn);
        }

        return [
            'early_minutes' => max(0, $earlyMinutes),
            'late_minutes' => max(0, $lateMinutes),
        ];
    }
}
