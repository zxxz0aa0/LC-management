@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">

    <!-- 返回按鈕 -->
    <div class="mb-3">
        <a href="{{ route('dispatch-records.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>

    <!-- 排趟基本資訊 -->
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-info-circle"></i> 排趟基本資訊</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">排趟名稱：</th>
                            <td><strong>{{ $record->dispatch_name }}</strong></td>
                        </tr>
                        <tr>
                            <th>批次編號：</th>
                            <td><code>{{ $record->batch_id }}</code></td>
                        </tr>
                        <tr>
                            <th>司機：</th>
                            <td>
                                @if($record->driver)
                                    <span class="badge bg-info">{{ $record->driver_fleet_number }}</span>
                                    {{ $record->driver_name }}
                                @else
                                    <span class="text-muted">{{ $record->driver_name }} (已刪除)</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>訂單數量：</th>
                            <td>
                                <span class="badge bg-success">{{ $record->order_count }} 筆</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%">排趟日期：</th>
                            <td>
                                <i class="fas fa-calendar"></i>
                                {{ $record->dispatch_date ? $record->dispatch_date->format('Y-m-d') : 'N/A' }}
                            </td>
                        </tr>
                        <tr>
                            <th>執行時間：</th>
                            <td>
                                <i class="fas fa-clock"></i>
                                {{ $record->performed_at->format('Y-m-d H:i:s') }}
                            </td>
                        </tr>
                        <tr>
                            <th>執行人：</th>
                            <td>
                                <i class="fas fa-user"></i>
                                {{ $record->performer->name ?? 'N/A' }}
                            </td>
                        </tr>
                        @if($record->notes)
                        <tr>
                            <th>備註：</th>
                            <td>{{ $record->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 訂單清單 -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">
                <i class="fas fa-list"></i> 訂單清單
                <span class="badge bg-light text-dark">{{ $orders->count() }} 筆</span>
            </h4>
        </div>
        <div class="card-body">
            @if($orders->isEmpty())
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle"></i> 找不到訂單資料（訂單可能已被刪除）
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:2%">序</th>
                                <!--<th style="width:4%">訂單編號</th>-->
                                <th style="width:6%">客戶姓名</th>
                                <th style="width:8%">身份證字號</th>
                                <th style="width:8%">用車日期</th>
                                <th style="width:6%">用車時間</th>
                                <th style="width:22%">上車地址</th>
                                <th style="width:22%">下車地址</th>
                                <th style="width:6%">訂單類型</th>
                                <th style="width:4%">狀態</th>
                                <th style="width:4%">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $index => $order)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <!--<td>
                                        <a href="{{ route('orders.show', $order->id) }}" target="_blank" class="text-decoration-none">
                                            {{ $order->order_number }}
                                            <i class="fas fa-external-link-alt fa-xs"></i>
                                        </a>
                                    </td>-->
                                    <td>{{ $order->customer_name }}</td>
                                    <td>{{ $order->customer_id_number }}</td>
                                    <td>
                                        {{ $order->ride_date->format('Y-m-d') }}
                                    </td>
                                    <td>
                                        {{ $order->ride_time ? \Illuminate\Support\Carbon::parse($order->ride_time)->format('H:i') : 'N/A' }}
                                    </td>
                                    <td>
                                        <h6>
                                            {{ $order->pickup_address }}
                                        </h6>
                                    </td>
                                    <td>
                                        <h6>
                                            {{ $order->dropoff_address }}
                                        </h6>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $order->order_type }}</span>
                                    </td>
                                    <td>
                                        @switch($order->status)
                                            @case('open')
                                                <span class="badge bg-success">可派遣</span>
                                                @break
                                            @case('assigned')
                                                <span class="badge bg-primary">已指派</span>
                                                @break
                                            @case('bkorder')
                                                <span class="badge bg-warning">已候補</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-danger">已取消</span>
                                                @break
                                            @case('cancelledOOC')
                                                <span class="badge bg-danger">已取消-9999</span>
                                                @break
                                            @case('cancelledNOC')
                                                <span class="badge bg-danger">取消！</span>
                                                @break
                                            @case('cancelledCOTD')
                                                <span class="badge bg-danger">取消 X</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">未知</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('orders.show', array_merge(['order' => $order], request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine']))) }}" class="btn btn-info btn-sm" title="檢視">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('orders.edit', array_merge(['order' => $order], request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine']))) }}" class="btn btn-warning btn-sm" title="編輯">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if(in_array($order->status, ['open', 'assigned', 'bkorder']) && !in_array($order->status, ['cancelled', 'cancelledOOC', 'cancelledNOC', 'cancelledCOTD']))
                                                <button type="button" class="btn btn-danger btn-sm" onclick="showCancelModal({{ $order->id }})" title="取消">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                            <!--<button type="button" class="btn btn-danger btn-sm" onclick="deleteOrder({{ $order->id }})" title="刪除">
                                                <i class="fas fa-trash"></i>
                                            </button>-->
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- 返回按鈕 (底部) -->
    <div class="mt-3 mb-4">
        <a href="{{ route('dispatch-records.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 返回列表
        </a>
    </div>

    {{-- 取消訂單原因選擇 Modal --}}
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>取消訂單
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cancellationReasonText" class="form-label">
                            <i class="fas fa-comment-alt me-2"></i>取消原因說明（選填）
                        </label>
                        <textarea
                            class="form-control"
                            id="cancellationReasonText"
                            rows="3"
                            maxlength="500"
                            placeholder="請輸入取消原因詳細說明...（最多500字）"
                        ></textarea>
                        <small class="text-muted">別家有車也可以不用填</small>
                    </div>

                    <p class="mb-4">請選擇取消原因：</p>
                    <div class="d-grid gap-2 mb-4">
                        <button type="button" class="btn btn-outline-dark" onclick="cancelOrderWithReason('cancelled')">
                            <i class="me-2"></i>一般取消
                        </button>
                        <button type="button" class="btn btn-outline-dark" onclick="cancelOrderWithReason('cancelledOOC')">
                            <i class="me-2"></i>別家有車
                        </button>
                        <button type="button" class="btn btn-outline-dark" onclick="cancelOrderWithReason('cancelledNOC')">
                            <i class="me-2"></i>！取消
                        </button>
                        <button type="button" class="btn btn-outline-dark" onclick="cancelOrderWithReason('cancelledCOTD')">
                            <i class="me-2"></i>X 取消
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>取消
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 取消訂單 JavaScript 功能 --}}
    <script>
    let currentOrderId = null;

    // 顯示取消原因選擇 Modal
    function showCancelModal(orderId) {
        currentOrderId = orderId;
        // 清空之前的取消原因說明
        document.getElementById('cancellationReasonText').value = '';
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
        cancelModal.show();
    }

    // 使用指定原因取消訂單
    function cancelOrderWithReason(reason) {
        if (!currentOrderId) {
            alert('錯誤：無法取得訂單 ID');
            return;
        }

        // 取得取消原因說明文字
        const cancellationReasonText = document.getElementById('cancellationReasonText').value.trim();

        // 關閉 Modal
        const cancelModal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
        cancelModal.hide();

        // 發送 AJAX 請求
        fetch(`/orders/${currentOrderId}/cancel`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                cancel_reason: reason,
                cancellation_reason_text: cancellationReasonText // 傳送取消原因說明
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 成功提示
                alert('✅ ' + data.message);

                // 重新載入頁面以更新狀態顯示
                location.reload();
            } else {
                // 錯誤提示
                alert('❌ ' + data.message);
            }
        })
        .catch(error => {
            console.error('取消訂單失敗:', error);
            alert('❌ 取消失敗，請稍後再試');
        })
        .finally(() => {
            currentOrderId = null; // 清除當前訂單 ID
        });
    }
    </script>

</div>
@endsection
