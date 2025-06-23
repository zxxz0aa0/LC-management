@extends('layouts.app')

@section('content')
<div class="container-fluid">

<div class="card">
    <div class="container-fluid">
        <h3 class="mb-4">個案查詢</h3>

        {{-- 🔍 客戶搜尋欄 --}}
        <form method="GET" action="{{ route('orders.index') }}" class="mb-4">
            <div class="input-group">
                <input type="text" name="keyword" class="form-control" placeholder="輸入姓名、電話或身分證字號查詢客戶"
                    value="{{ request('keyword') }}">
                <button class="btn btn-primary" type="submit">搜尋客戶</button>
            </div>
        </form>

        {{-- 🔍 若有搜尋，顯示客戶資料表 --}}
        @if(request()->filled('keyword'))
            <h5>搜尋結果：</h5>
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
                            <td>
                                {{-- 帶入 customer_id 前往建立訂單 --}}
                                <a href="{{ route('orders.create', [
                                'customer_id' => $customer->id,
                                'id_number' => $customer->id_number
                                ]) }}" class="btn btn-sm btn-success">
                                    建立訂單
                                </a>
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
    <h3 class="mb-4">訂單列表</h3>

    {{-- 顯示成功訊息 --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    
    {{-- 建立新訂單按鈕 --}}
    <!--<div class="mb-3 text-end">
        <a href="{{ route('orders.create') }}" class="btn btn-primary">＋ 新增訂單</a>
    </div>-->

    {{-- 訂單資料表格 --}}
    <div class="table-responsive">
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
