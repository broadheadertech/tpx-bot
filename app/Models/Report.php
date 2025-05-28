<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [

        'barber_id',
        'service_id',
        'customer_no',
        'name',
        'booking_type',
        'time',
        'date',
        'amount',
        'mop',
        'status'
    ];

    public function barber()
    {
        return $this->belongsTo(Barber::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
