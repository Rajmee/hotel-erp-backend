<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Model;

class CustomerBooking extends Model
{

    protected $table = 'customer_bookings';

    protected $guarded = [
        'id',
    ];


}
