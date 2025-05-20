<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequestModel extends Model
{

    use HasFactory;
    
    protected $table = 'leave_requests';

    protected $guarded = [];

    
    protected $fillable = [
        'pegawai_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by',
        'rejection_reason',
    ];

    public function pegawai()
    {
        return $this->belongsTo(PegawaiModel::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
