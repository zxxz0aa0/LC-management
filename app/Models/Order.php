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
        'driver_name', 'driver_plate_number', 'driver_fleet_number',

        // 其他欄位
        'order_type', 'service_company',
        'ride_date', 'ride_time',
        'pickup_county', 'pickup_district', 'pickup_address',
        'pickup_lat', 'pickup_lng',
        'dropoff_county', 'dropoff_district', 'dropoff_address',
        'dropoff_lat', 'dropoff_lng',
        'wheelchair', 'stair_machine', 'companions', 'carpool_customer_id', 'carpool_name', 'carpool_id',
        'remark', 'created_by', 'identity', 'carpool_with',
        'status', 'special_status',
        'carpool_id_number',
    ];

    // 資料類型轉換
    protected $casts = [
        'ride_date' => 'date',
        'wheelchair' => 'boolean',
        'stair_machine' => 'boolean',
        'companions' => 'integer',
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
        'dropoff_lat' => 'decimal:8',
        'dropoff_lng' => 'decimal:8',
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

    /**
     * 根據請求參數篩選訂單。
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $request)
    {
        // 關鍵字篩選
        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_name', 'like', '%'.$request->keyword.'%')
                    ->orWhere('customer_id_number', 'like', '%'.$request->keyword.'%')
                    ->orWhere('customer_phone', 'like', '%'.$request->keyword.'%');
            });
        }

        // 日期區間篩選
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('ride_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('ride_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('ride_date', '<=', $request->end_date);
        }

        return $query;
    }
}
