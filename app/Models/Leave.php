<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by_user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // *** Definisi Relasi ***

     /**
     * Get the user who submitted the leave application.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed(); // Menggunakan withTrashed untuk mengizinkan akses ke user yang sudah dihapus
    }
    
    /**
     * Get the user who approved/rejected the leave application.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}