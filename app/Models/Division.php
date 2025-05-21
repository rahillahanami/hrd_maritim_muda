<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Division extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function head()
    {
        return $this->belongsTo(Employee::class, 'head_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
