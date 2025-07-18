@extends('layouts.app')




@section('content')
<div class="container-fluid">

<div class="card">
    <div class="container-fluid">
        <h3 class="mt-3">個案查詢</h3>

        {{-- 🔍 客戶搜尋欄 --}}
        <form method="GET" action="{{ route('orders.index') }}" class="mb-3" style="width:100%">
            <div class="input-group" style="width:100%">
                <input type="text" name="keyword" class="form-control" placeholder="輸入姓名、電話或身分證字號查詢客戶"
                    value="{{ request('keyword') }}">
                <button class="btn btn-primary" type="submit">搜尋客戶</button>
            </div>
        </form>

        {{-- 🔍 若有搜尋，則根據結果數量顯示不同內容 --}}
        @if(request()->filled('keyword') || request()->filled('customer_id'))

            @if($customers->isEmpty())
                <div class="alert alert-warning">查無符合的客戶資料</div>

            @elseif($customers->count() > 1)
                {{-- 結果 > 1，顯示選擇列表 --}}
                <div class="alert alert-info">找到多筆符合資料，請選擇一位客戶：</div>
                <ul class="list-group">
                    @foreach($customers as $customer)
                        <li class="list-group-item">
                            <a href="{{ route('orders.index', ['customer_id' => $customer->id, 'keyword' => request('keyword')]) }}">
                                {{ $customer->name }} / {{ $customer->id_number }} / {{ is_array($customer->phone_number) ? $customer->phone_number[0] : $customer->phone_number }} / {{ is_array($customer->addresses) ? $customer->addresses[0] : $customer->addressess }}
                            </a>
                        </li>
                    @endforeach
                </ul>

            @else
                {{-- 結果 = 1，顯示詳細資料表 --}}
                @php $customer = $customers->first(); @endphp
                <table class="table table-bordered">
                    <thead class="table-success">
                        <tr>
                            <th>訂單來源</th>
                            <th>姓名</th>
                            <th>身分證字號</th>
                            <th>電話</th>
                            <th>住址</th>
                            <th>身份別</th>
                            <th>可服務車隊</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $customer->county_care }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->id_number }}</td>
                            <td>{{ is_array($customer->phone_number) ? implode(' / ', $customer->phone_number) : $customer->phone_number }}</td>
                            <td>{{ is_array($customer->addresses) ? implode(' / ', $customer->addresses) : $customer->addresses }}</td>
                            <td>{{ $customer->identity }}</td>
                            <td>{{ $customer->service_company }}</td>
                            <td>
                                <a href="{{ route('orders.create', ['customer_id' => $customer->id, 'keyword' => request('keyword')]) }}" class="btn btn-sm btn-success">
                                    建立訂單
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="row ml-3 mt-3">
                    <div class="col-md-2">
                        狀態：
                        @if(in_array($customer->status, ['暫停中', '已結案']))
                            <span class="h4 text-danger">{{ $customer->status }}</span>
                        @else
                            {{ $customer->status }}
                        @endif
                    </div>
                    <div class="col-md-2">
                        特殊狀態：
                        @if(in_array($customer->special_status, ['黑名單', 'VIP']))
                            <span class="h4 text-danger">{{ $customer->special_status }}</span>
                        @else
                            {{ $customer->special_status }}
                        @endif
                    </div>
                    <div class="col-md-8">乘客備註：{{ $customer->note }}</div>
                </div>
            @endif
            <hr>
        @endif

        {{-- 📋 之後可以放訂單列表（目前不顯示） --}}
        {{-- <table>...</table> --}}
    </div>
</div>


<div class="card">
    <div class="row mt-3">
        <div class="col-md-4">
            <h3 class="mt-2 ml-2">訂單列表</h3>
        </div>
        <div class="col-md-8">
            {{-- 訂單日期區間篩選 --}}
            <form method="GET" action="{{ route('orders.index') }}" class="row g-2 mb-0 align-items-end">
                {{-- 開始日期 --}}
                <div class="col-auto">
                    <label for="start_date" class="form-label mb-0">開始日期</label>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="{{ request('start_date') ?? \Carbon\Carbon::today()->toDateString() }}"
                        class="form-control">
                </div>

                {{-- 結束日期 --}}
                <div class="col-auto">
                    <label for="end_date" class="form-label mb-0">結束日期</label>
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="{{ request('end_date') ?? \Carbon\Carbon::now()->endOfMonth()->toDateString() }}"
                        class="form-control">
                </div>

                {{-- 若有客戶關鍵字也要保留 --}}
                @if(request('keyword'))
                    <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                @endif

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">日期區間</button>
                </div>
            </form>

        </div>
    </div>

    <hr style="background-color: black; height: 2px; border: none;" class="mb-0">

    {{-- 顯示訂單日期區間 --}}


    {{-- 顯示成功訊息 --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif


    {{-- 建立新訂單按鈕 --}}
    <!--<div class="mb-3 text-end">
        <a href="{{ route('orders.create') }}" class="btn btn-primary">＋ 新增訂單</a>
    </div>-->

    {{-- 訂單資料表格 --}}
    <div id="orders-list" class="table-responsive p-3">
        <table id="order-table" class="table table-bordered table-hover align-middle" style="width:100%">
            <thead class="table-success">
                <tr>
                    <th class="align-middle text-center" style="width:5%">客戶姓名</th>
                    <th class="align-middle text-center" style="width:5%">用車日期</th>
                    <th class="align-middle text-center" style="width:5%">用車時間</th>
                    <th class="align-middle text-center" style="width:20%">上車地址</th>
                    <th class="align-middle text-center" style="width:20%">下車地址</th>
                    <th class="align-middle text-center" style="width:6%">爬梯機</th>
                    <th class="align-middle text-center" style="width:5%">特殊單</th>
                    <th class="align-middle text-center" style="width:5%">車隊編號</th>
                    <th class="align-middle text-center" style="width:5%">訂單狀態</th>
                    <th class="align-middle text-center" style="width:12%">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->ride_date ? \Carbon\Carbon::parse($order->ride_date)->format('m/d') : 'N/A' }}</td>
                    <td>{{ $order->ride_time ? \Carbon\Carbon::parse($order->ride_time)->format('H:i') : 'N/A' }}</td>
                    <td>{{ $order->pickup_address }}</td>
                    <td>{{ $order->dropoff_address }}</td>
                    <td>
                        @if($order->stair_machine == 1)
                            爬梯單
                        @endif
                    </td>
                    <td>
                        @switch($order->special_status)
                        @case('一般')
                            <span class="badge bg-success">一般</span>
                            @break
                        @case('VIP')
                            <span class="badge bg-pink">VIP</span>
                            @break
                        @case('個管單')
                            <span class="badge bg-pink">個管單</span>
                            @break
                        @default
                            <span class="badge bg-light text-dark" >未知狀態</span>
                    @endswitch
                    </td>
                    <td>{{ $order->driver_fleet_number }}</td>
                    <td>
                        @switch($order->status)
                            @case('open')
                                <span class="badge bg-success">可派遣</span>
                                @break
                            @case('assigned')
                                <span class="badge bg-primary">已指派</span>
                                @break
                            @case('bkorder')
                                <span class="badge bg-warning">已後補</span>
                                @break
                            @case('blocked')
                                <span class="badge bg-danger">黑名單</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger">已取消</span>
                                @break
                            @default
                                <span class="badge bg-light text-dark">未知狀態</span>
                        @endswitch
                    </td>
                    <td>
                        <button type="button" class="btn btn-info btn-sm view-order-btn" data-order-id="{{ $order->id }}">檢視</button>
                        <button type="button" class="btn btn-sm btn-primary edit-order-btn" data-id="{{ $order->id }}">編輯</button>
                        {{-- 刪除按鈕可以之後再補上 --}}
                    </td>
                </tr>
                @empty

                @endforelse
            </tbody>
        </table>
    </div>

</div>

</div>
@endsection

@push('scripts')
<script>
function initOrderTable() {
    // 先檢查是否已經初始化過 DataTable，若有則先銷毀
    if ($.fn.DataTable.isDataTable('#order-table')) {
        $('#order-table').DataTable().destroy();
    }
    $('#order-table').DataTable({
        language: {
            lengthMenu: "每頁顯示 _MENU_ 筆資料",
            zeroRecords: "查無資料",
            info: "顯示第 _START_ 到 _END_ 筆，共 _TOTAL_ 筆資料",
            infoEmpty: "目前沒有資料",
            infoFiltered: "(從 _MAX_ 筆資料中篩選)",
            search: "快速搜尋：",
            paginate: {
                first: "第一頁",
                last: "最後一頁",
                next: "下一頁",
                previous: "上一頁"
            }
        },
            order: [[1, 'asc']],
            pageLength: 100,  // 預設每頁顯示 100 筆資料
    });
}

function handleOrderFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const modalElement = form.closest('.modal'); // 動態尋找父層的 modal
    if (!form.classList.contains('orderForm') || !modalElement) return;
    
    // 檢查是否正在進行地標選擇，如果是則阻止提交
    if (form.hasAttribute('data-landmark-selecting')) {
        console.log('地標選擇中，阻止表單提交');
        return false;
    }

    // Temporarily enable any disabled fields so their values are captured
    const disabledFields = form.querySelectorAll(':disabled');
    disabledFields.forEach(field => field.disabled = false);

    const formData = new FormData(form);

    // Restore disabled state
    disabledFields.forEach(field => field.disabled = true);

    // 將 keyword 和日期區間加入 formData
    const keyword = document.querySelector('input[name="keyword"]').value;
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;

    if (keyword) {
        formData.append('keyword', keyword);
    }
    if (startDate) {
        formData.append('start_date', startDate);
    }
    if (endDate) {
        formData.append('end_date', endDate);
    }

    fetch(form.action, {
        method: 'POST', // Laravel 會透過 _method 欄位自動處理 PUT/PATCH
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    }).then(response => {
        if (response.status === 422) {
            return response.json().then(data => ({ status: 422, data: data.html }));
        }
        return response.text().then(html => ({ status: response.status, data: html }));
    }).then(res => {
        if (res.status === 422) {
            // 驗證失敗，將包含錯誤訊息的表單內容填回 modal body
            const contentContainer = modalElement.querySelector('#editOrderContent') || modalElement.querySelector('.modal-body');
            if (contentContainer) {
                contentContainer.innerHTML = res.data;
            }
        } else {
            // 成功，更新訂單列表並關閉 modal
            if ($.fn.DataTable.isDataTable('#order-table')) {
                $('#order-table').DataTable().destroy();
            }
            // 後端應回傳更新後的整個表格 HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = res.data;
            const newTable = tempDiv.querySelector('#order-table');
            const oldTable = document.getElementById('order-table');
            if (newTable && oldTable) {
                oldTable.parentNode.replaceChild(newTable, oldTable);
            } else {
                // 如果回傳的不是完整的 table，則直接更新列表區域
                $('#orders-list').html(res.data);
            }

            initOrderTable(); // 重新初始化 DataTable
            form.reset();
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide(); // 關閉當前的 modal
            }
        }
    }).catch(error => {
        console.error('表單提交錯誤:', error);
        alert('發生錯誤，請稍後再試');
    });
}

$(document).ready(function () {
    initOrderTable();
    initializeCategoryFilters();
    
    // 使用事件委派，為所有透過 AJAX 載入的 .orderForm 表單綁定提交流程
    $(document).on('submit', '.orderForm', handleOrderFormSubmit);
    
    // 分頁切換事件
    $(document).on('shown.bs.tab', 'button[data-bs-toggle="pill"]', function (e) {
        const target = $(e.target).data('bs-target');
        const dropdown = $(e.target).closest('.landmark-dropdown');
        
        if (target.includes('popular-content')) {
            const container = dropdown.find('.landmark-popular');
            loadPopularLandmarks(container);
        } else if (target.includes('recent-content')) {
            const container = dropdown.find('.landmark-recent');
            loadRecentLandmarks(container);
        }
    });
    
    // 地標選擇器打開時載入預設內容
    $(document).on('shown.bs.dropdown', '.landmark-dropdown', function () {
        const dropdown = $(this);
        const searchResults = dropdown.find('.landmark-results');
        
        // 如果搜尋結果為空，顯示提示
        if (searchResults.is(':empty')) {
            searchResults.html(`
                <div class="text-center py-4">
                    <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">請輸入關鍵字搜尋地標</p>
                    <small class="text-muted">或直接在地址欄輸入關鍵字加上 * 觸發搜尋</small>
                </div>
            `);
        }
    });
});

// 全選 / 取消全選
$('#select-all').click(function () {
    $('input[name="ids[]"]').prop('checked', this.checked);
});

// 檢視訂單詳細資料
$(document).on('click', '.view-order-btn', function() {
    var orderId = $(this).data('order-id');
    var url = "{{ url('orders') }}/" + orderId;

    $('#orderDetailContent').html('載入中...');
    $('#orderDetailModal').modal('show');

    $.get(url, function(data) {
        $('#orderDetailContent').html(data);
    });
});

// 建立訂單功能已改為頁面式，不再需要 Modal 處理

// 修改訂單
$(document).on('click', '.edit-order-btn', function() {
    const orderId = $(this).data('id');
    const url = '/orders/' + orderId + '/edit';
    const contentContainer = $('#editOrderContent');

    contentContainer.html('<div class="text-center py-3">載入中...</div>');
    $('#editOrderModal').modal('show');

    $.get(url, function(data) {
        contentContainer.html(data);
        // 重新初始化地標功能
        initializeLandmarkInputsInModal();
    });
});

// --- 從 form.blade.php 移過來的 scripts ---

// 共乘查詢
$(document).on('click', '#searchCarpoolBtn', function () {
    const keyword = $('#carpoolSearchInput').val();
    fetch(`/carpool-search?keyword=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(data => {
            const resultsDiv = $('#carpoolResults');
            resultsDiv.html('');

            if (data.length === 0) {
                resultsDiv.html('<div class="text-danger">查無資料</div>');
                return;
            }

            if (data.length === 1 && data[0].id_number === keyword) {
                $('#carpool_with').val(data[0].name);
                $('#carpool_id_number').val(data[0].id_number);
                $('#carpool_phone_number').val(Array.isArray(data[0].phone_number) ? data[0].phone_number[0] : data[0].phone_number);
                $('#carpool_addresses').val(data[0].addresses);
                resultsDiv.html('');
                return;
            }

            const list = $('<ul>').addClass('list-group');
            data.forEach(c => {
                const item = $('<li>').addClass('list-group-item d-flex justify-content-between align-items-center');
                item.html(`
                    <div class="row w-100 align-items-center">
                        <div class="col-md-1 d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-success select-carpool-btn">選擇</button>
                        </div>
                        <div class="col-md-11 d-flex align-items-center">
                            <strong>${c.name}</strong> / ${(Array.isArray(c.phone_number) ? c.phone_number[0] : c.phone_number)} / ${c.id_number} / ${c.addresses}
                        </div>
                    </div>
                `);
                item.find('.select-carpool-btn').on('click', () => {
                    $('#carpoolSearchInput').val(c.name);
                    $('#carpool_with').val(c.name);
                    $('#carpool_id_number').val(c.id_number);
                    $('#carpool_phone_number').val(Array.isArray(c.phone_number) ? c.phone_number[0] : c.phone_number);
                    $('#carpool_addresses').val(c.addresses[0]);
                    $('#carpool_customer_id').val(c.id);
                    resultsDiv.html('');
                });
                list.append(item);
            });
            resultsDiv.append(list);
        })
        .catch(error => {
            console.error('查詢錯誤：', error);
            $('#carpoolResults').html('<div class="text-danger">查詢失敗，請稍後再試</div>');
        });
});

// 清除共乘
$(document).on('click', '#clearCarpoolBtn', function () {
    $('#carpoolSearchInput').val('');
    $('#carpool_with').val('');
    $('#carpool_id_number').val('');
    $('#carpool_phone_number').val('');
    $('#carpool_addresses').val('');
    $('#carpool_customer_id').val('');
    $('#carpoolResults').html('');
});

// 交換地址
$(document).on('click', '#swapAddressBtn', function () {
    const pickupInput = $('input[name="pickup_address"]');
    const dropoffInput = $('input[name="dropoff_address"]');
    const temp = pickupInput.val();
    pickupInput.val(dropoffInput.val());
    dropoffInput.val(temp);
});

// 駕駛查詢
$(document).on('click', '#searchDriverBtn', function () {
    const fleetNumber = $('#driver_fleet_number').val();
    if (!fleetNumber) return;

    fetch(`/drivers/fleet-search?fleet_number=${encodeURIComponent(fleetNumber)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            $('#driver_id').val(data.id);
            $('#driver_name').val(data.name);
            $('#driver_plate_number').val(data.plate_number);
        })
        .catch(() => {
            alert('查詢失敗，請稍後再試');
        });
});

// 清除駕駛
$(document).on('click', '#clearDriverBtn', function () {
    $('#driver_fleet_number').val('');
    $('#driver_id').val('');
    $('#driver_name').val('');
    $('#driver_plate_number').val('');
    const statusSelect = $('select[name="status"]');
    if (statusSelect) {
        statusSelect.val('open');
        statusSelect.prop('readonly', false);
    }
});

// 監聽隊編輸入
$(document).on('input', '#driver_fleet_number', function() {
    const statusSelect = $('select[name="status"]');
    if ($(this).val().trim() !== '') {
        statusSelect.val('assigned');
        statusSelect.prop('disabled', true);
    } else {
        statusSelect.val('open');
        statusSelect.prop('disabled', false);
    }
});

</script>
@endpush

<!-- 訂單檢視Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="orderDetailModalLabel">訂單詳細資料</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
        </div>
        <div class="modal-body">
          <!-- AJAX會把資料放這 -->
          <div id="orderDetailContent">載入中...</div>
        </div>
      </div>
    </div>
  </div>

<!-- 編輯訂單 Modal - 優化版本 -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content shadow-lg border-0">
        <div class="modal-header bg-gradient-primary text-white">
          <div class="d-flex align-items-center">
            <i class="fas fa-edit me-3 fs-4"></i>
            <div>
              <h5 class="modal-title mb-0" id="editOrderModalLabel">編輯訂單</h5>
              <small class="text-light opacity-75">修改訂單資訊</small>
            </div>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="關閉"></button>
        </div>
        <div class="modal-body p-0">
          {{-- AJAX載入表單 --}}
          <div id="editOrderContent">
            <div class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">載入中...</span>
              </div>
              <p class="mt-3 text-muted">載入訂單資料中...</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- 建立訂單 Modal 已移除，改為頁面式 -->

<script>
// 初始化 Modal 中的地標功能（使用 Dropdown）
function initializeLandmarkInputsInModal() {
    console.log('初始化 Modal 中的地標功能');
    // 等待 DOM 載入完成
    setTimeout(() => {
        const modalElement = document.querySelector('#createOrderModal, #editOrderModal');
        if (modalElement) {
            console.log('找到 Modal 元素');
            // 重新綁定 dropdown 事件
            bindLandmarkDropdownEvents();
        }
    }, 100);
}

function bindLandmarkDropdownEvents() {
    // 處理 * 觸發搜尋
    const landmarkInputs = document.querySelectorAll('.landmark-input');
    landmarkInputs.forEach(input => {
        // 移除舊的監聽器
        input.removeEventListener('input', handleLandmarkInput);
        // 添加新的監聽器
        input.addEventListener('input', handleLandmarkInput);
    });
    
    // 綁定 dropdown 搜尋按鈕
    document.querySelectorAll('.landmark-search-btn').forEach(btn => {
        btn.removeEventListener('click', handleLandmarkSearch);
        btn.addEventListener('click', handleLandmarkSearch);
    });
}

function handleLandmarkInput(e) {
    const inputValue = e.target.value;
    if (inputValue.includes('*')) {
        // 移除星號並觸發搜尋
        const keyword = inputValue.replace('*', '');
        e.target.value = keyword;
        
        // 開啟對應的 dropdown 並搜尋
        const dropdown = e.target.closest('.landmark-input-group').querySelector('.dropdown');
        if (dropdown) {
            const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
            const searchInput = dropdown.querySelector('.landmark-search-input');
            
            // 設定搜尋關鍵字
            searchInput.value = keyword;
            
            // 開啟 dropdown
            const bsDropdown = new bootstrap.Dropdown(dropdownToggle);
            bsDropdown.show();
            
            // 執行搜尋
            setTimeout(() => {
                searchLandmarksInDropdown(dropdown, keyword);
            }, 100);
        }
    }
}

function handleLandmarkSearch() {
    const dropdown = this.closest('.dropdown');
    const searchInput = dropdown.querySelector('.landmark-search-input');
    const keyword = searchInput.value.trim();
    
    if (keyword) {
        searchLandmarksInDropdown(dropdown, keyword);
    }
}

// 在 dropdown 中搜尋地標
function searchLandmarksInDropdown(dropdown, keyword) {
    const resultsContainer = dropdown.querySelector('.landmark-results');
    resultsContainer.innerHTML = '<div class="text-center py-2">搜尋中...</div>';
    
    fetch(`/landmarks-search?keyword=${encodeURIComponent(keyword)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayLandmarkResults(resultsContainer, data.data, dropdown);
            } else {
                resultsContainer.innerHTML = '<div class="text-muted py-2">查無符合條件的地標</div>';
            }
        })
        .catch(error => {
            console.error('搜尋地標錯誤:', error);
            resultsContainer.innerHTML = '<div class="text-danger py-2">搜尋失敗，請稍後再試</div>';
        });
}

// 顯示搜尋結果 - 優化版本
function displayLandmarkResults(container, landmarks, dropdown) {
    if (!landmarks || landmarks.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0">查無符合條件的地標</p>
                <small class="text-muted">請嘗試其他關鍵字或分類</small>
            </div>
        `;
        return;
    }
    
    let html = '';
    
    landmarks.forEach((landmark, index) => {
        const fullAddress = landmark.city + landmark.district + landmark.address;
        const categoryBadge = getCategoryBadge(landmark.category);
        const categoryIcon = getCategoryIcon(landmark.category);
        const usageCount = landmark.usage_count || 0;
        const isPopular = usageCount >= 10;
        
        html += `
            <div class="landmark-item border rounded-3 mb-2 p-3 landmark-card" 
                 style="cursor: pointer; transition: all 0.3s ease;" 
                 data-category="${landmark.category}"
                 onclick="selectLandmarkFromDropdown('${fullAddress}', ${landmark.id}, this)"
                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'">
                <div class="d-flex align-items-start">
                    <div class="landmark-icon me-3 d-flex align-items-center justify-content-center rounded-circle bg-light" style="width: 40px; height: 40px;">
                        <i class="${categoryIcon} text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="mb-1 fw-bold text-dark">
                                ${landmark.name}
                                ${isPopular ? '<i class="fas fa-fire text-warning ms-1" title="熱門地標"></i>' : ''}
                            </h6>
                            <div class="d-flex align-items-center">
                                ${categoryBadge}
                                <small class="text-muted ms-2">
                                    <i class="fas fa-chart-bar me-1"></i>${usageCount}次
                                </small>
                            </div>
                        </div>
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-map-marker-alt me-1"></i>${fullAddress}
                        </p>
                        ${landmark.description ? `<small class="text-muted">${landmark.description}</small>` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// 獲取分類標籤
function getCategoryBadge(category) {
    const categories = {
        'medical': { text: '醫療', class: 'bg-danger' },
        'transport': { text: '交通', class: 'bg-primary' },
        'education': { text: '教育', class: 'bg-success' },
        'government': { text: '政府機關', class: 'bg-warning' },
        'commercial': { text: '商業', class: 'bg-info' },
        'general': { text: '一般', class: 'bg-secondary' }
    };
    
    const cat = categories[category] || { text: category, class: 'bg-secondary' };
    return `<span class="badge ${cat.class} rounded-pill">${cat.text}</span>`;
}

// 獲取分類圖標
function getCategoryIcon(category) {
    const icons = {
        'medical': 'fas fa-hospital',
        'transport': 'fas fa-bus',
        'education': 'fas fa-school',
        'government': 'fas fa-building',
        'commercial': 'fas fa-store',
        'general': 'fas fa-map-marker-alt'
    };
    
    return icons[category] || 'fas fa-map-marker-alt';
}

// 分類篩選功能
function initializeCategoryFilters() {
    $(document).on('click', '.category-filter', function() {
        const dropdown = $(this).closest('.landmark-dropdown');
        const category = $(this).data('category');
        
        // 更新按鈕狀態
        dropdown.find('.category-filter').removeClass('active');
        $(this).addClass('active');
        
        // 篩選結果
        const allItems = dropdown.find('.landmark-item');
        
        if (category === 'all') {
            allItems.show();
        } else {
            allItems.hide();
            allItems.filter(`[data-category="${category}"]`).show();
        }
        
        // 檢查是否有結果
        const visibleItems = dropdown.find('.landmark-item:visible');
        const resultsContainer = dropdown.find('.landmark-results');
        
        if (visibleItems.length === 0) {
            resultsContainer.append(`
                <div class="text-center py-4 no-results">
                    <i class="fas fa-filter text-muted mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">此分類下暫無地標</p>
                    <small class="text-muted">請選擇其他分類</small>
                </div>
            `);
        } else {
            resultsContainer.find('.no-results').remove();
        }
    });
}

// 載入熱門地標
function loadPopularLandmarks(container) {
    container.html(`
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">載入中...</span>
            </div>
            <p class="mt-2 text-muted">載入熱門地標...</p>
        </div>
    `);
    
    fetch('/landmarks-popular')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayLandmarkResults(container[0], data.data);
            } else {
                container.html(`
                    <div class="text-center py-4">
                        <i class="fas fa-fire text-muted mb-2" style="font-size: 2rem;"></i>
                        <p class="text-muted mb-0">暫無熱門地標</p>
                        <small class="text-muted">地標需要有一定使用次數才會顯示</small>
                    </div>
                `);
            }
        })
        .catch(error => {
            console.error('載入熱門地標失敗:', error);
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">載入失敗</p>
                    <small class="text-muted">請稍後再試</small>
                </div>
            `);
        });
}

// 載入最近使用的地標
function loadRecentLandmarks(container) {
    container.html(`
        <div class="text-center py-3">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">載入中...</span>
            </div>
            <p class="mt-2 text-muted">載入最近使用...</p>
        </div>
    `);
    
    // 從 localStorage 獲取最近使用的地標
    const recentLandmarks = JSON.parse(localStorage.getItem('recentLandmarks') || '[]');
    
    if (recentLandmarks.length > 0) {
        // 獲取最近使用地標的詳細資訊
        const landmarkIds = recentLandmarks.slice(0, 10).map(item => item.id);
        
        fetch('/landmarks-by-ids', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ids: landmarkIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayLandmarkResults(container[0], data.data);
            } else {
                showNoRecentMessage(container);
            }
        })
        .catch(error => {
            console.error('載入最近使用地標失敗:', error);
            showNoRecentMessage(container);
        });
    } else {
        showNoRecentMessage(container);
    }
}

function showNoRecentMessage(container) {
    container.html(`
        <div class="text-center py-4">
            <i class="fas fa-history text-muted mb-2" style="font-size: 2rem;"></i>
            <p class="text-muted mb-0">暫無最近使用記錄</p>
            <small class="text-muted">使用地標後會顯示在這裡</small>
        </div>
    `);
}

// 選擇地標（從 dropdown）
function selectLandmarkFromDropdown(address, landmarkId, element) {
    const dropdown = element.closest('.dropdown');
    const inputGroup = dropdown.closest('.landmark-input-group');
    const targetInput = inputGroup.querySelector('.landmark-input');
    
    // 填入地址
    targetInput.value = address;
    
    // 儲存地標 ID
    targetInput.setAttribute('data-landmark-id', landmarkId);
    
    // 添加選中效果
    $(element).addClass('bg-success text-white').fadeOut(100).fadeIn(100);
    
    // 關閉 dropdown
    const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
    const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
    if (bsDropdown) {
        setTimeout(() => {
            bsDropdown.hide();
        }, 300);
    }
    
    // 更新使用次數
    fetch('/landmarks-usage', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ landmark_id: landmarkId })
    }).catch(error => {
        console.error('更新地標使用次數失敗:', error);
    });
    
    // 保存到最近使用
    saveToRecentLandmarks(landmarkId, address);
}

// 保存到最近使用地標
function saveToRecentLandmarks(landmarkId, address) {
    let recentLandmarks = JSON.parse(localStorage.getItem('recentLandmarks') || '[]');
    
    // 移除重複項目
    recentLandmarks = recentLandmarks.filter(item => item.id !== landmarkId);
    
    // 添加到開頭
    recentLandmarks.unshift({
        id: landmarkId,
        address: address,
        timestamp: new Date().getTime()
    });
    
    // 只保留最近 20 個
    recentLandmarks = recentLandmarks.slice(0, 20);
    
    localStorage.setItem('recentLandmarks', JSON.stringify(recentLandmarks));
}
</script>