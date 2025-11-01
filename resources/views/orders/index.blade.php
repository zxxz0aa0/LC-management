@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 客戶搜尋區塊 --}}
    @include('orders.components.customer-search')

    {{-- 顯示模式切換 --}}
    <div class="mb-3 ps-2">
        <div class="d-flex align-items-center">
            <label class="me-2 mb-0">
                <i class="fas fa-eye me-1"></i>顯示模式：
            </label>
            <select id="tableViewMode" class="form-select form-select-sm" style="width: auto;">
                <option value="full">訂單模式</option>
                <option value="search">爬梯模式</option>
            </select>
        </div>
    </div>

    {{-- 訂單列表區塊 - 完整模式 --}}
    <div id="table-view-full">
        @include('orders.components.order-table', ['orders' => $orders])
    </div>

    {{-- 訂單列表區塊 - 搜尋模式 --}}
    <div id="table-view-search" style="display: none;">
        @include('orders.components.order-table-search', ['orders' => $orders])
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/orders/index.js') }}"></script>

    {{-- 列表切換功能 --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewModeSelect = document.getElementById('tableViewMode');
        const fullView = document.getElementById('table-view-full');
        const searchView = document.getElementById('table-view-search');

        // 切換顯示模式
        viewModeSelect.addEventListener('change', function() {
            const mode = this.value;

            if (mode === 'full') {
                fullView.style.display = 'block';
                searchView.style.display = 'none';

                // 觸發訂單模式表格的 DataTables 初始化
                if (window.orderIndex) {
                    window.orderIndex.reinitializeMainTable();
                }
            } else if (mode === 'search') {
                fullView.style.display = 'none';
                searchView.style.display = 'block';

                // 觸發爬梯模式表格的 DataTables 初始化
                if (window.orderIndex) {
                    window.orderIndex.initializeSearchTable();
                }
            }
        });
    });
    </script>
@endpush