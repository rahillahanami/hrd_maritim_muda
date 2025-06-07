<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resignation extends Model
{
    use HasFactory;

     protected $table = 'resignations'; // Pastikan nama tabelnya 'resignations'

     protected $fillable = [
        'user_id',
        'submission_date',
        'effective_date',
        'reason',
        'status',
        'notes',
        'approved_by_user_id',
    ];

     protected $casts = [
        'submission_date' => 'date',
        'effective_date' => 'date',
    ];

     public function user()
    {
        return $this->belongsTo(User::class)->withTrashed(); // Menggunakan withTrashed untuk mengizinkan akses ke user yang sudah dihapus
    }
    
     public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id'); // Menggunakan nama kolom foreign key yang spesifik
    }
}
