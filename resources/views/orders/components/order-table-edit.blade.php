<div class="card">
    <div class="card-header bg-warning">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>訂單列表 - 多編模式
                <span class="badge bg-dark ms-2" id="selectedCountBadge" style="display: none;">已選: <span id="selectedCountText">0</span></span>
            </h5>
            <div>
                <button type="button" class="btn btn-outline-dark" id="batchEditBtn" data-bs-toggle="modal" data-bs-target="#batchEditModal" disabled>
                    <i class="fas fa-edit me-2"></i>批次編輯
                </button>
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

        <div class="table-responsive" style="width:100%; font-size: 18px;">
            <table class="table table-hover" id="ordersTableEdit">
                <thead class="table-warning">
                    <tr>
                        <th style="width:3%" class="text-center align-middle">
                            <input type="checkbox" id="selectAll" class="form-check-input" title="全選">
                        </th>
                        <th style="width:6%">訂單來源</th>
                        <th style="width:10%">客戶姓名/電話</th>
                        <th style="width:8%">用車日期</th>
                        <th style="width:8%">用車時間</th>
                        <th style="width:24%">上車/下車地址</th>
                        <th style="width:8%">共乘姓名</th>
                        <th style="width:10%">特殊狀態</th>
                        <th style="width:8%">訂單狀態</th>
                        <th style="width:6%">操作</th>
                    </tr>
                </thead>
                @php
                    // 只顯示可派遣(open)的訂單
                    $editableOrders = collect($orders)->filter(function($order) {
                        return $order->status === 'open';
                    });
                @endphp
                <tbody>
                    @forelse($editableOrders as $order)
                    <tr class="order-row" data-order-id="{{ $order->id }}" data-carpool-group="{{ $order->carpool_group_id }}">
                        <td class="text-center align-middle">
                            <input type="checkbox" class="order-checkbox form-check-input" value="{{ $order->id }}">
                        </td>
                        <td>{{ $order->order_type }}</td>
                        <td>{{ $order->customer_name }}<br>{{ $order->customer_phone }}</td>
                        <td>{{ $order->ride_date ? (is_string($order->ride_date) ? $order->ride_date : $order->ride_date->format('Y-m-d')) : 'N/A' }}</td>
                        <td data-order="{{ $order->match_time ? $order->match_time->format('H:i') : ($order->ride_time ? \Illuminate\Support\Carbon::parse($order->ride_time)->format('H:i') : '99:99') }}">
                            @if($order->match_time)
                                <div class="d-flex align-items-center gap-1">
                                    <span>{{ $order->match_time->format('H:i') }}</span>
                                    <span class="badge bg-dark">搓合</span>
                                </div>
                            @else
                                {{ $order->ride_time ? \Illuminate\Support\Carbon::parse($order->ride_time)->format('H:i') : 'N/A' }}
                            @endif
                        </td>
                        <td>{{ Str::limit($order->pickup_address, 255) }}<br>{{ Str::limit($order->dropoff_address, 255) }}</td>
                        <td>{{ $order->carpool_name ?? '-' }}</td>
                        <td>
                            @if($order->stair_machine === '是')
                                <span class="badge bg-warning text-dark">爬梯機</span>
                            @elseif($order->stair_machine === '未知')
                                <span class="badge bg-secondary">爬梯機未知</span>
                            @endif
                            @if($order->wheelchair === '是')
                                <span class="badge bg-info text-dark">輪椅</span>
                            @elseif($order->wheelchair === '未知')
                                <span class="badge bg-secondary">輪椅未知</span>
                            @endif
                            @switch($order->special_status)
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
                                @case('共成單')
                                    <span class="badge bg-primary">共成單</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="ps-2">
                            @switch($order->status)
                                @case('open')
                                    <span class="badge bg-success">可派遣</span>
                                    @break
                                @case('assigned')
                                    <span class="badge bg-primary">已指派</span>
                                    @break
                                @case('bkorder')
                                    <span class="badge bg-warning text-dark">已候補</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">已取消</span>
                                    @break
                                @case('cancelledOOC')
                                    <span class="badge bg-danger">已取消-9999</span>
                                    @break
                                @case('cancelledNOC')
                                    <span class="badge bg-danger">取消!</span>
                                    @break
                                @case('cancelledCOTD')
                                    <span class="badge bg-danger">取消X</span>
                                    @break
                                @case('blocked')
                                    <span class="badge bg-success">無人承接</span>
                                    @break
                                @case('blacklist')
                                    <span class="badge bg-dark">黑名單</span>
                                    @break
                                @case('no_send')
                                    <span class="badge bg-danger">不派遣</span>
                                    @break
                                @case('regular_sedans')
                                    <span class="badge bg-info text-dark">一般車</span>
                                    @break
                                @case('no_car')
                                    <span class="badge bg-info text-dark">傳沒車</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">未知</span>
                            @endswitch
                        </td>
                        <td>
                            <a href="{{ route('orders.show', array_merge(['order' => $order], request()->only(['keyword', 'start_date', 'end_date', 'customer_id', 'order_type', 'stair_machine']))) }}" class="btn btn-info btn-sm" title="檢視">
                                <i class="fas fa-eye"></i>
                            </a>
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

{{-- 批次編輯 Modal --}}
<div class="modal fade" id="batchEditModal" tabindex="-1" aria-labelledby="batchEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="batchEditModalLabel">
                    <i class="fas fa-edit me-2"></i>批次編輯訂單
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="batchEditForm">
                @csrf
                <div class="modal-body">
                    <!-- 已選訂單提示 -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        已選擇 <strong><span id="selectedCount">0</span></strong> 筆訂單
                        <span id="carpoolWarning" class="text-warning" style="display:none;">
                            <br><i class="fas fa-exclamation-triangle me-1"></i>含有共乘訂單，請確認群組同步更新
                        </span>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>提示：</strong>留空的欄位不會被更新。
                    </div>

                    <!-- 9 項可編輯欄位 -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_ride_time" class="form-label">
                                <i class="fas fa-clock me-1"></i>用車時間
                            </label>
                            <input type="text" name="ride_time" id="edit_ride_time" class="form-control" placeholder="例如 08:30 或 0830" maxlength="5">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_customer_phone" class="form-label">
                                <i class="fas fa-phone me-1"></i>客戶電話
                            </label>
                            <input type="text" name="customer_phone" id="edit_customer_phone" class="form-control" placeholder="例如 0912345678">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_pickup_address" class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>上車地址
                        </label>
                        <div class="input-group">
                            <input type="text" name="pickup_address" id="edit_pickup_address" class="form-control address-field landmark-input" placeholder="輸入地址或使用*觸發地標搜尋">
                            <button type="button" class="btn btn-outline-secondary" onclick="openLandmarkModal('edit_pickup')">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">地址格式有誤（須含縣市/區域）</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_dropoff_address" class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i>下車地址
                        </label>
                        <div class="input-group">
                            <input type="text" name="dropoff_address" id="edit_dropoff_address" class="form-control address-field landmark-input" placeholder="輸入地址或使用*觸發地標搜尋">
                            <button type="button" class="btn btn-outline-secondary" onclick="openLandmarkModal('edit_dropoff')">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">地址格式有誤（須含縣市/區域）</div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_status" class="form-label">
                                <i class="fas fa-info-circle me-1"></i>訂單狀態
                            </label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="">-- 不更新 --</option>
                                <option value="open">可派遣</option>
                                <option value="assigned">已指派</option>
                                <option value="bkorder">已候補</option>
                                <option value="blocked">無人承接</option>
                                <option value="cancelled">已取消</option>
                                <option value="cancelledOOC">已取消-9999</option>
                                <option value="cancelledNOC">取消!</option>
                                <option value="cancelledCOTD">取消X</option>
                                <option value="blacklist">黑名單</option>
                                <option value="no_send">不派遣</option>
                                <option value="regular_sedans">一般車</option>
                                <option value="no_car">傳沒車</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_special_status" class="form-label">
                                <i class="fas fa-star me-1"></i>特殊狀態
                            </label>
                            <select name="special_status" id="edit_special_status" class="form-select">
                                <option value="">-- 不更新 --</option>
                                <option value="網頁單">網頁單</option>
                                <option value="Line">Line</option>
                                <option value="個管單">個管單</option>
                                <option value="黑名單">黑名單</option>
                                <option value="共乘單">共乘單</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_wheelchair" class="form-label">
                                <i class="fas fa-wheelchair me-1"></i>輪椅
                            </label>
                            <select name="wheelchair" id="edit_wheelchair" class="form-select">
                                <option value="">-- 不更新 --</option>
                                <option value="是">是</option>
                                <option value="否">否</option>
                                <option value="未知">未知</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_stair_machine" class="form-label">
                                <i class="fas fa-dolly me-1"></i>爬梯機
                            </label>
                            <select name="stair_machine" id="edit_stair_machine" class="form-select">
                                <option value="">-- 不更新 --</option>
                                <option value="是">是</option>
                                <option value="否">否</option>
                                <option value="未知">未知</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_remark" class="form-label">
                            <i class="fas fa-comment me-1"></i>訂單備註
                        </label>
                        <textarea name="remark" id="edit_remark" class="form-control" rows="3" maxlength="1000" placeholder="輸入訂單備註..."></textarea>
                        <small class="text-muted">（最多 1000 字）</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>取消
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check me-2"></i>確認更新
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- JavaScript 批次編輯功能 --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 已選訂單ID
    let selectedOrders = new Set();

    // 全選 checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedOrders();
        });
    }

    // 單筆選取 checkbox
    document.querySelectorAll('.order-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedOrders();

            // 更新全選狀態
            const allCheckboxes = document.querySelectorAll('.order-checkbox');
            const checkedCheckboxes = document.querySelectorAll('.order-checkbox:checked');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
            }
        });
    });

    // 更新已選訂單
    function updateSelectedOrders() {
        selectedOrders.clear();
        document.querySelectorAll('.order-checkbox:checked').forEach(checkbox => {
            selectedOrders.add(checkbox.value);
        });

        const count = selectedOrders.size;
        document.getElementById('selectedCountText').textContent = count;
        document.getElementById('selectedCount').textContent = count;

        // 顯示/隱藏 Badge 與禁用按鈕
        const badge = document.getElementById('selectedCountBadge');
        const btn = document.getElementById('batchEditBtn');

        if (count > 0) {
            badge.style.display = 'inline-block';
            btn.disabled = false;
        } else {
            badge.style.display = 'none';
            btn.disabled = true;
        }

        // 檢查共乘訂單
        checkCarpoolOrders();
    }

    // 檢查是否包含共乘訂單
    function checkCarpoolOrders() {
        let hasCarpoolOrders = false;
        document.querySelectorAll('.order-checkbox:checked').forEach(checkbox => {
            const row = checkbox.closest('tr');
            const carpoolGroup = row.dataset.carpoolGroup;
            if (carpoolGroup && carpoolGroup !== '' && carpoolGroup !== 'null') {
                hasCarpoolOrders = true;
            }
        });

        const warning = document.getElementById('carpoolWarning');
        if (warning) {
            warning.style.display = hasCarpoolOrders ? 'block' : 'none';
        }
    }

    // 地址簡易驗證
    document.querySelectorAll('.address-field').forEach(field => {
        field.addEventListener('blur', function() {
            const regex = /(.+?(市|縣).+?(區|鎮|鄉).+)/;
            const value = this.value.trim();

            if (value === '') {
                // 空白表示不更新
                this.classList.remove('is-invalid');
                this.classList.remove('is-valid');
            } else if (regex.test(value)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });
    });

    // 搭乘時間：HHMM 轉 HH:MM
    const timeInput = document.getElementById('edit_ride_time');
    if (timeInput) {
        timeInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/g, ''); // 只允許數字
            if (value.length === 4) {
                const hours = value.substring(0, 2);
                const minutes = value.substring(2, 4);
                e.target.value = `${hours}:${minutes}`;
            }
        });
    }

    // 表單提交
    const batchEditForm = document.getElementById('batchEditForm');
    if (batchEditForm) {
        batchEditForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // 驗證是否有選取
            if (selectedOrders.size === 0) {
                alert('請至少選擇 1 筆訂單');
                return;
            }

            // 驗證地址格式
            let hasInvalidAddress = false;
            document.querySelectorAll('.address-field').forEach(field => {
                if (field.classList.contains('is-invalid')) {
                    hasInvalidAddress = true;
                }
            });

            if (hasInvalidAddress) {
                alert('請修正地址格式後再送出');
                return;
            }

            // 構建資料
            const formData = new FormData(this);
            const data = {
                order_ids: Array.from(selectedOrders)
            };

            // 只傳有填寫的欄位
            for (let [key, value] of formData.entries()) {
                if (value && value.trim() !== '' && key !== '_token') {
                    data[key] = value.trim();
                }
            }

            // 顯示 Loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>更新中...';

            // 發送 AJAX 請求
            fetch('/orders/batch-edit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('成功：' + result.message);
                    location.reload();
                } else {
                    alert('失敗：' + (result.message || '批次更新失敗'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            })
            .catch(error => {
                console.error('批次更新失敗:', error);
                alert('批次更新失敗，請稍後再試');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            });
        });
    }

    // Modal 關閉時重置表單
    const modalElement = document.getElementById('batchEditModal');
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function() {
            batchEditForm.reset();
            document.querySelectorAll('.address-field').forEach(field => {
                field.classList.remove('is-invalid');
                field.classList.remove('is-valid');
            });
        });
    }

    // DataTables 自訂排序：使用 data-order 屬性
    $.fn.dataTable.ext.order['dom-data-order'] = function(settings, col) {
        return this.api().column(col, {order: 'index'}).nodes().map(function(td) {
            return $(td).attr('data-order') || '99:99';
        });
    };

    // 將初始化函式露給全局 index.blade.php 使用
    if (!window.orderIndex) {
        window.orderIndex = {};
    }

    let editTableInitialized = false;

    window.orderIndex.initializeEditTable = function() {
        // 如果已初始化，僅重繪
        if (editTableInitialized && $.fn.DataTable.isDataTable('#ordersTableEdit')) {
            $('#ordersTableEdit').DataTable().draw();
            return;
        }

        const $table = $('#ordersTableEdit');
        if ($table.length === 0) {
            console.log('表格不存在，跳過初始化');
            return;
        }

        // 確認有實際資料列（排除 colspan/提示列）
        const $tbody = $table.find('tbody');
        const dataRows = $tbody.find('tr').filter(function() {
            return $(this).find('td[colspan]').length === 0 && !$(this).hasClass('no-data-row');
        });

        console.log('檢測到資料行數量:', dataRows.length);

        if (dataRows.length === 0) {
            console.warn('無資料行，跳過 DataTable 初始化');
            return;
        }

        // 先清除舊實例
        if ($.fn.DataTable.isDataTable('#ordersTableEdit')) {
            $('#ordersTableEdit').DataTable().destroy();
        }

        const editTable = $('#ordersTableEdit').DataTable({
            language: {
                processing: "處理中...",
                loadingRecords: "載入中...",
                lengthMenu: "顯示 _MENU_ 筆資料",
                zeroRecords: "沒有符合的資料",
                info: "顯示第 _START_ 至 _END_ 筆，共 _TOTAL_ 筆",
                infoEmpty: "顯示第 0 至 0 筆，共 0 筆",
                infoFiltered: "(從 _MAX_ 筆資料中篩選)",
                search: "搜尋：",
                paginate: {
                    first: "第一頁",
                    previous: "上一頁",
                    next: "下一頁",
                    last: "最後一頁"
                }
            },
            order: [[3, 'asc'], [4, 'asc']], // 預設按日期、時間排序
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "全部"]],
            columnDefs: [
                { orderable: false, targets: [0, 9] }, // 勾選框與操作欄不可排序
                { type: 'date', targets: 3 }, // 日期欄
                { orderDataType: 'dom-data-order', targets: 4 } // 時間欄使用 data-order
            ],
            responsive: true,
            searching: true,
            paging: true,
            info: true,
            autoWidth: false,
            destroy: true,
            drawCallback: function() {
                // DataTables 繪製後重新綁定 checkbox 事件
                document.querySelectorAll('.order-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        updateSelectedOrders();

                        // 更新全選狀態
                        const allCheckboxes = document.querySelectorAll('.order-checkbox');
                        const checkedCheckboxes = document.querySelectorAll('.order-checkbox:checked');
                        if (selectAllCheckbox) {
                            selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
                        }
                    });
                });
            }
        });

        window.orderIndex.editTable = editTable;
        editTableInitialized = true;
        console.log('多編模式 DataTable 初始化完成');
    };

    // 預設初始化一次
    window.orderIndex.initializeEditTable();
});
</script>
