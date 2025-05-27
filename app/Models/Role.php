<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    //
    use SoftDeletes;
    protected $fillable = [
        'name'
    ];

    public function userDetails()
    {
        return $this->hasMany(UserDetail::class);
    }
}
