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
        'entry_status',
        'entry_status_updated_by',
        'entry_status_updated_at',
    ];

    protected $casts = [
        'order_ids' => 'array',
        'order_details' => 'array',
        'performed_at' => 'datetime',
        'dispatch_date' => 'date',
        'entry_status_updated_at' => 'datetime',
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
     * 關聯：登打狀態最後更新者
     */
    public function entryStatusUpdater()
    {
        return $this->belongsTo(User::class, 'entry_status_updated_by');
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

    /**
     * Accessor: 取得登打狀態中文名稱
     */
    public function getEntryStatusLabelAttribute()
    {
        return match ($this->entry_status) {
            'pending' => '未處理',
            'processing' => '處理中',
            'completed' => '處理完畢',
            default => '未知',
        };
    }

    /**
     * Accessor: 取得登打狀態 Badge 樣式
     */
    public function getEntryStatusBadgeClassAttribute()
    {
        return match ($this->entry_status) {
            'pending' => 'badge-secondary',
            'processing' => 'badge-info',
            'completed' => 'badge-success',
            default => 'badge-secondary',
        };
    }
}
