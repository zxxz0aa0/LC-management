<?php

namespace App\Http\Controllers;

use App\Exports\CustomerServiceStatisticsExport;
use App\Exports\GeographyStatisticsExport;
use App\Exports\TimeStatisticsExport;
use App\Models\Order;
use App\Services\CustomerServiceAnalysisService;
use App\Services\GeographyAnalysisService;
use App\Services\TimeAnalysisService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StatisticsController extends Controller
{
    protected $geographyService;

    protected $timeService;

    protected $customerServiceService;

    public function __construct(
        GeographyAnalysisService $geographyService,
        TimeAnalysisService $timeService,
        CustomerServiceAnalysisService $customerServiceService
    ) {
        $this->geographyService = $geographyService;
        $this->timeService = $timeService;
        $this->customerServiceService = $customerServiceService;
    }

    /**
     * 地理統計頁面
     */
    public function geographyIndex()
    {
        return view('statistics.geography');
    }

    /**
     * 時間統計頁面
     */
    public function timeAnalysisIndex()
    {
        return view('statistics.time-analysis');
    }

    /**
     * API: 取得地理統計數據
     */
    public function getGeographyData(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'order_type' => 'nullable|string|in:'.implode(',', Order::ORDER_TYPES),
            'status' => 'nullable|array',
            'status.*' => 'in:assigned,open,bkorder',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        if ($startDate->diffInDays($endDate) > 365) {
            return response()->json([
                'error' => '查詢範圍不可超過一年',
            ], 422);
        }

        try {
            $limit = GeographyAnalysisService::CHART_DISPLAY_LIMIT;

            $data = [
                'pickup_locations' => $this->geographyService->getPopularPickupLocations($request, $limit),
                'dropoff_locations' => $this->geographyService->getPopularDropoffLocations($request, $limit),
                'cross_county' => $this->geographyService->getCrossCountyOrders($request),
                'popular_routes' => $this->geographyService->getPopularRoutes($request, $limit),
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '查詢統計數據時發生錯誤',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 取得時間統計數據
     */
    public function getTimeAnalysisData(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'order_type' => 'nullable|string|in:'.implode(',', Order::ORDER_TYPES),
            'status' => 'nullable|array',
            'status.*' => 'in:assigned,open,bkorder',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        if ($startDate->diffInDays($endDate) > 365) {
            return response()->json([
                'error' => '查詢範圍不可超過一年',
            ], 422);
        }

        try {
            $data = [
                'peak_hours' => $this->timeService->getPeakHoursAnalysis($request),
                'weekday_distribution' => $this->timeService->getWeekdayDistribution($request),
                'monthly_trends' => $this->timeService->getMonthlyTrends($request),
                'advance_booking' => $this->timeService->getAdvanceBookingAnalysis($request),
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '查詢統計數據時發生錯誤',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 匯出地理統計報表
     */
    public function exportGeographyReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'order_type' => 'nullable|string|in:'.implode(',', Order::ORDER_TYPES),
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        if ($startDate->diffInDays($endDate) > 365) {
            return back()->with('error', '查詢範圍不可超過一年');
        }

        try {
            $data = [
                'pickup_locations' => $this->geographyService->getPopularPickupLocations($request, null),
                'dropoff_locations' => $this->geographyService->getPopularDropoffLocations($request, null),
                'cross_county' => $this->geographyService->getCrossCountyOrders($request),
                'popular_routes' => $this->geographyService->getPopularRoutes($request, null),
            ];

            $filename = "地理統計報表_{$validated['start_date']}至{$validated['end_date']}.xlsx";

            return Excel::download(new GeographyStatisticsExport($data), $filename);
        } catch (\Exception $e) {
            return back()->with('error', '匯出失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯出時間統計報表
     */
    public function exportTimeReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'order_type' => 'nullable|string|in:'.implode(',', Order::ORDER_TYPES),
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        if ($startDate->diffInDays($endDate) > 365) {
            return back()->with('error', '查詢範圍不可超過一年');
        }

        try {
            $data = [
                'peak_hours' => $this->timeService->getPeakHoursAnalysis($request),
                'weekday_distribution' => $this->timeService->getWeekdayDistribution($request),
                'monthly_trends' => $this->timeService->getMonthlyTrends($request),
                'advance_booking' => $this->timeService->getAdvanceBookingAnalysis($request),
            ];

            $filename = "時間統計報表_{$validated['start_date']}至{$validated['end_date']}.xlsx";

            return Excel::download(new TimeStatisticsExport($data), $filename);
        } catch (\Exception $e) {
            return back()->with('error', '匯出失敗：'.$e->getMessage());
        }
    }

    /**
     * 客服人員統計頁面
     */
    public function customerServiceIndex()
    {
        return view('statistics.customer-service');
    }

    /**
     * API: 取得建單人員清單
     */
    public function getAvailableUsers(Request $request)
    {
        $users = Order::select('created_by')
            ->distinct()
            ->whereNotNull('created_by')
            ->orderBy('created_by')
            ->pluck('created_by');

        return response()->json(['users' => $users]);
    }

    /**
     * API: 取得客服統計數據
     */
    public function getCustomerServiceData(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'order_type' => 'nullable|string|in:'.implode(',', Order::ORDER_TYPES),
            'created_by' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        if ($startDate->diffInDays($endDate) > 365) {
            return response()->json(['error' => '查詢範圍不可超過一年'], 422);
        }

        try {
            $data = [
                'order_count_by_user' => $this->customerServiceService->getOrderCountByUser($request),
                'order_types_by_user' => $this->customerServiceService->getOrderTypesByUser($request),
                'orders_by_hour' => $this->customerServiceService->getOrdersByHour($request),
                'order_type_summary' => $this->customerServiceService->getOrderTypeSummary($request),
                'status_distribution' => $this->customerServiceService->getOrderStatusDistribution($request),
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('Customer service statistics error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => '查詢統計數據時發生錯誤',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 匯出客服人員統計報表
     */
    public function exportCustomerServiceReport(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'order_type' => 'nullable|string|in:'.implode(',', Order::ORDER_TYPES),
            'created_by' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        if ($startDate->diffInDays($endDate) > 365) {
            return back()->with('error', '查詢範圍不可超過一年');
        }

        try {
            $data = [
                'order_count_by_user' => $this->customerServiceService->getOrderCountByUser($request),
                'order_types_by_user' => $this->customerServiceService->getOrderTypesByUser($request),
                'orders_by_hour' => $this->customerServiceService->getOrdersByHour($request),
                'order_type_summary' => $this->customerServiceService->getOrderTypeSummary($request),
                'status_distribution' => $this->customerServiceService->getOrderStatusDistribution($request),
            ];

            $filename = "客服人員統計報表_{$validated['start_date']}至{$validated['end_date']}.xlsx";

            return Excel::download(new CustomerServiceStatisticsExport($data), $filename);
        } catch (\Exception $e) {
            return back()->with('error', '匯出失敗：'.$e->getMessage());
        }
    }
}
