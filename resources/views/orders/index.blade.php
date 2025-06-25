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
                            <th>來源</th>
                            <th>姓名</th>
                            <th>電話</th>
                            <th>身分證字號</th>
                            <th>住址</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                        <tr>
                            <td>{{ $customer->county_care }}</td>
                            <td>{{ $customer->name }}</td>
                            <!--顯示第一支電話-->
                            <td>{{ $customer->phone_number[0]}}</td>
                            <!--可顯示全部電話-->
                            <!--<td>{{ is_array($customer->phone_number) ? implode(' / ', $customer->phone_number) : $customer->phone_number }}</td><-->
                            <td>{{ $customer->id_number }}</td>
                            <td>{{ is_array($customer->addresses) ? implode(' / ', $customer->addresses) : $customer->addresses }}
                            <td>
                                {{-- 帶入 customer_id 前往建立訂單 --}}
                                <a href="{{ route('orders.create', [
                                'customer_id' => $customer->id,
                                'id_number' => $customer->id_number
                                ]) }}" class="btn btn-sm btn-success">
                                    建立訂單
                                </a>
                                <!-- 觸發按鈕 -->
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOrderModal">
                                    ＋ 建立新訂單
                                </button>
                                <!-- Modal 本體 -->
                                <div class="modal fade" id="createOrderModal" tabindex="-1" aria-labelledby="createOrderLabel" aria-hidden="true">
                                <div class="modal-dialog modal-xl"> {{-- 可用 modal-lg 或 modal-xl 放大 --}}
                                    <div class="modal-content">
                                    <div class="modal-header">
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
    <div id="orders-list" class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th>編號</th>
                    <th>客戶姓名</th>
                    <th>用車日期</th>
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
                    <td>{{ $order->status }}</td>
                    <td>{{ $order->created_by }}</td>
                    <td>
                        <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-info">檢視</a>
                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-sm btn-warning">編輯</a>
                        {{-- 刪除按鈕可以之後再補上 --}}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">目前尚無訂單資料</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
</div>

</div>
@endsection

@push('scripts')
<script>
const orderForm = document.getElementById('orderForm');
if (orderForm) {
    orderForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        }).then(response => response.text())
          .then(html => {
              document.getElementById('orders-list').innerHTML = html; // 👈 更新訂單表格
              form.reset(); // 清空表單
          }).catch(error => {
              console.error(error);
              alert('發生錯誤，請稍後再試');
          });
    });
}
</script>
@endpush
