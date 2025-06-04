<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{

    use HasFactory;

    protected $fillable = [
        'employee_id',
        'evaluation_id', // 🟢 tambahkan ini
        'base_salary',
        'bonus',
        'final_salary',
        'performance_score',
    ];

    public function employee()
    {
        // Asumsi 'employee_id' di tabel salaries adalah foreign key ke 'employees.id'
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the evaluation that owns the salary record.
     */
    public function evaluation()
    {
        // Asumsi 'evaluation_id' di tabel salaries adalah foreign key ke 'evaluations.id'
        // Tuan Muda perlu memastikan model Evaluation (App\Models\Evaluation) ada
        // dan tabel evaluations ada.
        return $this->belongsTo(Evaluation::class);
    }
}
