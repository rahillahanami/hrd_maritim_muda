<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceModel extends Model
{

     use HasFactory;
    
    protected $table = 'attendances';

    protected $guarded = [];

    protected $casts = [
    'check_in' => 'datetime',
    'check_out' => 'datetime',
];

    protected $fillable = [
        'pegawai_id', 'date', 'check_in', 'check_out', 'status'
    ];

    public function pegawai()
    {
        return $this->belongsTo(PegawaiModel::class , 'pegawai_id');
    }
}
