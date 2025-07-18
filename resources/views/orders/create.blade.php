@extends('layouts.app')

@section('content')
<div class="container-fluid">


    {{-- 表單區域 --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-light d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center flex-grow-1">
                        <h5 class="mb-0 me-3">
                            <i class="fas fa-edit"></i> 訂單資訊
                        </h5>
                        <!--<h6 class="mb-0 me-2">訂單類型</h6>-->
                        <span class="fw-bold text-primary">{{ old('order_type', $order->order_type ?? $customer->county_care ?? '') }}</span>
                    </div>
                    <input type="hidden" name="order_type" value="{{ old('order_type', $order->order_type ?? $customer->county_care ?? '') }}">
                    <div class="ms-auto">
                        <a href="{{ route('orders.index', ['keyword' => request('keyword')]) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> 返回訂單列表
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    {{-- 引入部分表單 --}}
                    @include('orders.partials.form')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.card {
    border: none;
    border-radius: 10px;
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
}

.btn {
    border-radius: 8px;
}

.form-control, .form-select {
    border-radius: 8px;
}

.shadow {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
}
</style>
@endpush