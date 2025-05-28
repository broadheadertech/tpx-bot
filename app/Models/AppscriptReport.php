<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppscriptReport extends Model
{
    //
    protected $fillable = [
        'barber',
        'service',
        'customer_no',
        'name',
        'booking_type',
        'time',
        'date',
        'amount',
        'mop',
        'status'
    ];
}
