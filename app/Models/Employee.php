<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'name',
        'nip',
        'gender',
        'birth_date',
        'phone_number',
        'address',
        'division_id',
    ];

    protected $casts = [
        'date_joined' => 'date',
        'date_of_birth' => 'date',
        'deleted_at' => 'datetime', // <<< PASTIKAN INI ADA
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function headedDivisions()
    {
        // Asumsi: 'head_id' di tabel divisions merujuk ke 'employees.id'
        return $this->hasMany(Division::class, 'head_id');
    }

    public function scores()
    {
        return $this->hasMany(EmployeeScore::class);
    }

    public function performanceResults()
    {
        return $this->hasMany(PerformanceResult::class);
    }
}
