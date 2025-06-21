@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">訂單列表</h3>

    {{-- 顯示成功訊息 --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- 建立新訂單按鈕 --}}
    <div class="mb-3 text-end">
        <a href="{{ route('orders.create') }}" class="btn btn-primary">＋ 新增訂單</a>
    </div>

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
@endsection
