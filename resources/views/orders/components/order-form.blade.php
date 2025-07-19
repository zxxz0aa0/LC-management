<form method="POST" action="{{ isset($order) ? route('orders.update', $order) : route('orders.store') }}"
      class="order-form">
    @csrf
    @if(isset($order))
        @method('PUT')
    @endif

    {{-- 隱藏欄位 --}}
    <input type="hidden" name="customer_id" value="{{ old('customer_id', isset($order) ? $order->customer_id : ($customer->id ?? '')) }}">

    @if(request('keyword'))
        <input type="hidden" name="keyword" value="{{ request('keyword') }}">
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <h6>請修正以下錯誤：</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 客戶資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user me-2"></i>客戶資訊
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">電話</label>
                    <input type="text" name="customer_phone" class="form-control"
                        value="{{ old('customer_phone', isset($order) ? $order->customer_phone : '') }}" >
                </div>
                <div class="col-md-2">
                    <label class="form-label">客戶姓名</label>
                    <input type="text" name="customer_name" class="form-control"
                        value="{{ old('customer_name', isset($order) ? $order->customer_name : ($customer->name ?? '')) }}" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">身分證字號</label>
                    <input type="text" name="customer_id_number" class="form-control"
                        value="{{ old('customer_id_number', isset($order) ? $order->customer_id_number : ($customer->id_number ?? '')) }}" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">訂單類型</label>
                    <input type="text" name="order_type" class="form-control"
                        value="{{ old('order_type', isset($order) ? $order->order_type : ($customer->county_care ?? '')) }}" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">身份別</label>
                    <input type="text" name="identity" class="form-control"
                        value="{{ old('identity', isset($order) ? $order->identity : ($customer->identity ?? '')) }}" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">交通公司</label>
                    <input type="text" name="service_company" class="form-control"
                        value="{{ old('service_company', isset($order) ? $order->service_company : ($customer->service_company ?? '')) }}" readonly>
                </div>
                @if(old('special_status', isset($order) ? $order->special_status : ($customer->special_status ?? '')) == '黑名單')
                    <div class="col-md-12">
                        <input type="text" name="special_status" class="form-control bg-danger text-white text-center fs-1" style="height:100px;"
                            value="黑名單" readonly>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 用車資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-car me-2"></i>用車資訊
                </h5>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="historyOrderBtn"
                        style="display: none;" title="選擇歷史訂單快速填入">
                    <i class="fas fa-history me-1"></i>歷史訂單
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">用車日期</label>
                    <input type="date" name="ride_date" class="form-control" required
                           value="{{ old('ride_date', isset($order) ? $order->ride_date?->format('Y-m-d') : now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">用車時間</label>
                    <input type="text" name="ride_time" class="form-control time-auto-format" required
                           pattern="^([01]?[0-9]|2[0-3]):[0-5][0-9]$"
                           placeholder="直接輸入4位數字"
                           maxlength="5"
                           inputmode="numeric"
                           title="直接輸入4位數字，系統會自動格式化為 HH:MM"
                           value="{{ old('ride_time', isset($order) && $order->ride_time ? (strlen($order->ride_time) > 5 ? substr($order->ride_time, 0, 5) : $order->ride_time) : '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">回程時間</label>
                    <input type="text" name="back_time" class="form-control time-auto-format"
                           pattern="^([01]?[0-9]|2[0-3]):[0-5][0-9]$"
                           placeholder="例如1600"
                           maxlength="5"
                           inputmode="numeric"
                           title="直接輸入4位數字，系統會自動格式化為 HH:MM"
                           value="{{ old('back_time', '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">陪同人數</label>
                    <input type="number" name="companions" class="form-control" min="0"
                           value="{{ old('companions', isset($order) ? $order->companions : 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">輪椅</label>
                    <select name="wheelchair" class="form-select">
                        <option value="0" {{ old('wheelchair', isset($order) ? $order->wheelchair : 0) == 0 ? 'selected' : '' }}>否</option>
                        <option value="1" {{ old('wheelchair', isset($order) ? $order->wheelchair : 0) == 1 ? 'selected' : '' }}>是</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">爬梯機</label>
                    <select name="stair_machine" class="form-select">
                        <option value="0" {{ old('stair_machine', isset($order) ? $order->stair_machine : 0) == 0 ? 'selected' : '' }}>否</option>
                        <option value="1" {{ old('stair_machine', isset($order) ? $order->stair_machine : 0) == 1 ? 'selected' : '' }}>是</option>
                    </select>
                </div>
            </div>

            {{-- 地址資訊 --}}
            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label class="form-label">上車地址</label>
                    <div class="input-group">
                        <input type="text" name="pickup_address" id="pickup_address" class="form-control landmark-input" required
                               value="{{ old('pickup_address', isset($order) ? $order->pickup_address : '') }}"
                               placeholder="輸入地址或使用*觸發地標搜尋">
                        <button type="button" class="btn btn-outline-secondary" onclick="openLandmarkModal('pickup')">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 text-center">
                    <button type="button" class="btn btn-outline-info" id="swapAddressBtn">
                        <i class="fas fa-exchange-alt"></i> 交換地址
                    </button>
                </div>
                <div class="col-12">
                    <label class="form-label">下車地址</label>
                    <div class="input-group">
                        <input type="text" name="dropoff_address" id="dropoff_address" class="form-control landmark-input" required
                               value="{{ old('dropoff_address', isset($order) ? $order->dropoff_address : '') }}"
                               placeholder="輸入地址或使用*觸發地標搜尋">
                        <button type="button" class="btn btn-outline-secondary" onclick="openLandmarkModal('dropoff')">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 共乘資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>共乘資訊
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">共乘對象搜尋</label>
                    <div class="input-group">
                        <input type="text" id="carpoolSearchInput" class="form-control"
                               placeholder="輸入姓名、ID或電話">
                        <button type="button" class="btn btn-success" id="searchCarpoolBtn">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="clearCarpoolBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-8">
                    <div id="carpoolResults"></div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label class="form-label">共乘姓名</label>
                    <input type="text" name="carpool_with" id="carpool_with" class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">共乘身分證</label>
                    <input type="text" name="carpool_id_number" id="carpool_id_number" class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">共乘電話</label>
                    <input type="text" name="carpool_phone_number" id="carpool_phone_number" class="form-control" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">共乘地址</label>
                    <input type="text" name="carpool_addresses" id="carpool_addresses" class="form-control" readonly>
                </div>
            </div>
            <input type="hidden" name="carpool_customer_id" id="carpool_customer_id">
        </div>
    </div>

    {{-- 駕駛資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user-tie me-2"></i>駕駛資訊
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">駕駛隊編</label>
                    <div class="input-group">
                        <input type="text" name="driver_fleet_number" id="driver_fleet_number" class="form-control"
                               value="{{ old('driver_fleet_number', isset($order) ? $order->driver_fleet_number : '') }}">
                        <button type="button" class="btn btn-success" id="searchDriverBtn">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="clearDriverBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">駕駛姓名</label>
                    <input type="text" name="driver_name" id="driver_name" class="form-control" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">車牌號碼</label>
                    <input type="text" name="driver_plate_number" id="driver_plate_number" class="form-control" readonly>
                </div>
            </div>
            <input type="hidden" name="driver_id" id="driver_id">
        </div>
    </div>

    {{-- 其他資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i>其他資訊
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">特殊狀態</label>
                    <select name="special_status" class="form-select">
                        <option value="一般" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '一般' ? 'selected' : '' }}>一般</option>
                        <option value="網頁" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '網頁' ? 'selected' : '' }}>網頁</option>
                        <option value="個管單" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '個管單' ? 'selected' : '' }}>個管單</option>
                        <option value="黑名單" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '黑名單' ? 'selected' : '' }}>黑名單</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">訂單狀態</label>
                    <select name="status" class="form-select">
                        <option value="open" {{ old('status', isset($order) ? $order->status : 'open') == 'open' ? 'selected' : '' }}>可派遣</option>
                        <option value="assigned" {{ old('status', isset($order) ? $order->status : 'open') == 'assigned' ? 'selected' : '' }}>已指派</option>
                        <option value="replacement" {{ old('status', isset($order) ? $order->status : 'open') == 'replacement' ? 'selected' : '' }}>候補</option>
                        <option value="cancelled" {{ old('status', isset($order) ? $order->status : 'open') == 'cancelled' ? 'selected' : '' }}>已取消</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">建立者</label>
                    <input type="text" name="created_by" class="form-control"
                    value="{{ old('created_by', isset($order) ? $order->created_by : optional(auth()->user())->name) }}" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">訂單備註</label>
                    <textarea name="remark" class="form-control" rows="3">{{ old('remark', isset($order) ? $order->remark : '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- 提交按鈕 --}}
    <div class="text-center py-4">
        <button type="submit" class="btn btn-primary btn-lg px-5">
            <i class="fas fa-save me-2"></i>
            {{ isset($order) ? '更新訂單' : '建立訂單' }}
        </button>
    </div>
</form>