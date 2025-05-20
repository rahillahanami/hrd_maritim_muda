<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PegawaiModel extends Model
{
    use HasFactory;
    
    protected $table = 'pegawai';

    protected $guarded = [];

    public function divisi() 
    {

    return $this->belongsTo(DivisiModel::class, 'divisi_id');
    
    }
}
