@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">新增訂單</h3>

    {{-- 之後用來顯示錯誤訊息 --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 新增訂單表單 --}}
    <form method="POST" action="{{ route('orders.store') }}">
        @csrf

        <div class="row mb-3">
            <div class="col-md-6">
                <label for="order_number">訂單編號</label>
                <input type="text" name="order_number" class="form-control">
            </div>

            <div class="col-md-6">
                <label for="ride_date">用車日期</label>
                <input type="date" name="ride_date" class="form-control">
            </div>
        </div>

        {{-- 更多欄位待補：客戶、駕駛、上下車地點、備註、需求…等 --}}

        <div class="text-end">
            <button type="submit" class="btn btn-primary">建立訂單</button>
        </div>
    </form>
</div>
@endsection
