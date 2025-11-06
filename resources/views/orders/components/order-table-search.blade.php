<div class="card">
    <div class="card-header bg-info">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>訂單列表
            </h5>
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
            <table class="table table-hover" id="ordersTableSearch">
                <thead class="table-info">
                    <tr>
                        <th>訂單來源</th>
                        <th>客戶姓名</th>
                        <th>身分證字號</th>
                        <th>生日</th>
                        <th>用車日期</th>
                        <th>用車時間</th>
                        <th>上車地址/下車地址</th>
                        <th>備註</th>
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
                        <td>{{ $order->customer_id_number}}</td>
                        <td>
                            @if($order->customer?->birthday)
                                @php
                                    $birthday = \Carbon\Carbon::parse($order->customer->birthday);
                                    $rocYear = $birthday->year - 1911;
                                    $rocDate = sprintf('%03d/%02d/%02d', $rocYear, $birthday->month, $birthday->day);
                                @endphp
                                {{ $rocDate }}
                            @else
                                N/A
                            @endif
                        </td>
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
                        <td>{{ Str::limit($order->pickup_address, 60) }}<br>{{ Str::limit($order->dropoff_address, 60) }}</td>
                        <td>{{ $order->customer?->note }}</td>
                        <td>
                            @if($order->stair_machine == '是')
                                <span class="badge bg-warning">爬梯機</span>
                            @elseif($order->stair_machine == '未知')
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

{{-- 時間範圍匯出 Modal --}}
<div class="modal fade" id="exportDateModal" tabindex="-1" aria-labelledby="exportDateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="exportDateModalLabel">
                    <i class="fas fa-calendar-alt me-2"></i>選擇匯出時間範圍
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportDateForm">
                    {{-- 篩選模式選擇 --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-filter me-2"></i>篩選模式
                        </label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="filter_mode" id="filter_created_at" value="created_at">
                            <label class="btn btn-outline-primary" for="filter_created_at">只篩選建立時間</label>

                            <input type="radio" class="btn-check" name="filter_mode" id="filter_ride_date" value="ride_date">
                            <label class="btn btn-outline-primary" for="filter_ride_date">只篩選用車日期</label>

                            <input type="radio" class="btn-check" name="filter_mode" id="filter_both" value="both" checked>
                            <label class="btn btn-outline-primary" for="filter_both">兩者都要</label>
                        </div>
                    </div>

                    {{-- 建立時間範圍 --}}
                    <div class="mb-4 border rounded p-3 bg-light" data-section="created-at">
                        <h6 class="mb-3">
                            <i class="fas fa-clock me-2 text-primary"></i>建立時間範圍
                        </h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">開始時間</label>
                                <input type="datetime-local" class="form-control" name="created_start_date" id="export_created_start_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">結束時間</label>
                                <input type="datetime-local" class="form-control" name="created_end_date" id="export_created_end_date">
                            </div>
                        </div>
                        <div>
                            <label class="form-label text-muted">
                                <i class="fas fa-bolt me-1"></i>快捷選項：
                            </label>
                            <div class="btn-group d-flex flex-wrap gap-2" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-range-type="created" data-range="today">今日</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-range-type="created" data-range="yesterday">昨日</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-range-type="created" data-range="week">本週</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-range-type="created" data-range="month">本月</button>
                            </div>
                        </div>
                    </div>

                    {{-- 用車日期 --}}
                    <div class="mb-4 border rounded p-3 bg-light" data-section="ride-date">
                        <h6 class="mb-3">
                            <i class="fas fa-car me-2 text-success"></i>用車日期
                        </h6>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">選擇日期</label>
                                <input type="date" class="form-control" name="ride_date" id="export_ride_date">
                            </div>
                        </div>
                        <div>
                            <label class="form-label text-muted">
                                <i class="fas fa-bolt me-1"></i>快捷選項：
                            </label>
                            <div class="btn-group d-flex flex-wrap gap-2" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-range-type="ride" data-range="today">今日</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-range-type="ride" data-range="yesterday">昨日</button>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>說明：</h6>
                        <ul class="mb-0">
                            <li>可根據<strong>建立時間</strong>（created_at）或<strong>用車日期</strong>（ride_date）篩選</li>
                            <li>匯出格式：簡化格式（14欄位）</li>
                            <li>狀態限制：僅匯出可派遣、已指派、已候補訂單</li>
                            <li>共乘訂單：僅匯出主訂單，避免重複</li>
                            <li>建立時間範圍：最多一年</li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>取消
                </button>
                <button type="button" class="btn btn-success" id="confirmExport">
                    <i class="fas fa-file-export me-2"></i>確認匯出
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 時間範圍匯出功能 --}}
<script>
// ===== 時間範圍匯出功能 =====
// 格式化日期時間為 datetime-local 格式
function formatDateTime(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// 格式化日期為 date 格式 (YYYY-MM-DD)
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// 快捷時間範圍設定
document.addEventListener('DOMContentLoaded', function() {
    // 篩選模式切換時顯示/隱藏對應區塊
    const createdAtSection = document.querySelector('[data-section="created-at"]');
    const rideDateSection = document.querySelector('[data-section="ride-date"]');
    const filterModeRadios = document.querySelectorAll('input[name="filter_mode"]');

    function toggleSections() {
        const selectedMode = document.querySelector('input[name="filter_mode"]:checked').value;

        if (selectedMode === 'created_at') {
            // 只篩選建立時間：顯示建立時間，隱藏用車日期
            createdAtSection.style.display = 'block';
            rideDateSection.style.display = 'none';
        } else if (selectedMode === 'ride_date') {
            // 只篩選用車日期：隱藏建立時間，顯示用車日期
            createdAtSection.style.display = 'none';
            rideDateSection.style.display = 'block';
        } else if (selectedMode === 'both') {
            // 兩者都要：顯示全部
            createdAtSection.style.display = 'block';
            rideDateSection.style.display = 'block';
        }
    }

    // 監聽篩選模式變更
    filterModeRadios.forEach(radio => {
        radio.addEventListener('change', toggleSections);
    });

    // 初始化顯示狀態（預設為「兩者都要」）
    toggleSections();

    document.querySelectorAll('[data-range]').forEach(btn => {
        btn.addEventListener('click', function() {
            const range = this.dataset.range;
            const rangeType = this.dataset.rangeType; // 'created' 或 'ride'
            const now = new Date();
            let startDate, endDate, singleDate;

            switch(range) {
                case 'today':
                    startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0);
                    endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59);
                    singleDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    break;
                case 'yesterday':
                    const yesterday = new Date(now);
                    yesterday.setDate(yesterday.getDate() - 1);
                    startDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 0, 0);
                    endDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate(), 23, 59);
                    singleDate = new Date(yesterday.getFullYear(), yesterday.getMonth(), yesterday.getDate());
                    break;
                case 'week':
                    const weekStart = new Date(now);
                    weekStart.setDate(now.getDate() - now.getDay());
                    startDate = new Date(weekStart.getFullYear(), weekStart.getMonth(), weekStart.getDate(), 0, 0);
                    endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59);
                    singleDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    break;
                case 'month':
                    startDate = new Date(now.getFullYear(), now.getMonth(), 1, 0, 0);
                    endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0, 23, 59);
                    singleDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    break;
            }

            // 根據 rangeType 設定對應的輸入框
            if (rangeType === 'created') {
                document.getElementById('export_created_start_date').value = formatDateTime(startDate);
                document.getElementById('export_created_end_date').value = formatDateTime(endDate);
            } else if (rangeType === 'ride') {
                document.getElementById('export_ride_date').value = formatDate(singleDate);
            }
        });
    });

    // 確認匯出處理
    document.getElementById('confirmExport').addEventListener('click', function() {
        const form = document.getElementById('exportDateForm');
        const filterMode = document.querySelector('input[name="filter_mode"]:checked').value;

        const createdStartInput = document.getElementById('export_created_start_date');
        const createdEndInput = document.getElementById('export_created_end_date');
        const rideDateInput = document.getElementById('export_ride_date');

        // 根據篩選模式驗證必填欄位
        if (filterMode === 'created_at' || filterMode === 'both') {
            if (!createdStartInput.value || !createdEndInput.value) {
                alert('請選擇建立時間的開始時間和結束時間');
                return;
            }

            // 驗證建立時間範圍
            const createdStart = new Date(createdStartInput.value);
            const createdEnd = new Date(createdEndInput.value);

            if (createdStart >= createdEnd) {
                alert('建立時間：開始時間必須早於結束時間');
                return;
            }

            // 檢查時間範圍是否超過一年
            const daysDiff = (createdEnd - createdStart) / (1000 * 60 * 60 * 24);
            if (daysDiff > 365) {
                alert('建立時間範圍不得超過一年（365天）');
                return;
            }
        }

        if (filterMode === 'ride_date' || filterMode === 'both') {
            if (!rideDateInput.value) {
                alert('請選擇用車日期');
                return;
            }
        }

        // 建構匯出URL
        const params = new URLSearchParams();
        params.append('filter_mode', filterMode);

        if (createdStartInput.value && createdEndInput.value) {
            params.append('created_start_date', createdStartInput.value);
            params.append('created_end_date', createdEndInput.value);
        }

        if (rideDateInput.value) {
            params.append('ride_date', rideDateInput.value);
        }

        // 執行匯出
        window.location.href = `/orders/export-simple-by-date?${params.toString()}`;

        // 關閉Modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('exportDateModal'));
        if (modal) {
            modal.hide();
        }
    });
});
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
