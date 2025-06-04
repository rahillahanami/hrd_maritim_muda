<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'division_id',
        'status',
        'file_path',
        // 'type', // <<< HAPUS BARIS INI
        'user_id',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];


    // Relasi division() (tetap ada)
    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    // *** Tambahkan relasi user() ini ***
    /**
     * Get the user who uploaded the document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}