@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 頁面標題和麵包屑 --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">
                                <i class="fas fa-plus-circle"></i> 
                                建立新訂單
                            </h4>
                            <small class="text-light">
                                @if($customer)
                                    客戶：{{ $customer->name }} ({{ $customer->id_number }})
                                @else
                                    請填寫完整訂單資訊
                                @endif
                            </small>
                        </div>
                        <div>
                            <a href="{{ route('orders.index', ['keyword' => request('keyword')]) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> 返回訂單列表
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 表單區域 --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> 訂單資訊
                    </h5>
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