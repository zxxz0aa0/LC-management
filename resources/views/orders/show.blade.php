@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="fas fa-eye me-2"></i>訂單詳細資料 - {{ $order->order_number }}
                </h3>
                <div>
                    <a href="{{ route('orders.edit', $order) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit me-2"></i>編輯訂單
                    </a>
                    <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-2"></i>返回列表
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @include('orders.components.order-detail', ['order' => $order])
        </div>
    </div>
</div>
@endsection