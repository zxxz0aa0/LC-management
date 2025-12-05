<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerServiceAnalysisService
{
    /**
     * 快取時間（分鐘）
     */
    const CACHE_MINUTES = 30;

    /**
     * 圖表顯示限制
     */
    const CHART_DISPLAY_LIMIT = 15;

    /**
     * 取得每位人員建單總數量
     */
    public function getOrderCountByUser(Request $request): array
    {
        $cacheKey = 'statistics:customer_service:order_count_by_user:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type', 'created_by', 'status'])));

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($request) {
            $query = Order::selectRaw('
                    created_by,
                    COUNT(*) as total_orders,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->whereBetween('created_at', [$request->start_date, $request->end_date])
                ->whereNotNull('created_by')
                ->groupBy('created_by')
                ->orderByDesc('total_orders');

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            // 篩選建單人員
            if ($request->filled('created_by')) {
                $query->where('created_by', $request->created_by);
            }

            // 篩選訂單狀態
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $data = $query->get();

            return $data->map(function ($item, $index) {
                return [
                    'rank' => $index + 1,
                    'user_name' => $item->created_by,
                    'total_orders' => $item->total_orders,
                    'unique_customers' => $item->unique_customers,
                ];
            })->toArray();
        });
    }

    /**
     * 取得每位人員的當天/預約訂單數量
     */
    public function getOrderTypesByUser(Request $request): array
    {
        $cacheKey = 'statistics:customer_service:order_types_by_user:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type', 'created_by', 'status'])));

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($request) {
            $query = Order::selectRaw('
                    created_by,
                    SUM(CASE WHEN DATE(ride_date) = DATE(created_at) THEN 1 ELSE 0 END) as same_day_orders,
                    SUM(CASE WHEN DATE(ride_date) > DATE(created_at) THEN 1 ELSE 0 END) as advance_orders
                ')
                ->whereBetween('created_at', [$request->start_date, $request->end_date])
                ->whereNotNull('created_by')
                ->groupBy('created_by')
                ->orderByDesc(DB::raw('
                    SUM(CASE WHEN DATE(ride_date) = DATE(created_at) THEN 1 ELSE 0 END) +
                    SUM(CASE WHEN DATE(ride_date) > DATE(created_at) THEN 1 ELSE 0 END)
                '));

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            // 篩選建單人員
            if ($request->filled('created_by')) {
                $query->where('created_by', $request->created_by);
            }

            // 篩選訂單狀態
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $data = $query->get();

            return [
                'users_data' => $data->map(function ($item) {
                    return [
                        'user_name' => $item->created_by,
                        'same_day_orders' => $item->same_day_orders,
                        'advance_orders' => $item->advance_orders,
                    ];
                })->toArray(),
            ];
        });
    }

    /**
     * 取得每小時建單數量
     */
    public function getOrdersByHour(Request $request): array
    {
        $cacheKey = 'statistics:customer_service:orders_by_hour:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type', 'created_by', 'status'])));

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($request) {
            $query = Order::selectRaw('
                    HOUR(created_at) as hour,
                    COUNT(*) as order_count
                ')
                ->whereBetween('created_at', [$request->start_date, $request->end_date])
                ->whereNotNull('created_by')
                ->groupBy('hour')
                ->orderBy('hour');

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            // 篩選建單人員
            if ($request->filled('created_by')) {
                $query->where('created_by', $request->created_by);
            }

            // 篩選訂單狀態
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $hourlyStats = $query->get()->keyBy('hour');

            // 填補缺失的小時（0-23）
            $completeHourlyData = collect(range(0, 23))->map(function ($hour) use ($hourlyStats) {
                $stat = $hourlyStats->get($hour);

                return [
                    'hour' => $hour,
                    'order_count' => $stat ? $stat->order_count : 0,
                ];
            });

            return [
                'hourly_data' => $completeHourlyData->toArray(),
            ];
        });
    }

    /**
     * 取得當天/預約訂單總數量
     */
    public function getOrderTypeSummary(Request $request): array
    {
        $cacheKey = 'statistics:customer_service:order_type_summary:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type', 'created_by', 'status'])));

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($request) {
            // 當天訂單查詢
            $sameDayQuery = Order::whereBetween('created_at', [$request->start_date, $request->end_date])
                ->whereRaw('DATE(ride_date) = DATE(created_at)')
                ->whereNotNull('created_by');

            if ($request->filled('order_type')) {
                $sameDayQuery->where('order_type', $request->order_type);
            }

            if ($request->filled('created_by')) {
                $sameDayQuery->where('created_by', $request->created_by);
            }

            if ($request->filled('status')) {
                $sameDayQuery->where('status', $request->status);
            }

            $sameDayCount = $sameDayQuery->count();

            // 預約訂單查詢
            $advanceQuery = Order::whereBetween('created_at', [$request->start_date, $request->end_date])
                ->whereRaw('DATE(ride_date) > DATE(created_at)')
                ->whereNotNull('created_by');

            if ($request->filled('order_type')) {
                $advanceQuery->where('order_type', $request->order_type);
            }

            if ($request->filled('created_by')) {
                $advanceQuery->where('created_by', $request->created_by);
            }

            if ($request->filled('status')) {
                $advanceQuery->where('status', $request->status);
            }

            $advanceCount = $advanceQuery->count();

            $totalCount = $sameDayCount + $advanceCount;

            return [
                'same_day_count' => $sameDayCount,
                'advance_count' => $advanceCount,
                'same_day_percentage' => $totalCount > 0 ? round(($sameDayCount / $totalCount) * 100, 1) : 0,
                'advance_percentage' => $totalCount > 0 ? round(($advanceCount / $totalCount) * 100, 1) : 0,
                'total_count' => $totalCount,
            ];
        });
    }

    /**
     * 取得訂單狀態分布
     */
    public function getOrderStatusDistribution(Request $request): array
    {
        $cacheKey = 'statistics:customer_service:status_distribution:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type', 'created_by', 'status'])));

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($request) {
            $query = Order::selectRaw('
                    status,
                    COUNT(*) as order_count
                ')
                ->whereBetween('created_at', [$request->start_date, $request->end_date])
                ->whereNotNull('created_by')
                ->groupBy('status')
                ->orderByDesc('order_count');

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            // 篩選建單人員
            if ($request->filled('created_by')) {
                $query->where('created_by', $request->created_by);
            }

            // 篩選訂單狀態
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $data = $query->get();
            $totalCount = $data->sum('order_count');

            return [
                'status_data' => $data->map(function ($item) use ($totalCount) {
                    return [
                        'status' => $item->status,
                        'order_count' => $item->order_count,
                        'percentage' => $totalCount > 0 ? round(($item->order_count / $totalCount) * 100, 1) : 0,
                    ];
                })->toArray(),
                'total_count' => $totalCount,
            ];
        });
    }
}
