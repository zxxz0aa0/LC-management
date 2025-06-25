    
    <form id="orderForm" method="POST" action="{{ route('orders.store') }}">
        @csrf

        <div class="row mb-7">

            <div class="h4 col-md-6 text-danger">
                <label class="form-label">訂單類型：</label>
                <span>{{ old('order_type', $customer->county_care ?? '') }}</span>
                <input type="hidden" name="order_type" value="{{ old('order_type', $customer->county_care ?? '') }}">
            </div>
        </div>
        <!--個案資料表ID-->
        <input type="hidden" name="customer_id" value="{{ old('customer_id', $customer->id ?? '') }}">
        
        <div class="card container-fluid" style="border:1px solid DodgerBlue;">
            {{-- 客戶資訊 --}}
            <!--<h5 class="mt-3 text-center">客戶資訊</h5>
            <hr style="border-top: 1px solid #000;">-->
            <div class="row mb-3">
                <div class="col-md-1 mt-3">
                    <label>個案姓名</label>
                    <input type="text" name="customer_name" class="form-control"
                        value="{{ old('customer_name', $customer->name ?? '') }}" readonly>
                </div>
                <div class="col-md-3 mt-3">
                    <label>個案身分證字號</label>
                    <input type="text" name="customer_id_number" class="form-control"
                        value="{{ old('customer_id_number', $customer->id_number ?? '') }}" readonly>
                </div>
                <div class="col-md-3 mt-3">
                    <label>個案電話</label>
                    <input type="text" name="customer_phone" class="form-control"
                        value="{{ old('customer_phone', $customer->phone_number[0] ?? '') }}">
                </div>
                <div class="col-md-3 mt-3">
                    <label>個案身份別</label>
                    <input type="text" name="identity" class="form-control"
                        value="{{ old('identity', $customer->identity ?? '') }}" readonly>
                </div>
                <div class="col-md-2 mt-3">
                <label>交通公司</label>
                    <input type="text" name="service_company" class="form-control text-primary"
                        value="{{ old('service_company', $customer->service_company ?? '') }}" readonly>
                 </div>
                <div class="col-md-3 mt-3">
                    <label>共乘對象</label>
                    <div class="input-group">
                        <input type="text" name="carpool_with" id="carpool_with" class="form-control" placeholder="" readonly onfocus="this.blur();">
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
                    
                <div class="col-md-2 mt-3">
                    <label>共乘身分證字號</label>
                    <div class="input-group">
                        <input type="text" name="carpool_id_number" id="carpool_id_number" class="form-control" placeholder="" readonly onfocus="this.blur();">
                    </div>
                </div>
                <div class="col-md-2 mt-3">
                    <label>共乘電話</label>
                    <div class="input-group">
                        <input type="text" name="carpool_phone_number" id="carpool_phone_number" class="form-control" placeholder="" readonly onfocus="this.blur();">
                    </div>
                </div>   
                <div class="col-md-5 mt-3">
                    <label>共乘乘客地址</label>
                    <div class="input-group">
                        <input type="text" name="carpool_addresses" id="carpool_addresses" class="form-control" placeholder="" readonly onfocus="this.blur();">
                    </div>
                </div>
            </div>
    </div>

    

        <div class="card container-fluid" style="border:1px solid Tomato;">
        {{-- 用車資訊 --}}
        <!--<h5 class="mt-3 text-center">用車資訊</h5>
        <hr style="border-top: 1px solid #000;">-->
        <div class="row mb-3 mt-3">
                <div class="col-md-3">
                    <label>用車日期</label>
                    <input type="date" name="ride_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label>用車時間（格式： 時:分）</label>
                    <input type="text" name="ride_time" class="form-control"
                        pattern="^([01]\d|2[0-3]):[0-5]\d$"
                        placeholder="例如：13:45"
                        value="{{ old('ride_time', $order->ride_time ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label>陪同人數</label>
                    <input type="number" name="companions" class="form-control" min="0">
                </div>
            
                <div class="col-md-2">
                    <label>是否需要輪椅</label>
                    <select name="wheelchair" class="form-select">
                        <option value="0" {{ old('wheelchair', $customer->wheelchair ?? false) ? '' : 'selected' }}>否</option>
                        <option value="1" {{ old('wheelchair', $customer->wheelchair ?? false) ? 'selected' : '' }}>是</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>是否需要爬梯機</label>
                    <select name="stair_machine" class="form-select">
                        <option value="0" {{ old('wheelchair', $customer->stair_climbing_machine ?? false) ? '' : 'selected' }}>否</option>
                        <option value="1" {{ old('wheelchair', $customer->stair_climbing_machine ?? false) ? 'selected' : '' }}>是</option>
                    </select>
                </div>


            {{-- 上車資訊 --}}
            <!--<h5 class="mt-4">上車地點</h5>-->
            <div class="row mb-3">
                <div class="col-md-12 mt-3">
                    <label>上車地址 (要有XX市XX區)</label>
                    <input type="text" name="pickup_address" class="form-control"
                        value="{{ old('pickup_address', $customer->addresses[0] ?? '') }}">
                </div>
            </div>

            {{-- 下車資訊 --}}
            <div class="row mb-0">
            <!--<h5 class="col-md-4 mt-4">下車地點</h5>-->

            {{-- 🚕 上下車地址交換按鈕 --}}
            <div class="col-md-12 mt-1 d-flex justify-content-center align-items-center">
                <button type="button" class="btn btn-outline-info" id="swapAddressBtn">
                交換上下車地址
                </button>
            </div>
            </div>
            <div class="row mb-2 mt-1">
                <div class="col-md-12">
                    <label>下車地址  (要有XX市XX區)</label>
                    <input type="text" name="dropoff_address" class="form-control">
                </div>
            </div>


            <div class="row">
                {{-- 額外資訊 --}}
                <div class="col-md-6 mb-3">
                    <label>是否為特別訂單</label>
                    <select name="special_order" class="form-select">
                        <option value="0">否</option>
                        <option value="1">是</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>特別狀態訂單 (說明:T9的粉紅色)</label>
                    <select name="special_status" class="form-select">
                        <option value="一般">一般</option>
                        <option value="個管單">個管單</option>
                        <option value="VIP">VIP</option>
                        <option value="黑名單">黑名單</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>訂單備註</label>
                    <textarea name="remark" rows="3" class="form-control"></textarea>
                </div>
                <div class="mb-1">
                    <label>乘客備註</label>
                    <p class="h5 text-danger">{{ old('remark2', $customer->note ?? '') }}</p>
                </div>
            </div>

        </div>

        {{-- 駕駛資訊 --}}
        <div class="row mb-3">
            <div class="col-md-4">
                <label>駕駛隊編</label>
                <div class="input-group">
                    <input type="text" id="fleet_number_input" class="form-control" placeholder="輸入隊編">
                    <button type="button" class="btn btn-success" id="searchDriverBtn">查詢</button>
                </div>
            </div>
            <div class="col-md-4">
                <label>駕駛姓名</label>
                <input type="text" name="driver_name" id="driver_name" class="form-control" readonly>
            </div>
            <div class="col-md-4">
                <label>車牌號碼</label>
                <input type="text" name="driver_plate_number" id="driver_plate_number" class="form-control" readonly>
            </div>
            {{-- 隱藏 driver_id --}}
            <input type="hidden" name="driver_id" id="driver_id">
        </div>

       

                {{-- 基本資料 --}}
        <div class="row mb-3">
            <div class="col-md-4 mt-3">
                <label>訂單狀態</label>
                <select name="status" class="form-select">
                    <option value="open">可派遣</option>
                    <option value="assigned">已指派</option>
                    <option value="replacement">候補派遣</option>
                    <option value="blocked">黑名單</option>
                    <option value="cancelled">已取消</option>
                </select>
            </div>
        
            <div class="col-md-4 mt-3">
                <label>建單人員</label>
                <input type="text" name="created_by" class="form-control" 
                    value="{{ $user->name }}" readonly>
            </div>
        </div>

                {{-- 提交按鈕 --}}
        <div class="mb-3 text-end">
            <button type="submit" class="btn btn-success">&#10004送出訂單&#128203;</button>
        </div>
    </form>



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
                document.getElementById('carpool_addresses').value = data[0].addresses;
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
            document.getElementById('carpool_id_number').value = '';
            document.getElementById('carpool_phone_number').value = '';
            document.getElementById('carpool_addresses').value = '';
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

<script>
document.getElementById('searchDriverBtn').addEventListener('click', function () {
    const fleetNumber = document.getElementById('fleet_number_input').value;

    if (!fleetNumber) return;

    fetch(`/drivers/fleet-search?fleet_number=${encodeURIComponent(fleetNumber)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            document.getElementById('driver_id').value = data.id;
            document.getElementById('driver_name').value = data.name;
            document.getElementById('driver_plate_number').value = data.plate_number;
        })
        .catch(() => {
            alert('查詢失敗，請稍後再試');
        });
});
</script>


@endpush