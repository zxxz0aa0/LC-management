<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GeographyAnalysisService
{
    /**
     * 圖表顯示限制
     */
    const CHART_DISPLAY_LIMIT = 15;

    /**
     * 取得要使用的狀態陣列（預設 assigned/open/bkorder）
     */
    private function resolveStatuses(Request $request): array
    {
        $default = ['assigned', 'open', 'bkorder'];
        $statuses = $request->input('status', []);

        if (is_string($statuses)) {
            $statuses = [$statuses];
        }

        $statuses = array_values(array_unique(array_filter($statuses)));

        return count($statuses) ? $statuses : $default;
    }

    /**
     * 熱門上車地點
     */
    public function getPopularPickupLocations(Request $request, ?int $limit = null)
    {
        $statuses = $this->resolveStatuses($request);
        $limitKey = $limit ? "limit_{$limit}" : 'all';
        $cacheKey = 'statistics:pickup_locations:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])).json_encode($statuses)).
            ":{$limitKey}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $limit, $statuses) {
            $query = Order::selectRaw('
                    pickup_county,
                    pickup_district,
                    COUNT(*) as order_count,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', $statuses)
                ->where('is_main_order', true)
                ->groupBy('pickup_county', 'pickup_district')
                ->orderByDesc('order_count');

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
     * 熱門下車地點
     */
    public function getPopularDropoffLocations(Request $request, ?int $limit = null)
    {
        $statuses = $this->resolveStatuses($request);
        $limitKey = $limit ? "limit_{$limit}" : 'all';
        $cacheKey = 'statistics:dropoff_locations:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])).json_encode($statuses)).
            ":{$limitKey}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $limit, $statuses) {
            $query = Order::selectRaw('
                    dropoff_county,
                    dropoff_district,
                    COUNT(*) as order_count,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', $statuses)
                ->where('is_main_order', true)
                ->groupBy('dropoff_county', 'dropoff_district')
                ->orderByDesc('order_count');

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
     * 跨縣市訂單統計
     */
    public function getCrossCountyOrders(Request $request)
    {
        $statuses = $this->resolveStatuses($request);
        $cacheKey = 'statistics:cross_county:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])).json_encode($statuses));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $statuses) {
            $totalOrdersQuery = Order::whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', $statuses)
                ->where('is_main_order', true);

            if ($request->filled('order_type')) {
                $totalOrdersQuery->where('order_type', $request->order_type);
            }

            $totalOrders = $totalOrdersQuery->count();

            $crossCountyOrdersQuery = Order::whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', $statuses)
                ->where('is_main_order', true)
                ->whereColumn('pickup_county', '!=', 'dropoff_county');

            if ($request->filled('order_type')) {
                $crossCountyOrdersQuery->where('order_type', $request->order_type);
            }

            $crossCountyOrders = $crossCountyOrdersQuery->count();
            $sameCountyOrders = $totalOrders - $crossCountyOrders;

            $topCrossCountyRoutesQuery = Order::selectRaw('
                    pickup_county,
                    dropoff_county,
                    COUNT(*) as order_count
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', $statuses)
                ->where('is_main_order', true)
                ->whereColumn('pickup_county', '!=', 'dropoff_county');

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
                        'route' => $item->pickup_county.' 至'.$item->dropoff_county,
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
     * 熱門路線
     */
    public function getPopularRoutes(Request $request, ?int $limit = null)
    {
        $statuses = $this->resolveStatuses($request);
        $limitKey = $limit ? "limit_{$limit}" : 'all';
        $cacheKey = 'statistics:popular_routes:'.
            md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])).json_encode($statuses)).
            ":{$limitKey}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $limit, $statuses) {
            $query = Order::selectRaw('
                    pickup_county,
                    pickup_district,
                    dropoff_county,
                    dropoff_district,
                    COUNT(*) as order_count
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', $statuses)
                ->where('is_main_order', true)
                ->groupBy('pickup_county', 'pickup_district', 'dropoff_county', 'dropoff_district')
                ->orderByDesc('order_count');

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
                    'route' => $pickupArea.' 至'.$dropoffArea,
                    'order_count' => $item->order_count,
                ];
            });
        });
    }
}
