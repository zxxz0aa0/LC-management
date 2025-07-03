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
                                <button class="btn btn-sm btn-success create-order-btn" data-customer-id="{{ $customer->id }}">
                                    建立訂單
                                </button>
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
                        value="{{ request('end_date') ?? \Carbon\Carbon::today()->toDateString() }}"
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
                            @case('replacement')
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
        }
    });
}

function handleOrderFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const modalElement = form.closest('.modal'); // 動態尋找父層的 modal
    if (!form.classList.contains('orderForm') || !modalElement) return;

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
    // 使用事件委派，為所有透過 AJAX 載入的 .orderForm 表單綁定提交流程
    $(document).on('submit', '.orderForm', handleOrderFormSubmit);
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

// 建立訂單
$(document).on('click', '.create-order-btn', function() {
    const customerId = $(this).data('customer-id');
    const url = '{{ route('orders.create') }}';
    const modalBody = $('#createOrderModal .modal-body');

    modalBody.html('<div class="text-center py-3">載入中...</div>');
    $('#createOrderModal').modal('show');

    $.get(url, { customer_id: customerId }, function(data) {
        modalBody.html(data);
    });
});

// 修改訂單
$(document).on('click', '.edit-order-btn', function() {
    const orderId = $(this).data('id');
    const url = '/orders/' + orderId + '/edit';
    const contentContainer = $('#editOrderContent');

    contentContainer.html('<div class="text-center py-3">載入中...</div>');
    $('#editOrderModal').modal('show');

    $.get(url, function(data) {
        contentContainer.html(data);
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

<!-- 編輯訂單 Modal -->
<div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editOrderModalLabel">編輯訂單</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
        </div>
        <div class="modal-body">
          {{-- AJAX載入表單 --}}
          <div id="editOrderContent">
            <div class="text-center py-3">
              載入中...
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- 建立訂單 Modal -->
<div class="modal fade" id="createOrderModal" tabindex="-1" aria-labelledby="createOrderLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createOrderLabel">新增訂單</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-3">載入中...</div>
            </div>
        </div>
    </div>
</div>