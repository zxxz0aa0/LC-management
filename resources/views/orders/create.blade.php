@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-light text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="fas fa-plus me-2"></i>新增訂單
                </h4>
                <a href="{{ route('orders.index', $searchParams ?? []) }}" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left me-2"></i>返回列表
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('orders.components.order-form')
        </div>
    </div>
</div>

{{-- 地標選擇 Modal --}}
@include('orders.components.landmark-modal')

{{-- 歷史訂單選擇 Modal --}}
@include('orders.components.history-modal')
@endsection

@push('scripts')
    <script src="{{ asset('js/orders/form.js') }}"></script>
@endpush