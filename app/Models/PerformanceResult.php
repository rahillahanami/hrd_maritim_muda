<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'evaluation_id',
        'score',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }
}
