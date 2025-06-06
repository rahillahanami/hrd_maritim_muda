<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin \Spatie\Permission\Traits\HasRoles //
 */

class User extends Authenticatable 
{
    use HasFactory, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

      protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime', // <<< PASTIKAN INI ADA
        ];
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
}
