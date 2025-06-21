<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // 可填寫的欄位（要與資料表欄位對應）
    protected $fillable = [
        'order_number', 'customer_id', 'driver_id',

        // 快照欄位
        'customer_name', 'customer_id_number', 'customer_phone',
        'driver_name', 'driver_plate_number',

        // 其他欄位
        'order_type', 'service_company',
        'ride_date', 'ride_time',
        'pickup_county', 'pickup_district', 'pickup_address',
        'pickup_lat', 'pickup_lng',
        'dropoff_county', 'dropoff_district', 'dropoff_address',
        'dropoff_lat', 'dropoff_lng',
        'wheelchair', 'stair_machine', 'companions',
        'remark', 'created_by', 'identity', 'carpool_with',
        'special_order', 'status',
    ];

    // 關聯：每筆訂單屬於一位客戶
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // 關聯：每筆訂單可以指派一位駕駛
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
