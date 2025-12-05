<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TimeAnalysisService
{
    /**
     * 尖峰時段分析（0-23 小時）
     */
    public function getPeakHoursAnalysis(Request $request)
    {
        $cacheKey = 'statistics:peak_hours:'.md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request) {
            $query = Order::selectRaw('
                    HOUR(ride_time) as hour,
                    COUNT(*) as order_count,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true);

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            $hourlyStats = $query->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy('hour');

            // 填補缺失的小時（0-23）
            $completeHourlyData = collect(range(0, 23))->map(function ($hour) use ($hourlyStats) {
                $stat = $hourlyStats->get($hour);

                return [
                    'hour' => str_pad($hour, 2, '0', STR_PAD_LEFT).':00',
                    'hour_value' => $hour,
                    'order_count' => $stat ? $stat->order_count : 0,
                    'unique_customers' => $stat ? $stat->unique_customers : 0,
                ];
            });

            // 找出尖峰時段（訂單數最多的前3個小時）
            $peakHours = $completeHourlyData->sortByDesc('order_count')
                ->take(3)
                ->pluck('hour')
                ->toArray();

            return [
                'hourly_data' => $completeHourlyData->values(),
                'peak_hours' => $peakHours,
            ];
        });
    }

    /**
     * 週間分布（週一到週日）
     */
    public function getWeekdayDistribution(Request $request)
    {
        $cacheKey = 'statistics:weekday:'.md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request) {
            $query = Order::selectRaw('
                    DAYOFWEEK(ride_date) as weekday,
                    COUNT(*) as order_count,
                    COUNT(DISTINCT customer_id) as unique_customers
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true);

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            $weekdayStats = $query->groupBy('weekday')
                ->orderBy('weekday')
                ->get()
                ->keyBy('weekday');

            // MySQL DAYOFWEEK: 1=週日, 2=週一, ..., 7=週六
            $weekdayNames = ['週日', '週一', '週二', '週三', '週四', '週五', '週六'];

            // 轉換為週一開始的順序
            $weekdayData = collect([2, 3, 4, 5, 6, 7, 1])->map(function ($dayNumber) use ($weekdayStats, $weekdayNames) {
                $stat = $weekdayStats->get($dayNumber);

                return [
                    'weekday' => $weekdayNames[$dayNumber - 1],
                    'weekday_value' => $dayNumber,
                    'order_count' => $stat ? $stat->order_count : 0,
                    'unique_customers' => $stat ? $stat->unique_customers : 0,
                ];
            });

            // 找出訂單數最多的工作日
            $busiestDay = $weekdayData->sortByDesc('order_count')->first();

            return [
                'weekday_data' => $weekdayData->values(),
                'busiest_day' => $busiestDay,
            ];
        });
    }

    /**
     * 月份趨勢（過去 12 個月）
     */
    public function getMonthlyTrends(Request $request, int $months = 12)
    {
        $cacheKey = 'statistics:monthly_trends:'.md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);

            $query = Order::selectRaw('
                    DATE_FORMAT(ride_date, "%Y-%m") as month,
                    COUNT(*) as order_count,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    COUNT(CASE WHEN status = "assigned" THEN 1 END) as assigned_count,
                    COUNT(CASE WHEN status = "open" THEN 1 END) as open_count
                ')
                ->whereBetween('ride_date', [$startDate, $endDate])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true);

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            $monthlyStats = $query->groupBy('month')
                ->orderBy('month')
                ->get()
                ->keyBy('month');

            // 填補缺失的月份
            $completeMonthlyData = collect();
            $currentMonth = $startDate->copy()->startOfMonth();

            while ($currentMonth->lte($endDate)) {
                $monthKey = $currentMonth->format('Y-m');
                $stat = $monthlyStats->get($monthKey);

                $completeMonthlyData->push([
                    'month' => $monthKey,
                    'month_name' => $currentMonth->format('Y年m月'),
                    'order_count' => $stat ? $stat->order_count : 0,
                    'unique_customers' => $stat ? $stat->unique_customers : 0,
                    'assigned_count' => $stat ? $stat->assigned_count : 0,
                    'open_count' => $stat ? $stat->open_count : 0,
                ]);

                $currentMonth->addMonth();
            }

            // 計算成長率
            $growthRate = $this->calculateGrowthRate($completeMonthlyData);

            return [
                'monthly_data' => $completeMonthlyData->values(),
                'growth_rate' => $growthRate,
            ];
        });
    }

    /**
     * 提前預約分析
     */
    public function getAdvanceBookingAnalysis(Request $request)
    {
        $cacheKey = 'statistics:advance_booking:'.md5(json_encode($request->only(['start_date', 'end_date', 'order_type'])));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request) {
            $query = Order::selectRaw('
                    DATEDIFF(ride_date, DATE(created_at)) as advance_days,
                    COUNT(*) as order_count
                ')
                ->whereBetween('ride_date', [$request->start_date, $request->end_date])
                ->whereIn('status', ['assigned', 'open', 'bkorder'])
                ->where('is_main_order', true);

            // 篩選訂單來源
            if ($request->filled('order_type')) {
                $query->where('order_type', $request->order_type);
            }

            $advanceBookingStats = $query->having('advance_days', '>=', 0)
                ->groupBy('advance_days')
                ->orderBy('advance_days')
                ->get();

            // 計算平均提前天數
            $totalOrders = $advanceBookingStats->sum('order_count');
            $totalDays = $advanceBookingStats->sum(function ($item) {
                return $item->advance_days * $item->order_count;
            });

            $avgAdvanceDays = $totalOrders > 0 ? round($totalDays / $totalOrders, 1) : 0;

            // 分類統計
            $sameDay = $advanceBookingStats->where('advance_days', 0)->sum('order_count');
            $within3Days = $advanceBookingStats->whereBetween('advance_days', [1, 3])->sum('order_count');
            $within7Days = $advanceBookingStats->whereBetween('advance_days', [4, 7])->sum('order_count');
            $moreThan7Days = $advanceBookingStats->where('advance_days', '>', 7)->sum('order_count');

            return [
                'advance_booking_data' => $advanceBookingStats->take(30)->values(), // 只顯示前30天的分布
                'avg_advance_days' => $avgAdvanceDays,
                'categories' => [
                    'same_day' => $sameDay,
                    'within_3_days' => $within3Days,
                    'within_7_days' => $within7Days,
                    'more_than_7_days' => $moreThan7Days,
                ],
                'percentages' => [
                    'same_day_pct' => $totalOrders > 0 ? round(($sameDay / $totalOrders) * 100, 2) : 0,
                    'within_3_days_pct' => $totalOrders > 0 ? round(($within3Days / $totalOrders) * 100, 2) : 0,
                    'within_7_days_pct' => $totalOrders > 0 ? round(($within7Days / $totalOrders) * 100, 2) : 0,
                    'more_than_7_days_pct' => $totalOrders > 0 ? round(($moreThan7Days / $totalOrders) * 100, 2) : 0,
                ],
            ];
        });
    }

    /**
     * 計算月成長率
     */
    private function calculateGrowthRate($monthlyData)
    {
        if ($monthlyData->count() < 2) {
            return 0;
        }

        $lastMonth = $monthlyData->last()['order_count'];
        $previousMonth = $monthlyData->slice(-2, 1)->first()['order_count'];

        if ($previousMonth == 0) {
            return $lastMonth > 0 ? 100 : 0;
        }

        return round((($lastMonth - $previousMonth) / $previousMonth) * 100, 2);
    }
}
