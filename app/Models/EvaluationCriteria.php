<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriteria extends Model
{

    use HasFactory;
    protected $fillable = ['name', 'weight', 'type']; // type = 'benefit' or 'cost'

    public function scores()
    {
        return $this->hasMany(EmployeeScore::class, 'criteria_id');
    }
}

