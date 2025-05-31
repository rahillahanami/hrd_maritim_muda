<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Division extends Model
{
    use HasFactory;
    protected $guarded = [];

        protected $fillable = [
        'name',
        'head_id', // Pastikan ini ada di $fillable
        'description',
    ];

    public function head()
    {
        return $this->belongsTo(Employee::class, 'head_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

       public function workPlans()
    {
        return $this->hasMany(WorkPlan::class);
    }
    
}
