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
                <option value="edit">多編模式</option>
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

    {{-- 訂單列表區塊 - 多編模式 --}}
    <div id="table-view-edit" style="display: none;">
        @include('orders.components.order-table-edit', ['orders' => $orders])
    </div>

    {{-- 地標選擇 Modal --}}
    @include('orders.components.landmark-modal')
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/orders/index.js') }}"></script>
    <script src="{{ asset('js/orders/form.js') }}"></script>

    {{-- 列表切換功能 --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewModeSelect = document.getElementById('tableViewMode');
        const fullView = document.getElementById('table-view-full');
        const searchView = document.getElementById('table-view-search');

        // 切換顯示模式
        viewModeSelect.addEventListener('change', function() {
            const mode = this.value;
            const editView = document.getElementById('table-view-edit');

            if (mode === 'full') {
                fullView.style.display = 'block';
                searchView.style.display = 'none';
                editView.style.display = 'none';

                // 觸發訂單模式表格的 DataTables 初始化
                if (window.orderIndex) {
                    window.orderIndex.reinitializeMainTable();
                }
            } else if (mode === 'search') {
                fullView.style.display = 'none';
                searchView.style.display = 'block';
                editView.style.display = 'none';

                // 觸發爬梯模式表格的 DataTables 初始化
                if (window.orderIndex) {
                    window.orderIndex.initializeSearchTable();
                }
            } else if (mode === 'edit') {
                fullView.style.display = 'none';
                searchView.style.display = 'none';
                editView.style.display = 'block';

                // 觸發多編模式表格的 DataTables 初始化
                if (window.orderIndex && typeof window.orderIndex.initializeEditTable === 'function') {
                    window.orderIndex.initializeEditTable();
                }
            }
        });
    });
    </script>

    {{-- 駕駛快速指派功能 JavaScript --}}
    <script>
    $(document).ready(function() {
        // 駕駛搜尋
        $(document).on('click', '.search-driver-btn', function() {
            const $btn = $(this);
            const orderId = $btn.data('order-id');
            const $input = $(`.driver-fleet-input[data-order-id="${orderId}"]`);
            const fleetNumber = $input.val().trim();

            if (!fleetNumber) {
                alert('請輸入駕駛隊編');
                $input.focus();
                return;
            }

            // 禁用按鈕避免重複點擊
            $btn.prop('disabled', true);
            const originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');

            // 查詢駕駛資訊
            fetch(`/drivers/fleet-search?fleet_number=${encodeURIComponent(fleetNumber)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        $btn.prop('disabled', false);
                        $btn.html(originalHtml);
                        return;
                    }

                    // 更新訂單駕駛
                    updateOrderDriver(orderId, {
                        id: data.id,
                        name: data.name,
                        plate_number: data.plate_number,
                        fleet_number: fleetNumber
                    }, $btn, originalHtml);
                })
                .catch(error => {
                    alert('查詢失敗，請稍後再試');
                    console.error('駕駛搜尋錯誤:', error);
                    $btn.prop('disabled', false);
                    $btn.html(originalHtml);
                });
        });

        // 清除駕駛
        $(document).on('click', '.clear-driver-btn', function() {
            const $btn = $(this);
            const orderId = $btn.data('order-id');

            if (!confirm('確定要清除駕駛資訊嗎？訂單狀態將恢復為「可派遣」。')) {
                return;
            }

            // 禁用按鈕
            $btn.prop('disabled', true);
            const originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');

            updateOrderDriver(orderId, {
                id: null,
                name: null,
                plate_number: null,
                fleet_number: null
            }, $btn, originalHtml);
        });

        // 更新訂單駕駛（AJAX）
        function updateOrderDriver(orderId, driverData, $btn, originalBtnHtml) {
            fetch(`/orders/${orderId}/assign-driver`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    driver_id: driverData.id,
                    driver_name: driverData.name,
                    driver_plate_number: driverData.plate_number,
                    driver_fleet_number: driverData.fleet_number
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // 更新頁面顯示
                    const $input = $(`.driver-fleet-input[data-order-id="${orderId}"]`);
                    const $nameDisplay = $(`#driver-name-${orderId}`);

                    $input.val(data.driver_fleet_number || '');
                    $nameDisplay.text(data.driver_name || '');

                    // 更新清除按鈕顯示
                    const $clearBtn = $(`.clear-driver-btn[data-order-id="${orderId}"]`);
                    if (data.driver_id) {
                        // 有駕駛 - 顯示清除按鈕
                        if ($clearBtn.length === 0) {
                            const $searchBtn = $(`.search-driver-btn[data-order-id="${orderId}"]`);
                            $searchBtn.after(`
                                <button type="button"
                                        class="btn btn-danger btn-sm clear-driver-btn"
                                        data-order-id="${orderId}"
                                        title="清除駕駛">
                                    <i class="fas fa-times"></i>
                                </button>
                            `);
                        }
                    } else {
                        // 無駕駛 - 移除清除按鈕
                        $clearBtn.remove();
                    }

                    // 顯示成功訊息並重新整理頁面
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || '更新失敗');
                    // 恢復按鈕狀態
                    if ($btn) {
                        $btn.prop('disabled', false);
                        if (originalBtnHtml) {
                            $btn.html(originalBtnHtml);
                        }
                    }
                }
            })
            .catch(error => {
                alert('更新失敗，請稍後再試');
                console.error('駕駛指派錯誤:', error);
                // 恢復按鈕狀態
                if ($btn) {
                    $btn.prop('disabled', false);
                    if (originalBtnHtml) {
                        $btn.html(originalBtnHtml);
                    }
                }
            });
        }

        // Enter 鍵觸發搜尋
        $(document).on('keypress', '.driver-fleet-input', function(e) {
            if (e.which === 13) { // Enter 鍵
                e.preventDefault();
                const orderId = $(this).data('order-id');
                $(`.search-driver-btn[data-order-id="${orderId}"]`).click();
            }
        });
    });
    </script>
@endpush