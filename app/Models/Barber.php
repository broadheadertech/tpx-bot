<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barber extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
