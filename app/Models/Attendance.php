<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $guarded = [];

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
}
