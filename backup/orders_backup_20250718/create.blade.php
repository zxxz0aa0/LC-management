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

@push('scripts')
<script>
$(document).ready(function() {
    // 共乘查詢
    $(document).on('click', '#searchCarpoolBtn', function () {
        const keyword = $('#carpoolSearchInput').val();
        fetch(`/carpool-search?keyword=${encodeURIComponent(keyword)}`)
            .then(res => res.json())
            .then(data => {
                const resultsDiv = $('#carpoolResults');
                resultsDiv.html('');

                if (data.length === 0) {
                    resultsDiv.html('<div class="text-danger">查無資料</div>');
                    return;
                }

                if (data.length === 1 && data[0].id_number === keyword) {
                    $('#carpool_with').val(data[0].name);
                    $('#carpool_id_number').val(data[0].id_number);
                    $('#carpool_phone_number').val(Array.isArray(data[0].phone_number) ? data[0].phone_number[0] : data[0].phone_number);
                    $('#carpool_addresses').val(data[0].addresses);
                    resultsDiv.html('');
                    return;
                }

                const list = $('<ul>').addClass('list-group');
                data.forEach(c => {
                    const item = $('<li>').addClass('list-group-item d-flex justify-content-between align-items-center');
                    item.html(`
                        <div class="row w-100 align-items-center">
                            <div class="col-md-1 d-flex align-items-center">
                                <button type="button" class="btn btn-sm btn-success select-carpool-btn">選擇</button>
                            </div>
                            <div class="col-md-11 d-flex align-items-center">
                                <strong>${c.name}</strong> / ${(Array.isArray(c.phone_number) ? c.phone_number[0] : c.phone_number)} / ${c.id_number} / ${c.addresses}
                            </div>
                        </div>
                    `);
                    item.find('.select-carpool-btn').on('click', () => {
                        $('#carpoolSearchInput').val(c.name);
                        $('#carpool_with').val(c.name);
                        $('#carpool_id_number').val(c.id_number);
                        $('#carpool_phone_number').val(Array.isArray(c.phone_number) ? c.phone_number[0] : c.phone_number);
                        $('#carpool_addresses').val(c.addresses[0]);
                        $('#carpool_customer_id').val(c.id);
                        resultsDiv.html('');
                    });
                    list.append(item);
                });
                resultsDiv.append(list);
            })
            .catch(error => {
                console.error('查詢錯誤：', error);
                $('#carpoolResults').html('<div class="text-danger">查詢失敗，請稍後再試</div>');
            });
    });

    // 清除共乘
    $(document).on('click', '#clearCarpoolBtn', function () {
        $('#carpoolSearchInput').val('');
        $('#carpool_with').val('');
        $('#carpool_id_number').val('');
        $('#carpool_phone_number').val('');
        $('#carpool_addresses').val('');
        $('#carpool_customer_id').val('');
        $('#carpoolResults').html('');
    });

    // 交換地址
    $(document).on('click', '#swapAddressBtn', function () {
        const pickupInput = $('input[name="pickup_address"]');
        const dropoffInput = $('input[name="dropoff_address"]');
        const temp = pickupInput.val();
        pickupInput.val(dropoffInput.val());
        dropoffInput.val(temp);
    });

    // 駕駛查詢
    $(document).on('click', '#searchDriverBtn', function () {
        const fleetNumber = $('#driver_fleet_number').val();
        if (!fleetNumber) return;

        fetch(`/drivers/fleet-search?fleet_number=${encodeURIComponent(fleetNumber)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                $('#driver_id').val(data.id);
                $('#driver_name').val(data.name);
                $('#driver_plate_number').val(data.plate_number);
            })
            .catch(() => {
                alert('查詢失敗，請稍後再試');
            });
    });

    // 清除駕駛
    $(document).on('click', '#clearDriverBtn', function () {
        $('#driver_fleet_number').val('');
        $('#driver_id').val('');
        $('#driver_name').val('');
        $('#driver_plate_number').val('');
        const statusSelect = $('select[name="status"]');
        if (statusSelect) {
            statusSelect.val('open');
            statusSelect.prop('readonly', false);
        }
    });

    // 監聽隊編輸入
    $(document).on('input', '#driver_fleet_number', function() {
        const statusSelect = $('select[name="status"]');
        if ($(this).val().trim() !== '') {
            statusSelect.val('assigned');
            statusSelect.prop('disabled', true);
        } else {
            statusSelect.val('open');
            statusSelect.prop('disabled', false);
        }
    });
});
</script>
@endpush