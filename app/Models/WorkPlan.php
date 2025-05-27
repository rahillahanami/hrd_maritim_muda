<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkPlan extends Model
{
    use HasFactory;

    protected $table = 'work_plans';

     protected $fillable = [
        'title',
        'description',
        'target_metric',
        'target_value',
        'start_date',
        'due_date',
        'progress_percentage',
        'status',
        'user_id',
        'division_id',
        'notes',
        'approved_by_user_id',
    ];

     protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'progress_percentage' => 'integer',
        'target_value' => 'decimal:2', // Pastikan presisi decimal sesuai migrasi (8,2)
    ];

      public function user()
    {
        return $this->belongsTo(User::class);
    }

       public function division()
    {
        return $this->belongsTo(Division::class);
    }

      public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id'); // Menggunakan nama kolom foreign key yang spesifik
    }

}
