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

    {{-- 日期資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-calendar-alt me-2"></i>日期設定
            </h5>
        </div>
        <div class="card-body">
            {{-- 建立模式選擇（僅在新增訂單時顯示） --}}
            @if(!isset($order))
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">建立模式</label>
                    <div class="date-mode-selector">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="date_mode" id="single_day" value="single" checked>
                            <label class="btn btn-outline-primary" for="single_day">單日訂單</label>

                            <input type="radio" class="btn-check" name="date_mode" id="manual_multiple" value="manual">
                            <label class="btn btn-outline-primary" for="manual_multiple">手動多日</label>

                            <input type="radio" class="btn-check" name="date_mode" id="recurring" value="recurring">
                            <label class="btn btn-outline-primary" for="recurring">週期性</label>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- 單日模式 --}}
            <div id="single-date-section">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">用車日期</label>
                        <input type="date" name="ride_date" class="form-control" required
                               value="{{ old('ride_date', isset($order) ? $order->ride_date?->format('Y-m-d') : now()->format('Y-m-d')) }}">
                    </div>
                </div>
            </div>

            {{-- 手動多日選擇區域 --}}
            @if(!isset($order))
            <div id="manual-dates-section" style="display: none;" class="mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">選擇日期</label>
                        <input type="text" id="multiple-date-picker" class="form-control" placeholder="點擊選擇多個日期">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">已選擇的日期</label>
                        <div id="selected-dates-list" class="selected-dates-container">
                            <div class="text-muted">尚未選擇任何日期</div>
                        </div>
                    </div>
                </div>

                {{-- 預覽按鈕 --}}
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-primary" id="generate-manual-preview">
                        <i class="fas fa-eye me-2"></i>預覽批量訂單
                    </button>
                </div>
            </div>

            {{-- 週期性日期選擇區域 --}}
            <div id="recurring-dates-section" style="display: none;" class="mt-4">
                {{-- 日期範圍和重複週期 --}}
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">開始日期</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">結束日期</label>
                        <input type="date" name="end_date" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">重複週期</label>
                        <select name="recurrence_type" class="form-select">
                            <option value="weekly">每週</option>
                            <option value="biweekly">每兩週</option>
                            <option value="monthly">每月</option>
                        </select>
                    </div>
                </div>

                {{-- 星期幾複選 --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">選擇星期幾（可複選）</label>
                        <div class="weekday-selection">
                            <div class="btn-group flex-wrap" role="group">
                                <input type="checkbox" class="btn-check" id="weekday-1" name="weekdays[]" value="1">
                                <label class="btn btn-outline-primary" for="weekday-1">一</label>

                                <input type="checkbox" class="btn-check" id="weekday-2" name="weekdays[]" value="2">
                                <label class="btn btn-outline-primary" for="weekday-2">二</label>

                                <input type="checkbox" class="btn-check" id="weekday-3" name="weekdays[]" value="3">
                                <label class="btn btn-outline-primary" for="weekday-3">三</label>

                                <input type="checkbox" class="btn-check" id="weekday-4" name="weekdays[]" value="4">
                                <label class="btn btn-outline-primary" for="weekday-4">四</label>

                                <input type="checkbox" class="btn-check" id="weekday-5" name="weekdays[]" value="5">
                                <label class="btn btn-outline-primary" for="weekday-5">五</label>

                                <input type="checkbox" class="btn-check" id="weekday-6" name="weekdays[]" value="6">
                                <label class="btn btn-outline-primary" for="weekday-6">六</label>

                                <input type="checkbox" class="btn-check" id="weekday-0" name="weekdays[]" value="0">
                                <label class="btn btn-outline-primary" for="weekday-0">日</label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 快速選擇模板 --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">快速選擇模板</label>
                        <div class="quick-select-templates">
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2" data-template="246">
                                模式ㄧ (二、四、六)
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2" data-template="135">
                                模式二 (一、三、五)
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2" data-template="15">
                                模式三 (一、五)
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-template="clear">
                                清除選擇
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 預覽生成的日期 --}}
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-primary" id="generate-recurring-dates">
                        <i class="fas fa-calendar-check me-2"></i>產生日期預覽
                    </button>
                    <div id="recurring-dates-preview" class="mt-3">
                        <!-- 動態顯示生成的日期 -->
                    </div>
                </div>
            </div>

            {{-- 批量訂單預覽區域 --}}
            <div id="batch-preview-section" style="display: none;" class="mt-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-eye me-2"></i>批量訂單預覽
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="batch-summary mb-3">
                            <span class="badge bg-primary fs-6">將建立 <span id="total-orders">0</span> 筆訂單</span>
                        </div>
                        <div id="batch-orders-preview" class="table-responsive">
                            <!-- 動態生成訂單預覽表格 -->
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-success me-2" id="create-batch-btn">
                                <i class="fas fa-plus me-2"></i>建立批量訂單
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancel-batch-btn">
                                <i class="fas fa-times me-2"></i>取消
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- 用車資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-car me-2"></i>用車資訊
                </h5>
                @if(!isset($order))
                <button type="button" class="btn btn-outline-secondary btn-sm" id="historyOrderBtn"
                        style="display: none;" title="選擇歷史訂單快速填入">
                    <i class="fas fa-history me-1"></i>歷史訂單
                </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            {{-- 用車基本資訊 --}}
            <div class="row g-3">
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
                    <label class="form-label">
                        回程時間
                    </label>
                    <input type="text" name="back_time" class="form-control time-auto-format"
                           pattern="^([01]?[0-9]|2[0-3]):[0-5][0-9]$"
                           placeholder="例如1600"
                           maxlength="5"
                           inputmode="numeric"
                           title="直接輸入4位數字，系統會自動格式化為 HH:MM。填寫後將自動建立往返兩筆訂單。"
                           value="{{ old('back_time', '') }}">
                    <small class="text-muted d-block">（填寫後將自動建立回程訂單）</small>
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
            <div class="mt-4" >
                <label class="" >個案備注：</label>{{ isset($order) ? $order->customer_note : ($customer->note ?? '') }}

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
                <div class="col-md-3">
                    <label class="form-label">共乘對象搜尋</label>
                    <div class="input-group">
                        <input type="text" id="carpoolSearchInput" class="form-control"
                               placeholder="輸入姓名、ID或電話" value="{{ old('carpool_with') }}">
                        <button type="button" class="btn btn-success" id="searchCarpoolBtn">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="clearCarpoolBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-9">
                    <div id="carpoolResults"></div>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label class="form-label">共乘姓名</label>
                    <input type="text" name="carpool_with" id="carpool_with" class="form-control" readonly
                    value="{{ old('carpool_with', isset($order) ? $order->carpool_name : '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">共乘身分證</label>
                    <input type="text" name="carpool_id_number" id="carpool_id_number" class="form-control" readonly
                    value="{{ old('carpool_id_number', isset($order) ? $order->carpool_id : '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">共乘電話</label>
                    <input type="text" name="carpool_phone_number" id="carpool_phone_number" class="form-control" readonly
                    value="{{ old('carpool_phone_number')}}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">共乘地址</label>
                    <input type="text" name="carpool_addresses" id="carpool_addresses" class="form-control" readonly
                    value="{{ old('carpool_addresses')}}">
                </div>
            </div>
            <input type="hidden" name="carpool_customer_id" id="carpool_customer_id" value="{{ old('carpool_customer_id') }}">
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
                    <input type="text" name="driver_name" id="driver_name" class="form-control" readonly
                    value="{{ old('driver_name', isset($order) ? $order->driver_name : '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">車牌號碼</label>
                    <input type="text" name="driver_plate_number" id="driver_plate_number" class="form-control" readonly
                    value="{{ old('driver_plate_number', isset($order) ? $order->driver_plate_number : '') }}">
                </div>
            </div>
            <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id', isset($order) ? $order->driver_id : '') }}">
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
                <div class="col-md-4">
                    <label class="form-label">特殊狀態</label>
                    <select name="special_status" class="form-select">
                        <option value="一般" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '一般' ? 'selected' : '' }}>一般</option>
                        <option value="網頁" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '網頁' ? 'selected' : '' }}>網頁</option>
                        <option value="個管單" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '個管單' ? 'selected' : '' }}>個管單</option>
                        <option value="黑名單" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '黑名單' ? 'selected' : '' }}>黑名單</option>
                        <option value="共乘" {{ old('special_status', isset($order) ? $order->special_status : '一般') == '共乘' ? 'selected' : '' }}>共乘</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">訂單狀態</label>
                    <select name="status" class="form-select">
                        <option value="open" {{ old('status', isset($order) ? $order->status : 'open') == 'open' ? 'selected' : '' }}>可派遣</option>
                        <option value="assigned" {{ old('status', isset($order) ? $order->status : 'open') == 'assigned' ? 'selected' : '' }}>已指派</option>
                        <option value="replacement" {{ old('status', isset($order) ? $order->status : 'open') == 'replacement' ? 'selected' : '' }}>候補</option>
                        <option value="cancelled" {{ old('status', isset($order) ? $order->status : 'open') == 'cancelled' ? 'selected' : '' }}>已取消</option>
                    </select>
                </div>
                <div class="col-md-4">
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