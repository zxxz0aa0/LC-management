<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GeographyAnalysisService
{
    /**
     * 圖表顯示的資料筆數限制
     */
    const CHART_DISPLAY_LIMIT = 15;

    /**
     * 取得上車區域統計
     *
     * @param  int|null  $limit  限制返回筆數，null 表示返回全部（用於匯出）
     * @return \Illuminate\Support\Collection
     */
    public function getPopularPickupLocations(Request $request, ?int $limit = null)
    {
        $limitKey = $limit ? "limit_{$limit}" : 'all';
        $cacheKey = 'statistics:pickup_locations:'.md5(json_encode($request->only(['start_date', 'end_date', 'order_type']))).":{$limitKey}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $limit) {
            $query = Order::selectRaw('
                    pickup_county,
                    pickup_district,
                    COUNT(*) as order_count,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder']) // 排除已取消
                ->where('is_main_order', true) // 避免共乘重複計算
                ->groupBy('pickup_county', 'pickup_district')
                ->orderByDesc('order_count');

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            if ($limit !== null) {
                $query->limit($limit);
            }

            $data = $query->get();

            return $data->map(function ($item, $index) {
                return [
                    'rank' => $index + 1,
                    'area' => $item->pickup_county.$item->pickup_district,
                    'order_count' => $item->order_count,
                    'unique_customers' => $item->unique_customers,
                ];
            });
        });
    }

    /**
     * 取得下車區域統計
     *
     * @param  int|null  $limit  限制返回筆數，null 表示返回全部（用於匯出）
     * @return \Illuminate\Support\Collection
     */
    public function getPopularDropoffLocations(Request $request, ?int $limit = null)
    {
        $limitKey = $limit ? "limit_{$limit}" : 'all';
        $cacheKey = 'statistics:dropoff_locations:'.md5(json_encode($request->only(['start_date', 'end_date', 'order_type']))).":{$limitKey}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $limit) {
            $query = Order::selectRaw('
                    dropoff_county,
                    dropoff_district,
                    COUNT(*) as order_count,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true)
                ->groupBy('dropoff_county', 'dropoff_district')
                ->orderByDesc('order_count');

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            if ($limit !== null) {
                $query->limit($limit);
            }

            $data = $query->get();

            return $data->map(function ($item, $index) {
                return [
                    'rank' => $index + 1,
                    'area' => $item->dropoff_county.$item->dropoff_district,
                    'order_count' => $item->order_count,
                    'unique_customers' => $item->unique_customers,
                ];
            });
        });
    }

    /**
     * 取得跨縣市訂單統計
     */
    public function getCrossCountyOrders(Request $request)
    {
        $cacheKey = 'statistics:cross_county:'.md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request) {
            $totalOrdersQuery = Order::whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true);

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $totalOrdersQuery->where('order_type', $request->order_type);
            }

            $totalOrders = $totalOrdersQuery->count();

            $crossCountyOrdersQuery = Order::whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true)
                ->whereColumn('pickup_county', '!=', 'dropoff_county');

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $crossCountyOrdersQuery->where('order_type', $request->order_type);
            }

            $crossCountyOrders = $crossCountyOrdersQuery->count();

            $sameCountyOrders = $totalOrders - $crossCountyOrders;

            // 取得前 5 名跨縣市路線
            $topCrossCountyRoutesQuery = Order::selectRaw('
                    pickup_county,
                    dropoff_county,
                    COUNT(*) as order_count
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true)
                ->whereColumn('pickup_county', '!=', 'dropoff_county');

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $topCrossCountyRoutesQuery->where('order_type', $request->order_type);
            }

            $topCrossCountyRoutes = $topCrossCountyRoutesQuery
                ->groupBy('pickup_county', 'dropoff_county')
                ->orderByDesc('order_count')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'route' => $item->pickup_county.' → '.$item->dropoff_county,
                        'order_count' => $item->order_count,
                    ];
                });

            return [
                'total_orders' => $totalOrders,
                'cross_county_orders' => $crossCountyOrders,
                'same_county_orders' => $sameCountyOrders,
                'cross_county_percentage' => $totalOrders > 0 ? round(($crossCountyOrders / $totalOrders) * 100, 2) : 0,
                'same_county_percentage' => $totalOrders > 0 ? round(($sameCountyOrders / $totalOrders) * 100, 2) : 0,
                'top_cross_county_routes' => $topCrossCountyRoutes,
            ];
        });
    }

    /**
     * 取得區域路線統計
     *
     * @param  int|null  $limit  限制返回筆數，null 表示返回全部（用於匯出）
     * @return \Illuminate\Support\Collection
     */
    public function getPopularRoutes(Request $request, ?int $limit = null)
    {
        $limitKey = $limit ? "limit_{$limit}" : 'all';
        $cacheKey = 'statistics:popular_routes:'.md5(json_encode($request->only(['start_date', 'end_date', 'order_type']))).":{$limitKey}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $limit) {
            $query = Order::selectRaw('
                    pickup_county,
                    pickup_district,
                    dropoff_county,
                    dropoff_district,
                    COUNT(*) as order_count
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true)
                ->groupBy('pickup_county', 'pickup_district',
                    'dropoff_county', 'dropoff_district')
                ->orderByDesc('order_count');

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            if ($limit !== null) {
                $query->limit($limit);
            }

            $data = $query->get();

            return $data->map(function ($item, $index) {
                $pickupArea = $item->pickup_county.$item->pickup_district;
                $dropoffArea = $item->dropoff_county.$item->dropoff_district;

                return [
                    'rank' => $index + 1,
                    'route' => $pickupArea.' → '.$dropoffArea,
                    'order_count' => $item->order_count,
                ];
            });
        });
    }
}
