<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';

    protected $guarded = [];

      public function division()
    {
        return $this->belongsTo(Division::class);
    }
}
