<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDetail extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
        'role_id',
        'telegram_id',
        'name',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
