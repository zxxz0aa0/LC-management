<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'id_number',
        'fleet_number',
        'plate_number',
        'car_color',
        'car_brand',
        'car_vehicle_style',
        'lc_company',
        'order_type',
        'service_type',
        'status',
    ];
}
