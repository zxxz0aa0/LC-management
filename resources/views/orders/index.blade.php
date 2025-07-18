@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 客戶搜尋區塊 --}}
    @include('orders.components.customer-search')
    
    {{-- 訂單列表區塊 --}}
    @include('orders.components.order-table', ['orders' => $orders])
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/orders/index.js') }}"></script>
@endpush