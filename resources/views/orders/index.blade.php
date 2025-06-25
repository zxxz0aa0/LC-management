@extends('layouts.app')




@section('content')
<div class="container-fluid">

<div class="card">
    <div class="container-fluid">
        <h3 class="mt-2">個案查詢</h3>

        {{-- 🔍 客戶搜尋欄 --}}
        <form method="GET" action="{{ route('orders.index') }}" class="mb-3">
            <div class="input-group">
                <input type="text" name="keyword" class="form-control" placeholder="輸入姓名、電話或身分證字號查詢客戶"
                    value="{{ request('keyword') }}">
                <button class="btn btn-primary" type="submit">搜尋客戶</button>
            </div>
        </form>

        {{-- 🔍 若有搜尋，顯示客戶資料表 --}}
        @if(request()->filled('keyword'))
            
            @if($customers->isEmpty())
                <div class="alert alert-warning">查無符合的客戶資料</div>
            @else
                <table class="table table-bordered">
                    <thead>
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
                        @foreach($customers as $customer)
                        <tr>
                            <td>{{ $customer->county_care }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->id_number }}</td>
                            <!--顯示第一支電話-->
                            <td>{{ $customer->phone_number[0]}}</td>
                            <!--可顯示全部電話-->
                            <!--<td>{{ is_array($customer->phone_number) ? implode(' / ', $customer->phone_number) : $customer->phone_number }}</td><-->
                            <td>{{ is_array($customer->addresses) ? implode(' / ', $customer->addresses) : $customer->addresses }}
                            <td>{{ $customer->identity }}</td>
                            <td>{{ $customer->service_company }}</td>
                            <td>
                                {{-- 帶入 customer_id 前往建立訂單 --}}
                                <!--<a href="{{ route('orders.create', ['customer_id' => $customer->id,'id_number' => $customer->id_number]) }}" class="btn btn-sm btn-success">
                                    建立訂單
                                </a>-->
                                <!-- 觸發按鈕 -->
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createOrderModal">
                                    建立訂單
                                </button>
                                <!-- Modal 本體 -->
                                <div class="modal fade" id="createOrderModal" tabindex="-1" aria-labelledby="createOrderLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl"> {{-- 使用 modal-fullscreen --}}
                                        <div class="modal-content">
                                            <div class="modal-header ">
                                                <h5 class="modal-title" id="createOrderLabel">新增訂單</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
                                            </div>
                                            <div class="modal-body">
                                                @include('orders.partials.form', ['user' => auth()->user()]){{-- 把 create 表單抽出來成共用 --}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>                            
                        @endforeach
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
    <h3 class="mt-2 ml-2">訂單列表</h3>

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
        <table id="order-table" class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th>編號</th>
                    <th>客戶姓名</th>
                    <th>用車日期</th>
                    <th>特殊狀態</th>
                    <th>訂單狀態</th>
                    <th>建單人員</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->ride_date }}</td>
                    <td>{{ $order->special_order }}</td>
                    <td>{{ $order->status }}</td>
                    <td>{{ $order->created_by }}</td>
                    <td>
                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-info">檢視</a>
                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-warning">編輯</a>
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

const orderForm = document.getElementById('orderForm');
if (orderForm) {
    orderForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        }).then(response => response.text())
          .then(html => {
                if ($.fn.DataTable.isDataTable('#order-table')) {
                  $('#order-table').DataTable().destroy();
                  }
              document.getElementById('orders-list').innerHTML = html; // 👈 更新訂單表格
                initOrderTable();
              form.reset(); // 清空表單
              const modalInstance = bootstrap.Modal.getInstance(document.getElementById('createOrderModal'));
              if (modalInstance) {
                  modalInstance.hide(); // 關閉 modal
              }
          }).catch(error => {
              console.error(error);
              alert('發生錯誤，請稍後再試');
          });
    });
}
</script>
<script>
    $(document).ready(function () {
    initOrderTable();
    });

    // 全選 / 取消全選
    $('#select-all').click(function () {
        $('input[name="ids[]"]').prop('checked', this.checked);
    });
</script>
@endpush
