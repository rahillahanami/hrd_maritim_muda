<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'starts_at', // Pastikan ini nama kolom di DB
        'ends_at',   // Pastikan ini nama kolom di DB
        'division_id',
        'created_by', // Tetap string sesuai DB Anda
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];


    public function division()
    {
        return $this->belongsTo(Division::class);
    }



}
