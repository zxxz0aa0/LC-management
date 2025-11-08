<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'dispatch_name',
        'driver_id',
        'driver_name',
        'driver_fleet_number',
        'order_ids',
        'order_count',
        'order_details',
        'dispatch_date',
        'performed_by',
        'performed_at',
        'notes',
    ];

    protected $casts = [
        'order_ids' => 'array',
        'order_details' => 'array',
        'performed_at' => 'datetime',
        'dispatch_date' => 'date',
    ];

    /**
     * 關聯：司機
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * 關聯：執行人
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * 取得該排趟的所有訂單
     */
    public function orders()
    {
        return Order::whereIn('id', $this->order_ids ?? [])->get();
    }

    /**
     * Scope: 最近 N 個月的記錄
     */
    public function scopeRecent($query, $months = 2)
    {
        return $query->where('performed_at', '>=', now()->subMonths($months));
    }

    /**
     * Scope: 依司機篩選
     */
    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Scope: 依日期範圍篩選
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->whereDate('dispatch_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('dispatch_date', '<=', $endDate);
        }

        return $query;
    }
}
