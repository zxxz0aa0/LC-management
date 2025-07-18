@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="fas fa-edit me-2"></i>編輯訂單 - {{ $order->order_number }}
                </h3>
                <div>
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye me-2"></i>檢視詳細
                    </a>
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>返回列表
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @include('orders.components.order-form', ['order' => $order])
        </div>
    </div>
</div>

{{-- 地標選擇 Modal --}}
@include('orders.components.landmark-modal')
@endsection

@push('scripts')
    <script src="{{ asset('js/orders/form.js') }}"></script>
@endpush