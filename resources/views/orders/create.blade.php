@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">新增訂單</h3>

    <form method="POST" action="{{ route('orders.store') }}">
        @csrf

        {{-- 基本資料 --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <label>建單人員</label>
                <input type="text" name="created_by" class="form-control" 
                    value="{{ $user->name }}" readonly>
            </div>
        </div>

        {{-- 客戶資訊 --}}
        <h5 class="mt-4">客戶資訊</h5>
        <div class="row mb-3">
            <div class="col-md-4 mt-3">
                <label>姓名</label>
                <input type="text" name="customer_name" class="form-control"
                    value="{{ old('customer_name', $customer->name ?? '') }}" readonly>
            </div>
            <div class="col-md-4 mt-3">
                <label>身分證字號</label>
                <input type="text" name="customer_id_number" class="form-control"
                    value="{{ old('customer_id_number', $customer->id_number ?? '') }}" readonly>
            </div>
            <div class="col-md-4 mt-3">
                <label>電話</label>
                <input type="text" name="customer_phone" class="form-control"
                    value="{{ old('customer_phone', $customer->phone_number[0] ?? '') }}">
            </div>
            <div class="col-md-4 mt-3">
                <label>身份別</label>
                <input type="text" name="identity" class="form-control"
                    value="{{ old('identity', $customer->identity ?? '') }}" readonly>
            </div>
            <div>
               <!--可再放一個-->
            </div>
            <div class="col-md-4 mt-3">
                <label>共乘對象</label>
                <div class="input-group">
                    <input type="text" name="carpool_with" id="carpool_with" class="form-control" placeholder="點選右側按鈕查詢" readonly onfocus="this.blur();">
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#carpoolModal">
                        查詢個案
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="clearCarpoolBtn">
                        清除
                    </button>
                </div>
            </div>

                <!-- Modale共乘對象 -->
                <div class="modal fade" id="carpoolModal" tabindex="-1" aria-labelledby="carpoolModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="carpoolModalLabel">查詢共乘對象</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
                    </div>
                    <div class="modal-body">
                        {{-- 查詢欄 --}}
                        <div class="input-group mb-3">
                        <input type="text" id="carpoolSearchInput" class="form-control" placeholder="輸入姓名、身分證字號、電話查詢">
                        <button class="btn btn-primary" type="button" id="searchCarpoolBtn">搜尋</button>
                        </div>

                        {{-- 查詢結果 --}}
                        <div id="carpoolResults"></div>
                    </div>
                    </div>
                </div>
                </div>
            <div class="col-md-4 mt-3">
                <label>共乘身分證字號</label>
                <div class="input-group">
                    <input type="text" name="carpool_id_number" id="carpool_id_number" class="form-control" placeholder="點選右側按鈕查詢" readonly onfocus="this.blur();">
                </div>
            </div>
            <div class="col-md-4 mt-3">
                <label>共乘電話</label>
                <div class="input-group">
                    <input type="text" name="carpool_phone_number" id="carpool_phone_number" class="form-control" placeholder="點選右側按鈕查詢" readonly onfocus="this.blur();">
                </div>
            </div>   
            <div class="col-md-12 mt-3">
                <label>共乘乘客地址</label>
                <div class="input-group">
                    <input type="text" name="carpool_addresses" id="carpool_addresses" class="form-control" placeholder="點選右側按鈕查詢" readonly onfocus="this.blur();">
                </div>
            </div>
        </div>

        {{-- 駕駛資訊 --}}
        <h5 class="mt-4">駕駛資訊</h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <label>駕駛 ID</label>
                <input type="number" name="driver_id" class="form-control">
            </div>
            <div class="col-md-4">
                <label>駕駛姓名</label>
                <input type="text" name="driver_name" class="form-control">
            </div>
            <div class="col-md-4">
                <label>車牌號碼</label>
                <input type="text" name="driver_plate_number" class="form-control">
            </div>
        </div>

        {{-- 用車資訊 --}}
        <h5 class="mt-4">用車資訊</h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <label>用車日期</label>
                <input type="date" name="ride_date" class="form-control">
            </div>
            <div class="col-md-4">
                <label>用車時間（格式： 時:分）</label>
                <input type="text" name="ride_time" class="form-control"
                    pattern="^([01]\d|2[0-3]):[0-5]\d$"
                    placeholder="例如：13:45"
                    value="{{ old('ride_time', $order->ride_time ?? '') }}">
            </div>
            <div class="col-md-4">
                <label>訂單狀態</label>
                <select name="status" class="form-select">
                    <option value="open">可派遣</option>
                    <option value="assigned">已指派</option>
                    <option value="replacement">候補派遣</option>
                    <option value="blocked">黑名單</option>
                    <option value="cancelled">已取消</option>
                </select>
            </div>
        </div>

        {{-- 上車資訊 --}}
        <h5 class="mt-4">上車地點</h5>
        <div class="row mb-3">
            <div class="col-md-12">
                <label>地址 (要有XX市XX區)</label>
                <input type="text" name="pickup_address" class="form-control"
                    value="{{ old('pickup_address', $customer->addresses[0] ?? '') }}">
            </div>
        </div>

        {{-- 下車資訊 --}}
        <div class="row">
        <h5 class="col-md-4 mt-4">下車地點</h5>
        {{-- 🚕 上下車地址交換按鈕 --}}
        <div class="col-md-4 mt-4">
            <button type="button" class="btn btn-outline-info" id="swapAddressBtn">
                交換上下車地址
            </button>
        </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12">
                <label>地址  (要有XX市XX區)</label>
                <input type="text" name="dropoff_address" class="form-control">
            </div>
        </div>

        {{-- 特殊需求 --}}
        <h5 class="mt-4">乘車需求</h5>
        <div class="row mb-3">
            <div class="col-md-4">
                <label>是否需要輪椅</label>
                <select name="wheelchair" class="form-select">
                    <option value="0" {{ old('wheelchair', $customer->wheelchair ?? false) ? '' : 'selected' }}>否</option>
                    <option value="1" {{ old('wheelchair', $customer->wheelchair ?? false) ? 'selected' : '' }}>是</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>是否需要爬梯機</label>
                <select name="stair_machine" class="form-select">
                    <option value="0" {{ old('wheelchair', $customer->stair_climbing_machine ?? false) ? '' : 'selected' }}>否</option>
                    <option value="1" {{ old('wheelchair', $customer->stair_climbing_machine ?? false) ? 'selected' : '' }}>是</option>
                </select>
            </div>
            <div class="col-md-4">
                <label>陪同人數</label>
                <input type="number" name="companions" class="form-control" min="0">
            </div>
        </div>

        {{-- 額外資訊 --}}
        <div class="mb-3">
            <label>訂單類型</label>
            <input type="text" name="order_type" class="form-control"
                value="{{ old('order_type', $customer->county_care ?? '') }}">
            
        </div>
        <div class="mb-3">
            <label>服務單位</label>
            <input type="text" name="service_company" class="form-control"
                 value="{{ old('service_company', $customer->service_company ?? '') }}">
        </div>
        <div class="mb-3">
            <label>是否為特別訂單</label>
            <select name="special_order" class="form-select">
                <option value="0">否</option>
                <option value="1">是</option>
            </select>
        </div>
        <div class="mb-3">
            <label>訂單備註</label>
            <textarea name="remark" rows="3" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label>乘客備註</label>
            <p>{{ old('remark2', $customer->note ?? '') }}</p>
        </div>

        {{-- 提交按鈕 --}}
        <div class="text-end">
            <button type="submit" class="btn btn-success">送出訂單</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('searchCarpoolBtn').addEventListener('click', function () {
    const keyword = document.getElementById('carpoolSearchInput').value;

    fetch(`/carpool-search?keyword=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(data => {
            const resultsDiv = document.getElementById('carpoolResults');
            resultsDiv.innerHTML = '';

            if (data.length === 0) {
                resultsDiv.innerHTML = '<div class="text-danger">查無資料</div>';
                return;
            }

            // 如果是唯一身分證號，就直接帶入
            if (data.length === 1 && data[0].id_number === keyword) {
                document.getElementById('carpool_with').value = data[0].name;
                document.getElementById('carpool_addresses').value = c.addresses;
                bootstrap.Modal.getInstance(document.getElementById('carpoolModal')).hide();
                return;
            }

            // 否則列出選擇清單
            const list = document.createElement('ul');
            list.className = 'list-group';

            data.forEach(c => {
                const item = document.createElement('li');
                item.className = 'list-group-item d-flex justify-content-between align-items-center';
                item.innerHTML = `
                    <div>
                        <strong>${c.name}</strong> / ${(Array.isArray(c.phone_number) ? c.phone_number[0] : c.phone_number)} / ${c.id_number}/ ${c.addresses}
                    </div>
                    <button type="button" class="btn btn-sm btn-success">選擇</button>
                `;

                item.querySelector('button').addEventListener('click', () => {
                    document.getElementById('carpool_with').value = c.name;
                    document.getElementById('carpool_id_number').value = c.id_number;
                    document.getElementById('carpool_phone_number').value = (Array.isArray(c.phone_number) ? c.phone_number[0] : c.phone_number);
                    document.getElementById('carpool_addresses').value = c.addresses;
                    bootstrap.Modal.getInstance(document.getElementById('carpoolModal')).hide();
                });

                list.appendChild(item);
            });

            resultsDiv.appendChild(list);
        })
        .catch(error => {
            console.error('查詢錯誤：', error);
            document.getElementById('carpoolResults').innerHTML = '<div class="text-danger">查詢失敗，請稍後再試</div>';
        });
});

// ✅ 清除按鈕功能
document.getElementById('clearCarpoolBtn').addEventListener('click', function () {
    document.getElementById('carpool_with').value = '';
});
</script>

<!--交換上下車地址按鈕功能-->
<script>
document.getElementById('swapAddressBtn').addEventListener('click', function () {
    const pickupInput = document.querySelector('input[name="pickup_address"]');
    const dropoffInput = document.querySelector('input[name="dropoff_address"]');

    // 交換值
    const temp = pickupInput.value;
    pickupInput.value = dropoffInput.value;
    dropoffInput.value = temp;
});
</script>

@endpush

