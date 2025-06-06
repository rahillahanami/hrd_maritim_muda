<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model

{

    use HasFactory;
    protected $fillable = ['period', 'notes'];

    public function scores()
    {
        return $this->hasMany(EmployeeScore::class);
    }
}

