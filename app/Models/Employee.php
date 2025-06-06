<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    //  protected $fillable = [
    //     'user_id', // Sudah ada
    //     'name',
    //     'nip',
    //     'phone_number',
    //     'position',
    //     'department',
    //     'date_joined',
    //     'date_of_birth',
    //     'gender',
    //     'address',
    // ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
