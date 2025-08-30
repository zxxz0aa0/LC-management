<div class="card">
    <div class="card-header bg-info">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>訂單列表
            </h5>
            <div class="btn-group">
                <!-- 匯入按鈕 -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-file-import me-2"></i>匯入 Excel
                </button>
                <!-- 批量更新按鈕 -->
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#batchUpdateModal">
                    <i class="fas fa-edit me-2"></i>批量更新
                </button>
                <!-- 匯出按鈕組 -->
                <div class="btn-group">
                    <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-file-export me-2"></i>匯出 Excel
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('orders.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}">
                            <i class="fas fa-file-excel me-2"></i>完整格式 (28欄位)
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('orders.export.simple') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}">
                            <i class="fas fa-file-csv me-2"></i>簡化格式 (14欄位)
                        </a></li>
                    </ul>
                </div>
                <!-- 範本下載按鈕組 -->
                <div class="btn-group">
                    <button type="button" class="btn btn-dark dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-2"></i>下載範本
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('orders.template') }}">
                            <i class="fas fa-file-excel me-2"></i>完整格式範本
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('orders.template.simple') }}">
                            <i class="fas fa-file-csv me-2"></i>簡化格式範本
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive" style="font-size: 18px" >
            <table class="table table-hover" id="ordersTable">
                <thead class="table-success">
                    <tr>
                        <th>訂單來源</th>
                        <th>客戶姓名</th>
                        <th>電話</th>
                        <th>用車日期</th>
                        <th>用車時間</th>
                        <th>上車地址/下車地址</th>
                        <!--<th>下車地址</th>-->
                        <th>共乘姓名</th>
                        <th>特殊狀態</th>
                        <th>駕駛</th>
                        <th>訂單狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_type }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->customer_phone }}</td>
                        <td>{{ $order->ride_date ? (is_string($order->ride_date) ? $order->ride_date : $order->ride_date->format('Y-m-d')) : 'N/A' }}</td>
                        <td>
                            @if($order->match_time)
                                <div class="d-flex align-items-center gap-1">
                                    <span>{{ $order->match_time->format('H:i') }}</span>
                                    <span class="badge bg-dark">搓合</span>
                                </div>
                            @else
                                {{ $order->ride_time ? \Illuminate\Support\Carbon::parse($order->ride_time)->format('H:i') : 'N/A' }}
                            @endif
                        </td>
                        <td>{{ Str::limit($order->pickup_address, 30) }}<br>{{ Str::limit($order->dropoff_address, 30) }}</td>
                        <!--<td>{{ Str::limit($order->dropoff_address, 30) }}</td>-->
                        <td>{{ $order->carpool_name }}</td>
                        <td>
                            @if($order->stair_machine == '是')
                                <span class="badge bg-warning">爬梯機</span>
                            @elseif($order->stair_machine == '未知')
                                <span class="badge bg-secondary"></span>
                            @endif
                            @if($order->wheelchair == '是')
                                <span class="badge bg-info">輪椅</span>
                            @elseif($order->wheelchair == '未知')
                                <span class="badge bg-secondary"></span>
                            @endif
                            @switch($order->special_status)
                                @case('一般')
                                    <span class="badge bg-success"></span>
                                    @break
                                @case('網頁單')
                                    <span class="badge bg-danger">網頁單</span>
                                    @break
                                @case('Line')
                                    <span class="badge bg-success">Line</span>
                                    @break
                                @case('個管單')
                                    <span class="badge bg-danger">個管單</span>
                                    @break
                                @case('黑名單')
                                    <span class="badge bg-dark">黑名單</span>
                                    @break
                                @case('共乘單')
                                    <span class="badge bg-primary">共乘單</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">未知</span>
                            @endswitch
                        </td>
                        <td>{{ $order->driver_fleet_number ?: '-' }}</td>
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
                                <a href="{{ route('orders.show', array_merge(['order' => $order], request()->only(['keyword', 'start_date', 'end_date', 'customer_id']))) }}" class="btn btn-info btn-sm" title="檢視">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('orders.edit', array_merge(['order' => $order], request()->only(['keyword', 'start_date', 'end_date', 'customer_id']))) }}" class="btn btn-warning btn-sm" title="編輯">
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
                    @empty
                    <tr class="no-data-row">
                        <td colspan="10" class="text-center">
                            <div class="py-4">
                                <i class="fas fa-inbox text-muted mb-2" style="font-size: 3rem;"></i>
                                <p class="text-muted mb-0">目前沒有訂單資料</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
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
                <p class="mb-4">請選擇取消原因：</p>
                <div class="d-grid gap-2">
                    <button type="button"  class="btn btn-outline-dark" onclick="cancelOrderWithReason('cancelled')">
                        <i class="me-2"></i>一般取消
                    </button>
                    <button type="button"  class="btn btn-outline-dark" onclick="cancelOrderWithReason('cancelledOOC')">
                        <i class="me-2"></i>別家有車
                    </button>
                    <button type="button"  class="btn btn-outline-dark" onclick="cancelOrderWithReason('cancelledNOC')">
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

{{-- 匯入 Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">
                    <i class="fas fa-file-import me-2"></i>匯入訂單資料
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="importFile" class="form-label">選擇 Excel 檔案</label>
                        <input type="file" class="form-control" id="importFile" name="file" accept=".xlsx,.xls" required>
                        <div class="form-text">
                            支援格式：.xlsx, .xls
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>使用說明：</h6>
                        <ul class="mb-0">
                            <li>請使用「下載範本」按鈕取得正確格式</li>
                            <li>支援完整格式（28欄位）和簡化格式（14欄位）</li>
                            <li>系統會自動偵測檔案格式類型</li>
                            <li>訂單編號不可重複</li>
                            <li>必填欄位：訂單編號、客戶姓名、電話、用車日期、時間、地址</li>
                            <li>長照訂單會自動驗證地址限制</li>
                            <li>大量資料（>1000筆）會自動使用佇列處理</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>取消
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>開始匯入
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 批量更新 Modal --}}
<div class="modal fade" id="batchUpdateModal" tabindex="-1" aria-labelledby="batchUpdateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="batchUpdateModalLabel">
                    <i class="fas fa-edit me-2"></i>批量更新訂單
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('orders.batch-update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="batchUpdateFile" class="form-label">選擇 Excel 檔案</label>
                        <input type="file" class="form-control" id="batchUpdateFile" name="file" accept=".xlsx,.xls" required>
                        <div class="form-text">
                            支援格式：.xlsx, .xls
                        </div>
                    </div>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>批量更新說明：</h6>
                        <ul class="mb-0">
                            <li><strong>A欄（訂單編號）</strong>：用於查詢要更新的訂單，必填</li>
                            <li><strong>E欄（隊員編號）</strong>：更新駕駛資訊，對應 drivers 表的 fleet_number</li>
                            <li><strong>H欄（媒合時間）</strong>：更新媒合時間，格式：YYYY-MM-DD HH:MM:SS</li>
                            <li><strong>M欄（狀態）</strong>：更新訂單狀態（待搶單/已指派/已取消/已候補/已完成）</li>
                            <li>系統會根據訂單編號查詢現有訂單並更新相應欄位</li>
                            <li>找不到的訂單編號會被跳過</li>
                            <li>駕駛資訊會根據隊員編號自動填入駕駛姓名、車牌等</li>
                        </ul>
                    </div>
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Excel 欄位格式範例：</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>A欄</th>
                                        <th>B欄</th>
                                        <th>C欄</th>
                                        <th>D欄</th>
                                        <th>E欄</th>
                                        <th>F欄</th>
                                        <th>G欄</th>
                                        <th>H欄</th>
                                        <th>...</th>
                                        <th>M欄</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>訂單編號</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>隊員編號</td>
                                        <td>-</td>
                                        <td>-</td>
                                        <td>媒合時間</td>
                                        <td>...</td>
                                        <td>狀態</td>
                                    </tr>
                                    <tr class="text-muted">
                                        <td>ORD20250829001</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>A001</td>
                                        <td></td>
                                        <td></td>
                                        <td>2025-08-29 08:30</td>
                                        <td></td>
                                        <td>已指派</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>取消
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>開始批量更新
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 取消訂單 JavaScript 功能 --}}
<script>
let currentOrderId = null;

// 顯示取消原因選擇 Modal
function showCancelModal(orderId) {
    currentOrderId = orderId;
    const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
    cancelModal.show();
}

// 使用指定原因取消訂單
function cancelOrderWithReason(reason) {
    if (!currentOrderId) {
        alert('錯誤：無法取得訂單 ID');
        return;
    }

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
            cancel_reason: reason
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

@if(session('import_errors'))
    <div class="modal fade" id="errorsModal" tabindex="-1" aria-labelledby="errorsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="errorsModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>匯入錯誤詳情
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        以下資料列無法匯入，請檢查後重新匯入：
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        @foreach(session('import_errors') as $error)
                            <div class="alert alert-danger mb-2">
                                {{ $error }}
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>關閉
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const errorsModal = new bootstrap.Modal(document.getElementById('errorsModal'));
            errorsModal.show();
        });
    </script>
@endif
