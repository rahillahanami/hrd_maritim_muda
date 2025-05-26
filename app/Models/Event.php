<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];
   
    // protected $fillable = [
    //     'name', // Pastikan 'name' ada di sini jika itu masalah sebelumnya
    //     'description',
    //     'starts_at', // Sesuaikan dengan nama kolom datetime di tabel events Anda (misal 'start_date', 'start_at')
    //     'ends_at',   // Sesuaikan
    //     'division_id', // Kolom baru untuk divisi
    // ];


    public function division()
    {
        return $this->belongsTo(Division::class);
    }

}
