<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use App\Services\CarpoolGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarpoolGroupController extends Controller
{
    protected $carpoolGroupService;

    public function __construct(CarpoolGroupService $carpoolGroupService)
    {
        $this->carpoolGroupService = $carpoolGroupService;
    }

    /**
     * 顯示共乘群組列表
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer'])
            ->where('is_main_order', true)
            ->whereNotNull('carpool_group_id')
            ->where('is_group_dissolved', false);

        // 搜尋功能
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('carpool_group_id', 'like', "%{$keyword}%")
                    ->orWhere('order_number', 'like', "%{$keyword}%");
            });
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 日期篩選
        if ($request->filled('date_from')) {
            $query->whereDate('ride_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('ride_date', '<=', $request->date_to);
        }

        // 排序和分頁
        $groups = $query->orderBy('created_at', 'desc')->paginate(20);

        // 為每個群組載入成員資訊
        $groups->getCollection()->transform(function ($mainOrder) {
            $groupInfo = $this->carpoolGroupService->getGroupInfo($mainOrder->carpool_group_id);
            $mainOrder->group_info = $groupInfo;

            return $mainOrder;
        });

        return view('carpool-groups.index', compact('groups'));
    }

    /**
     * 顯示特定群組詳細資訊
     */
    public function show($groupId)
    {
        $groupInfo = $this->carpoolGroupService->getGroupInfo($groupId);

        if (! $groupInfo) {
            return redirect()->route('carpool-groups.index')
                ->with('error', '找不到指定的共乘群組');
        }

        // 取得可用司機列表
        $availableDrivers = Driver::where('status', '在職')->get();

        return view('carpool-groups.show', compact('groupInfo', 'availableDrivers'));
    }

    /**
     * 指派司機給群組
     */
    public function assignDriver(Request $request, $groupId)
    {
        try {
            $request->validate([
                'driver_id' => 'required|exists:drivers,id',
            ]);

            $result = $this->carpoolGroupService->assignDriverToGroup(
                $groupId,
                $request->driver_id
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'driver' => $result['driver'],
                ]);
            }

            return redirect()->route('carpool-groups.show', $groupId)
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('指派司機失敗', [
                'group_id' => $groupId,
                'driver_id' => $request->driver_id,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '指派司機失敗：'.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('carpool-groups.show', $groupId)
                ->with('error', '指派司機失敗：'.$e->getMessage());
        }
    }

    /**
     * 取消群組
     */
    public function cancelGroup(Request $request, $groupId)
    {
        try {
            $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $result = $this->carpoolGroupService->cancelGroup(
                $groupId,
                $request->reason ?? ''
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                ]);
            }

            return redirect()->route('carpool-groups.index')
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('取消群組失敗', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '取消群組失敗：'.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('carpool-groups.show', $groupId)
                ->with('error', '取消群組失敗：'.$e->getMessage());
        }
    }

    /**
     * 解除群組
     */
    public function dissolveGroup(Request $request, $groupId)
    {
        try {
            $request->validate([
                'reason' => 'nullable|string|max:500',
                'force' => 'boolean',
            ]);

            $result = $this->carpoolGroupService->dissolveGroup(
                $groupId,
                $request->reason ?? '',
                $request->boolean('force', false)
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'dissolved_orders' => $result['orders'],
                ]);
            }

            return redirect()->route('carpool-groups.index')
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('解除群組失敗', [
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '解除群組失敗：'.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('carpool-groups.show', $groupId)
                ->with('error', '解除群組失敗：'.$e->getMessage());
        }
    }

    /**
     * 更新群組狀態
     */
    public function updateStatus(Request $request, $groupId)
    {
        try {
            $request->validate([
                'status' => 'required|in:open,assigned,replacement,blocked,cancelled,completed',
                'remark' => 'nullable|string|max:500',
            ]);

            $updateData = [];
            if ($request->filled('remark')) {
                $updateData['remark'] = $request->remark;
            }

            $this->carpoolGroupService->syncGroupStatus(
                $groupId,
                $request->status,
                $updateData
            );

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '群組狀態已更新',
                ]);
            }

            return redirect()->route('carpool-groups.show', $groupId)
                ->with('success', '群組狀態已更新');

        } catch (\Exception $e) {
            Log::error('更新群組狀態失敗', [
                'group_id' => $groupId,
                'status' => $request->status,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '更新狀態失敗：'.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('carpool-groups.show', $groupId)
                ->with('error', '更新狀態失敗：'.$e->getMessage());
        }
    }

    /**
     * 批量操作
     */
    public function batchAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:cancel,dissolve,assign_driver',
                'group_ids' => 'required|array|min:1',
                'group_ids.*' => 'string',
                'driver_id' => 'required_if:action,assign_driver|exists:drivers,id',
                'reason' => 'nullable|string|max:500',
            ]);

            $results = [
                'success' => [],
                'failed' => [],
            ];

            foreach ($request->group_ids as $groupId) {
                try {
                    switch ($request->action) {
                        case 'cancel':
                            $this->carpoolGroupService->cancelGroup($groupId, $request->reason ?? '');
                            $results['success'][] = $groupId;
                            break;

                        case 'dissolve':
                            $this->carpoolGroupService->dissolveGroup($groupId, $request->reason ?? '');
                            $results['success'][] = $groupId;
                            break;

                        case 'assign_driver':
                            $this->carpoolGroupService->assignDriverToGroup($groupId, $request->driver_id);
                            $results['success'][] = $groupId;
                            break;
                    }
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'group_id' => $groupId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            $message = '成功處理 '.count($results['success']).' 個群組';
            if (! empty($results['failed'])) {
                $message .= '，失敗 '.count($results['failed']).' 個群組';
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'results' => $results,
                ]);
            }

            return redirect()->route('carpool-groups.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('批量操作失敗', [
                'action' => $request->action,
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => '批量操作失敗：'.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('carpool-groups.index')
                ->with('error', '批量操作失敗：'.$e->getMessage());
        }
    }
}
