<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeScore extends Model
{

    use HasFactory;

    protected $fillable = ['employee_id', 'evaluation_id', 'criteria_id', 'score', 'evaluation_criteria_id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

   public function evaluationCriteria()
    {
        return $this->belongsTo(EvaluationCriteria::class);
    }

    public function criteria()
    {
        return $this->belongsTo(\App\Models\EvaluationCriteria::class, 'evaluation_criteria_id');
    }

}

