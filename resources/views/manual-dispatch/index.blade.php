@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>排趟管理</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif


    <!-- 上方：排趟列表 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                排趟列表
                <span class="badge bg-danger" id="dispatch-count">{{ $dispatchOrders->count() }}</span>
            </h4>
            <div>
                @if($dispatchOrders->isNotEmpty())
                    <span class="text-white">已選擇 {{ $dispatchOrders->count() }} 筆訂單</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <!-- 批次指派表單 -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="batch_fleet_number" class="form-label">隊員編號</label>
                    <input type="text" class="form-control" id="batch_fleet_number" value="{{ $searchFleetNumber ?? '' }}" placeholder="請輸入隊員編號">
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <button type="button" class="btn btn-success me-2" onclick="batchAssign()">
                        <i class="fas fa-check"></i> 確認排趟
                    </button>
                    <button type="button" class="btn btn-danger" onclick="clearDispatch()">
                        <i class="fas fa-trash"></i> 清空列表
                    </button>
                </div>
            </div>

            <!-- 排趟訂單列表 -->
            <div class="table-responsive">
                <table id="dispatch-orders-table" class="table table-hover align-middle">
                    <thead class="table-warning">
                        <tr>
                            <th class="text-center sortable-header" style="width:4%" data-sort="date" onclick="sortTable('date')">
                                日期 <i class="fas fa-sort sort-icon" id="sort-icon-date"></i>
                            </th>
                            <th class="text-center sortable-header" style="width:4%" data-sort="time" onclick="sortTable('time')">
                                時間 <i class="fas fa-sort sort-icon" id="sort-icon-time"></i>
                            </th>
                            <th class="text-center sortable-header" style="width:5%" data-sort="passenger" onclick="sortTable('passenger')">
                                乘客 <i class="fas fa-sort sort-icon" id="sort-icon-passenger"></i>
                            </th>
                            <th class="text-center sortable-header" style="width:4%" data-sort="origin_area" onclick="sortTable('origin_area')">
                                上車區 <i class="fas fa-sort sort-icon" id="sort-icon-origin_area"></i>
                            </th>
                            <th class="text-center" style="width:12%">上車地點</th>
                            <th class="text-center sortable-header" style="width:4%" data-sort="dest_area" onclick="sortTable('dest_area')">
                                下車區 <i class="fas fa-sort sort-icon" id="sort-icon-dest_area"></i>
                            </th>
                            <th class="text-center" style="width:12%">下車地點</th>
                            <th class="text-center sortable-header" style="width:6%" data-sort="type" onclick="sortTable('type')">
                                訂單類型 <i class="fas fa-sort sort-icon" id="sort-icon-type"></i>
                            </th>
                            <th class="text-center" style="width:6%">特殊狀態</th>
                            <th class="text-center" style="width:5%">隊編</th>
                            <th class="text-center" style="width:8%">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- 已指派訂單 (來自資料庫查詢) --}}
                        @foreach($assignedOrders as $order)
                            <tr id="assigned-order-{{ $order['id'] }}" class="table-light">
                                <td class="text-center">{{ \Carbon\Carbon::parse($order['date'])->format('m/d') }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::createFromFormat('H:i:s', $order['time'])->format('H:i') }}</td>
                                <td class="text-center">{{ $order['name'] }}</td>
                                <td class="text-center">{{ $order['origin_area'] }}</td>
                                <td>{{ $order['origin_address'] }}</td>
                                <td class="text-center">{{ $order['dest_area'] }}</td>
                                <td>{{ $order['dest_address'] }}</td>
                                <td class="text-center">{{ $order['type'] }}</td>
                                <td class="text-center">
                                    @if($order['special_status'])
                                        <span class="badge bg-danger">{{ $order['special_status'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $order['fleet_number'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="text-muted">已指派</span>
                                </td>
                            </tr>
                        @endforeach

                        {{-- 待指派訂單 (來自 Session) --}}
                        @forelse($dispatchOrders as $order)
                            <tr id="dispatch-order-{{ $order['id'] }}">
                                <td class="text-center">{{ \Carbon\Carbon::parse($order['date'])->format('m/d') }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::createFromFormat('H:i:s', $order['time'])->format('H:i') }}</td>
                                <td class="text-center">{{ $order['name'] }}</td>
                                <td class="text-center">{{ $order['origin_area'] }}</td>
                                <td>{{ $order['origin_address'] }}</td>
                                <td class="text-center">{{ $order['dest_area'] }}</td>
                                <td>{{ $order['dest_address'] }}</td>
                                <td class="text-center">{{ $order['type'] }}</td>
                                <td class="text-center">
                                    @if($order['special_status'])
                                        <span class="badge bg-danger">{{ $order['special_status'] }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">-</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeFromDispatch({{ $order['id'] }})">
                                        <i class="fas fa-times"></i> 移除
                                    </button>
                                </td>
                            </tr>
                        @empty
                            @if($assignedOrders->isEmpty())
                                <tr id="empty-dispatch-row">
                                    <td colspan="11" class="text-center text-muted">尚未選擇任何訂單</td>
                                </tr>
                            @endif
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>



    <!-- 下方：待派遣訂單搜尋與列表 -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">待派遣訂單搜尋</h4>
        </div>
        <div class="card-body">
            <!-- 篩選表單 -->
            <form method="GET" action="{{ route('manual-dispatch.index') }}" class="row g-2 mb-4">
                <div class="col-md-2">
                    <label>開始日期</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" placeholder="開始日期">
                </div>
                <div class="col-md-2">
                    <label>結束日期</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" placeholder="結束日期">
                </div>
                <div class="col-md-2">
                    <label>隊員編號</label>
                    <input type="text" name="fleet_number" class="form-control" value="{{ request('fleet_number') }}" placeholder="隊員編號">
                </div>
                <div class="col-md-4">
                    <label>關鍵字搜尋</label>
                    <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="搜尋編號、類型、地址">
                </div>
                <div class="col-md-2">
                    <P></P>
                    <button type="submit" class="btn btn-success">搜尋</button>
                    <a href="{{ route('manual-dispatch.index') }}" class="btn btn-secondary">重設</a>
                </div>
                <div class="col-md-5">
                    <P></P>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-success" onclick="setQuickDate(0)">今天</button>
                        <button type="button" class="btn btn-outline-success" onclick="setQuickDate(1)">明天</button>
                        <button type="button" class="btn btn-outline-success" onclick="setQuickDate(2)">後天</button>
                        <button type="button" class="btn btn-outline-success" onclick="setQuickDate(3)">大後天</button>
                    </div>
                </div>
            </form>

            <!-- 待派遣訂單列表 -->
            <div class="table-responsive" style="max-height: 450px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);">
                <table id="available-orders-table" class="table table-hover align-middle mb-0">
                    <thead class="table-warning sticky-top">
                        <tr>
                            <th class="text-center" style="width:4%">日期</th>
                            <th class="text-center" style="width:4%">時間</th>
                            <th class="text-center" style="width:5%">乘客</th>
                            <th class="text-center" style="width:4%">上車區</th>
                            <th class="text-center" style="width:12%">上車地點</th>
                            <th class="text-center" style="width:4%">下車區</th>
                            <th class="text-center" style="width:12%">下車地點</th>
                            <th class="text-center" style="width:6%">訂單類型</th>
                            <th class="text-center" style="width:6%">特殊狀態</th>
                            <th class="text-center" style="width:8%">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($availableOrders as $order)
                            <tr id="available-order-{{ $order->id }}">
                                <td class="text-center">{{ \Carbon\Carbon::parse($order->ride_date)->format('m/d') }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::createFromFormat('H:i:s', $order->ride_time)->format('H:i') }}</td>
                                <td class="text-center">{{ $order->customer_name }}</td>
                                <td class="text-center">{{ $order->pickup_district }}</td>
                                <td>{{ $order->pickup_address }}</td>
                                <td class="text-center">{{ $order->dropoff_district }}</td>
                                <td>{{ $order->dropoff_address }}</td>
                                <td class="text-center">{{ $order->order_type }}</td>
                                <td class="text-center">
                                    @if($order->special_status)
                                        <span class="badge bg-danger">{{ $order->special_status }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-success" onclick="addToDispatch({{ $order->id }})">
                                        <i class="fas fa-plus"></i> 加入排趟
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    @if(request()->hasAny(['start_date', 'end_date', 'keyword', 'fleet_number']))
                                        沒有找到符合條件的待派遣訂單
                                    @else
                                        請設定搜尋條件來查找待派遣訂單
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
/* 自定義滾動條樣式 */
.table-responsive::-webkit-scrollbar {
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* 響應式高度調整 */
@media (max-width: 768px) {
    .table-responsive {
        max-height: 300px !important;
    }
}

@media (max-width: 576px) {
    .table-responsive {
        max-height: 250px !important;
    }
}

/* 確保固定標題在滾動容器中正確顯示 */
.table-responsive .sticky-top {
    background-color: var(--bs-warning) !important;
    z-index: 10;
}

/* 排序功能樣式 */
.sortable-header {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s ease;
}

.sortable-header:hover {
    background-color: rgba(255, 193, 7, 0.8) !important;
}

.sort-icon {
    margin-left: 4px;
    font-size: 12px;
    color: #666;
    transition: color 0.2s ease;
}

.sortable-header:hover .sort-icon {
    color: #333;
}

.sortable-header.sorted-asc .sort-icon {
    color: #28a745;
}

.sortable-header.sorted-desc .sort-icon {
    color: #dc3545;
}
</style>

@push('scripts')
<!-- 載入 Toastr.js 用於通知 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script src="{{ asset('js/manual-dispatch.js') }}"></script>
<script>
// 傳遞伺服器日期到 JavaScript
window.serverCurrentDate = '{{ $serverCurrentDate }}';
</script>
@endpush

@endsection
