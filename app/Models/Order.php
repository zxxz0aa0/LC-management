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
        'status', 'special_status', 'special_order',
        'carpool_id_number', 'match_time',

        // 共乘群組相關欄位
        'carpool_group_id',
        'is_main_order',
        'carpool_member_count',
        'main_order_number',
        'member_sequence',
        'is_group_dissolved',
        'dissolved_at',
        'dissolved_by',
        'original_group_id',
    ];

    // 資料類型轉換
    protected $casts = [
        'ride_date' => 'date',
        'companions' => 'integer',
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
        'dropoff_lat' => 'decimal:8',
        'dropoff_lng' => 'decimal:8',
        'match_time' => 'datetime',

        // 共乘群組相關欄位
        'is_main_order' => 'boolean',
        'is_group_dissolved' => 'boolean',
        'dissolved_at' => 'datetime',
        'carpool_member_count' => 'integer',
        'member_sequence' => 'integer',
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
        // 檢查是否為搜尋模式
        $isSearching = $request->filled('keyword') ||
                       $request->filled('customer_id') ||
                       $request->filled('order_number') ||
                       $request->filled('start_date') ||
                       $request->filled('end_date') ||
                       $request->filled('order_type') ||
                       $request->filled('stair_machine');

        // 關鍵字篩選
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($q) use ($keyword) {
                // 直接搜尋
                $q->where('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('customer_id_number', 'like', "%{$keyword}%")
                    ->orWhere('customer_phone', 'like', "%{$keyword}%")
                    ->orWhere('order_number', 'like', "%{$keyword}%");

                // 搜尋群組相關訂單
                $q->orWhereHas('groupMembers', function ($subQ) use ($keyword) {
                    $subQ->where('customer_name', 'like', "%{$keyword}%")
                        ->orWhere('customer_id_number', 'like', "%{$keyword}%");
                });

                // 反向搜尋：如果搜到成員，也顯示主訂單
                $q->orWhereHas('mainOrder', function ($subQ) use ($keyword) {
                    $subQ->where('customer_name', 'like', "%{$keyword}%")
                        ->orWhere('customer_id_number', 'like', "%{$keyword}%");
                });
            });
        }

        // 訂單來源篩選
        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        // 爬梯機篩選
        if ($request->filled('stair_machine')) {
            $query->where('stair_machine', $request->stair_machine);
        }

        // 如果不是搜尋模式，只顯示主訂單
        if (! $isSearching) {
            $query->where('is_main_order', true);
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

    // ============ 共乘群組相關關聯 ============

    /**
     * 群組成員訂單關聯
     */
    public function groupMembers()
    {
        return $this->hasMany(Order::class, 'carpool_group_id', 'carpool_group_id')
            ->where('id', '!=', $this->id)
            ->where('is_group_dissolved', false);
    }

    /**
     * 群組主訂單關聯
     */
    public function mainOrder()
    {
        return $this->belongsTo(Order::class, 'carpool_group_id', 'carpool_group_id')
            ->where('is_main_order', true);
    }

    /**
     * 所有群組訂單（包含自己）
     */
    public function allGroupOrders()
    {
        return $this->hasMany(Order::class, 'carpool_group_id', 'carpool_group_id')
            ->where('is_group_dissolved', false);
    }

    // ============ 共乘群組相關 Scope ============

    /**
     * Scope: 僅主訂單
     */
    public function scopeMainOrders($query)
    {
        return $query->where('is_main_order', true);
    }

    /**
     * Scope: 群組訂單
     */
    public function scopeGroupOrders($query)
    {
        return $query->whereNotNull('carpool_group_id')
            ->where('is_group_dissolved', false);
    }

    /**
     * Scope: 已解散的群組
     */
    public function scopeDissolvedGroups($query)
    {
        return $query->where('is_group_dissolved', true);
    }

    // ============ 共乘群組相關方法 ============

    /**
     * 檢查是否為群組訂單
     */
    public function isGroupOrder()
    {
        return ! empty($this->carpool_group_id) && ! $this->is_group_dissolved;
    }

    /**
     * 檢查是否為主訂單
     */
    public function isMainOrder()
    {
        return $this->is_main_order && $this->isGroupOrder();
    }

    /**
     * 取得群組資訊
     */
    public function getGroupInfo()
    {
        if (! $this->isGroupOrder()) {
            return null;
        }

        $allOrders = $this->allGroupOrders;
        $mainOrder = $allOrders->where('is_main_order', true)->first();
        $members = $allOrders->where('is_main_order', false);

        return [
            'group_id' => $this->carpool_group_id,
            'member_count' => $this->carpool_member_count,
            'main_order' => $mainOrder,
            'members' => $members,
            'all_orders' => $allOrders,
        ];
    }

    /**
     * 取得群組成員名稱列表
     */
    public function getGroupMemberNames()
    {
        if (! $this->isGroupOrder()) {
            return [];
        }

        return $this->groupMembers->pluck('customer_name')->toArray();
    }
}
