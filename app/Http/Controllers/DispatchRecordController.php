<?php

namespace App\Http\Controllers;

use App\Models\DispatchRecord;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\Request;

class DispatchRecordController extends Controller
{
    /**
     * 顯示排趟記錄列表
     */
    public function index(Request $request)
    {
        $query = DispatchRecord::with(['driver', 'performer', 'entryStatusUpdater'])
            ->recent(2); // 只顯示最近 2 個月的記錄

        // 日期範圍篩選
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // 司機篩選
        if ($request->filled('driver_id')) {
            $query->byDriver($request->driver_id);
        }

        // 關鍵字搜尋（司機名稱、排趟名稱）
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('dispatch_name', 'like', "%{$keyword}%")
                    ->orWhere('driver_name', 'like', "%{$keyword}%");
            });
        }

        $records = $query->orderBy('performed_at', 'desc')->paginate(20);

        // 取得所有司機供篩選使用
        $drivers = Driver::where('status', 'active')->orderBy('fleet_number')->get();

        return view('dispatch-records.index', compact('records', 'drivers'));
    }

    /**
     * 顯示排趟記錄詳情
     */
    public function show($id)
    {
        $record = DispatchRecord::with(['driver', 'performer'])->findOrFail($id);

        // 取得該排趟的所有訂單
        $orders = Order::whereIn('id', $record->order_ids ?? [])
            ->with('customer')
            ->orderBy('ride_date')
            ->orderBy('ride_time')
            ->get();

        return view('dispatch-records.show', compact('record', 'orders'));
    }

    /**
     * 更新登打處理狀態
     */
    public function updateEntryStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed',
        ]);

        $record = DispatchRecord::findOrFail($id);

        $record->update([
            'entry_status' => $request->status,
            'entry_status_updated_by' => auth()->id(),
            'entry_status_updated_at' => now(),
        ]);

        // 重新載入關聯以取得更新者資訊
        $record->load('entryStatusUpdater');

        return response()->json([
            'success' => true,
            'message' => '登打處理狀態已更新',
            'data' => [
                'status' => $record->entry_status,
                'status_label' => $record->entry_status_label,
                'badge_class' => $record->entry_status_badge_class,
                'updated_by' => $record->entryStatusUpdater?->name,
                'updated_at' => $record->entry_status_updated_at?->format('Y-m-d H:i'),
            ],
        ]);
    }
}
