<style>
.form-control-custom {
    font-size: 19px !important;
}

/* 日期欄位更新時的高亮效果 */
.highlight-change {
    animation: highlightPulse 0.6s ease;
}

@keyframes highlightPulse {
    0%, 100% { background-color: transparent; }
    50% { background-color: #e3f2fd; }
}

/* 驗證錯誤時的輪椅欄位樣式 */
select[name="wheelchair"].is-invalid {
    border-color: #dc3545 !important;
    background-color: #f8d7da !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

select[name="wheelchair"].is-invalid:focus {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.5) !important;
}
</style>

<form method="POST" action="{{ isset($order) ? route('orders.update', $order) : route('orders.store') }}"
      class="order-form">
    @csrf
    @if(isset($order))
        @method('PUT')
    @endif

    {{-- 隱藏欄位 --}}
    <input type="hidden" name="customer_id" value="{{ old('customer_id', isset($order) ? $order->customer_id : ($customer->id ?? '')) }}">

    {{-- 搜尋參數隱藏欄位 --}}
    @if(request('keyword'))
        <input type="hidden" name="keyword" value="{{ request('keyword') }}">
    @endif
    @if(request('start_date'))
        <input type="hidden" name="start_date" value="{{ request('start_date') }}">
    @endif
    @if(request('end_date'))
        <input type="hidden" name="end_date" value="{{ request('end_date') }}">
    @endif
    @if(request('customer_id'))
        <input type="hidden" name="search_customer_id" value="{{ request('customer_id') }}">
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

    {{-- 【新增】地址驗證警告訊息（編輯訂單時） --}}
    @if (session('address_warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            {{ session('address_warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('no_send_suggestion'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle"></i>
            {{ session('no_send_suggestion') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
            @php
                $dateMode = old('date_mode', 'single');
            @endphp
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">建立模式</label>
                    <div class="date-mode-selector d-flex align-items-center">
                        <div class="btn-group me-3" role="group">
                            <input type="radio" class="btn-check" name="date_mode" id="single_day" value="single" {{ $dateMode === 'single' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="single_day">單日訂單</label>

                            <input type="radio" class="btn-check" name="date_mode" id="manual_multiple" value="manual" {{ $dateMode === 'manual' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="manual_multiple">手動多日</label>

                            {{-- 週期性選項：台北長照時不顯示（14天限制不適合週期訂單） --}}
                            @if(!isset($customer) || $customer->county_care !== '台北長照')
                            <input type="radio" class="btn-check" name="date_mode" id="recurring" value="recurring" {{ $dateMode === 'recurring' ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary" for="recurring">週期性</label>
                            @endif
                        </div>

                        {{-- 允許過去日期開關 --}}
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="allow_past_dates" onchange="togglePastDateRestriction()">
                            <label class="custom-control-label" for="allow_past_dates">
                                <i class="fas fa-history"></i> 補建過去訂單
                            </label>
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        勾選「補建過去訂單」可選擇過去的用車日期，用於補建遺漏的訂單
                    </small>
                </div>
            </div>
            @endif

            {{-- 單日模式 --}}
            <div id="single-date-section">
                <div class="row g-3" >
                    <div class="col-md-3">
                        <label class="form-label">用車日期</label>
                        <div class="input-group">
                            <input type="date"
                                   name="ride_date"
                                   id="ride_date"
                                   class="form-control form-control-custom"
                                   required
                                   @if(!isset($order))
                                   min="{{ \Carbon\Carbon::today()->toDateString() }}"
                                   @endif
                                   value="{{ old('ride_date', isset($order) ? $order->ride_date?->format('Y-m-d') : '') }}">
                            <button type="button" class="btn btn-outline-secondary" id="setTodayBtn" title="設定為今天">
                                <i class="fas fa-calendar-day me-1"></i>今日
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 手動多日選擇區域 --}}
            @if(!isset($order))
            <div id="manual-dates-section" style="display: none;" class="mt-4">
                {{-- 隱藏欄位：保存 old() 資料供 JavaScript 恢復 --}}
                @if(old('selected_dates'))
                    <input type="hidden" id="old-selected-dates" value="{{ json_encode(old('selected_dates')) }}">
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">選擇日期</label>
                        <input type="text" id="multiple-date-picker" class="form-control form-control-custom" placeholder="點擊選擇多個日期">
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
                        <input type="date" name="start_date" class="form-control form-control-custom" value="{{ old('start_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">結束日期</label>
                        <input type="date" name="end_date" class="form-control form-control-custom" value="{{ old('end_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">重複週期</label>
                        <select name="recurrence_type" class="form-select">
                            <option value="weekly" {{ old('recurrence_type') === 'weekly' ? 'selected' : '' }}>每週</option>
                            <option value="biweekly" {{ old('recurrence_type') === 'biweekly' ? 'selected' : '' }}>每兩週</option>
                            <option value="monthly" {{ old('recurrence_type') === 'monthly' ? 'selected' : '' }}>每月</option>
                        </select>
                    </div>
                </div>

                {{-- 星期幾複選 --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">選擇星期幾（可複選）</label>
                        <div class="weekday-selection">
                            <div class="btn-group flex-wrap" role="group">
                                @php
                                    $oldWeekdays = old('weekdays', []);
                                @endphp
                                <input type="checkbox" class="btn-check" id="weekday-1" name="weekdays[]" value="1" {{ in_array('1', $oldWeekdays) ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="weekday-1">一</label>

                                <input type="checkbox" class="btn-check" id="weekday-2" name="weekdays[]" value="2" {{ in_array('2', $oldWeekdays) ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="weekday-2">二</label>

                                <input type="checkbox" class="btn-check" id="weekday-3" name="weekdays[]" value="3" {{ in_array('3', $oldWeekdays) ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="weekday-3">三</label>

                                <input type="checkbox" class="btn-check" id="weekday-4" name="weekdays[]" value="4" {{ in_array('4', $oldWeekdays) ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="weekday-4">四</label>

                                <input type="checkbox" class="btn-check" id="weekday-5" name="weekdays[]" value="5" {{ in_array('5', $oldWeekdays) ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="weekday-5">五</label>

                                <input type="checkbox" class="btn-check" id="weekday-6" name="weekdays[]" value="6" {{ in_array('6', $oldWeekdays) ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="weekday-6">六</label>

                                <input type="checkbox" class="btn-check" id="weekday-0" name="weekdays[]" value="0" {{ in_array('0', $oldWeekdays) ? 'checked' : '' }}>
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
                            <button type="button" class="btn btn-outline-primary btn-sm me-2" data-template="12345">
                                一、二、三、四、五
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm me-2" data-template="246">
                                二、四、六
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm me-2" data-template="135">
                                一、三、五
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm me-2" data-template="15">
                                一、五
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-template="clear">
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
                    <div class="card-header bg-secondary text-white">
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
        <div class="card-header bg-info text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-car me-2"></i>用車資訊
                </h5>
            </div>
        </div>
        <div class="card-body">
            {{-- 用車基本資訊 --}}
            <div class="row g-3">
                <div class="col-md-2 d-grid align-items-center">
                    @if(!isset($order))
                    <button type="button" class="btn btn-outline-primary btn-lg" id="historyOrderBtn"
                            style="display: none;" title="選擇歷史訂單快速填入">
                        <i class="fas fa-history me-1"></i>歷史訂單
                    </button>
                    @endif
                </div>
                <div class="col-md-2">
                    <label class="form-label">用車時間</label>
                    <input type="text" id="ride_time" name="ride_time" class="form-control form-control-custom time-auto-format" required
                           pattern="^([01]?[0-9]|2[0-3]):[0-5][0-9]$"
                           placeholder="直接輸入4位數字"
                           maxlength="5"
                           title="直接輸入4位數字，系統會自動格式化為 HH:MM"
                           value="{{ old('ride_time', isset($order) && $order->ride_time ? (strlen($order->ride_time) > 5 ? substr($order->ride_time, 0, 5) : $order->ride_time) : '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">
                        回程時間
                    </label>
                    <input type="text" id="back_time" name="back_time" class="form-control form-control-custom time-auto-format"
                           pattern="^([01]?[0-9]|2[0-3]):[0-5][0-9]$"
                           placeholder="例如1600"
                           maxlength="5"
                           title="直接輸入4位數字，系統會自動格式化為 HH:MM。填寫後將自動建立往返兩筆訂單。"
                           value="{{ old('back_time', '') }}">
                    <small class="text-muted d-block">（填寫後將自動建立回程訂單）</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">陪同人數</label>
                    <input type="number" name="companions" class="form-control form-control-custom" min="0"
                           value="{{ old('companions', isset($order) ? $order->companions : 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">輪椅</label>
                    @php
                        // 決定輪椅預設值的優先順序：
                        // 1. old() - 表單驗證失敗時保留使用者輸入
                        // 2. 編輯訂單時使用訂單的值
                        // 3. 新增訂單時使用客戶的預設值
                        // 4. 最後預設為「否」
                        $defaultWheelchair = old('wheelchair',
                            isset($order) ? $order->wheelchair :
                                (isset($customer) && $customer->wheelchair ? $customer->wheelchair : '否')
                        );
                    @endphp
                    <select name="wheelchair" class="form-select form-control-custom">
                        <option value="否" {{ $defaultWheelchair == '否' ? 'selected' : '' }}>否</option>
                        <option value="是" {{ $defaultWheelchair == '是' ? 'selected' : '' }}>是</option>
                        <option value="未知" {{ $defaultWheelchair == '未知' ? 'selected' : '' }}>未知</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">爬梯機</label>
                    <select name="stair_machine" class="form-select form-control-custom">
                        @php
                            // 決定爬梯機預設值的優先順序：
                            // 1. old() - 表單驗證失敗時保留使用者輸入
                            // 2. 編輯訂單時使用訂單的值
                            // 3. 新增訂單時使用客戶的預設值
                            // 4. 最後預設為「否」
                            $defaultStairMachine = old('stair_machine',
                                isset($order) ? $order->stair_machine :
                                    (isset($customer) && $customer->stair_climbing_machine ? $customer->stair_climbing_machine : '否')
                            );
                        @endphp
                        <option value="否" {{ $defaultStairMachine == '否' ? 'selected' : '' }}>否</option>
                        <option value="是" {{ $defaultStairMachine == '是' ? 'selected' : '' }}>是</option>
                        <option value="未知" {{ $defaultStairMachine == '未知' ? 'selected' : '' }}>未知</option>
                    </select>
                        @if(isset($customer) && $customer->stair_climbing_machine === '是')
                            @php
                                $note = $customer->note;
                                $keyword = '開發';
                                $displayNote = '';

                                // 只有當 note 包含「開發」關鍵字時才顯示
                                if ($note && mb_strpos($note, $keyword) !== false) {
                                    // 找到關鍵字的位置
                                    $position = mb_strpos($note, $keyword);
                                    // 計算起始位置（關鍵字前4個字元）
                                    $start = max(0, $position - 4);
                                    // 擷取：前4個字元 + 關鍵字（2個字）= 共6個字
                                    $displayNote = mb_substr($note, $start, $position - $start + 2);
                                }
                            @endphp

                            <h4>
                                <span class="badge bg-danger">
                                    爬梯機個案@if($displayNote) {{ $displayNote }}@endif
                                </span>
                            </h4>
                        @endif
                </div>
            </div>

            {{-- 地址資訊 --}}
            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label class="form-label">上車地址</label>
                    <div class="input-group">
                        <input type="text" name="pickup_address" id="pickup_address" class="form-control form-control-custom landmark-input" required
                               value="{{ old('pickup_address', isset($order) ? $order->pickup_address : (is_array($customer->addresses ?? []) && !empty($customer->addresses) ? $customer->addresses[0] : '')) }}"
                               placeholder="輸入地址或使用*觸發地標搜尋">
                        <button type="button" class="btn btn-outline-secondary" onclick="openLandmarkModal('pickup')">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="col-12 text-center">
                    <button type="button" class="btn btn-outline-primary" id="swapAddressBtn">
                        <i class="fas fa-exchange-alt"></i> 交換地址
                    </button>
                    <button type="button" class="btn btn-outline-info copyOrderInfoBtn">
                        <i class="fas fa-copy me-1"></i>
                        複製訂單資訊
                    </button>
                </div>
                <div class="col-12">
                    <label class="form-label">下車地址</label>
                    <div class="input-group">
                        <input type="text" name="dropoff_address" id="dropoff_address" class="form-control form-control-custom landmark-input" required
                               value="{{ old('dropoff_address', isset($order) ? $order->dropoff_address : '') }}"
                               placeholder="輸入地址或使用*觸發地標搜尋">
                        <button type="button" class="btn btn-outline-secondary" onclick="openLandmarkModal('dropoff')">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-4 h5" style="color:red">
                <label class="">個案備註：</label>{{ isset($order) ? $order->customer_note : ($customer->note ?? '') }}

            </div>


        </div>
    </div>

    {{-- 客戶資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-user me-2"></i>客戶資訊
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">電話</label>
                    <input type="text" name="customer_phone" class="form-control form-control-custom"  value="{{ old('customer_phone', isset($order) ? $order->customer_phone : '') }}" >
                </div>
                <div class="col-md-2">
                    <label class="form-label">客戶姓名</label>
                        <input type="text" name="customer_name" class="form-control form-control-custom"
                        value="{{ old('customer_name', isset($order) ? $order->customer_name : ($customer->name ?? '')) }}" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">身分證字號</label>
                    <input type="text" name="customer_id_number" class="form-control form-control-custom"
                        value="{{ old('customer_id_number', isset($order) ? $order->customer_id_number : ($customer->id_number ?? '')) }}" readonly>
                </div>
                <div class="col-md-2">
                    <label class="form-label">訂單類型</label>
                    <input type="text" name="order_type" class="form-control form-control-custom"
                        value="{{ old('order_type', isset($order) ? $order->order_type : ($customer->county_care ?? '')) }}">
                </div>
                <!--<div class="col-md-2"> 這邊主要先隱藏欄位，有需要用再打開
                    <label class="form-label">身份別</label>-->
                    <input type="hidden" name="identity" class="form-control form-control-custom"
                        value="{{ old('identity', isset($order) ? $order->identity : ($customer->identity ?? '')) }}" readonly>
                <!--</div>-->
                <div class="col-md-2">
                    <label class="form-label">交通公司</label>
                    <input type="text" name="service_company" class="form-control form-control-custom"
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
                    @php
                        // 決定特殊狀態預設值的優先順序：
                        // 1. old() - 表單驗證失敗時保留使用者輸入
                        // 2. 編輯訂單時使用訂單的值
                        // 3. 新增訂單時使用客戶的預設值
                        // 4. 最後預設為「一般」
                        $defaultSpecialStatus = old('special_status',
                            isset($order) ? $order->special_status :
                                (isset($customer) && $customer->special_status ? $customer->special_status : '一般')
                        );
                    @endphp
                    <select name="special_status" class="form-select form-control-custom">
                        <option value="一般" {{ $defaultSpecialStatus == '一般' ? 'selected' : '' }}>一般</option>
                        <option value="網頁單" {{ $defaultSpecialStatus == '網頁單' ? 'selected' : '' }}>網頁單</option>
                        <option value="Line" {{ $defaultSpecialStatus == 'Line' ? 'selected' : '' }}>Line</option>
                        <option value="個管單" {{ $defaultSpecialStatus == '個管單' ? 'selected' : '' }}>個管單</option>
                        <option value="黑名單" {{ $defaultSpecialStatus == '黑名單' ? 'selected' : '' }}>黑名單</option>
                        <option value="共乘單" {{ $defaultSpecialStatus == '共乘單' ? 'selected' : '' }}>共乘單</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">訂單狀態</label>
                    @php
                        // 決定訂單狀態預設值的優先順序：
                        // 1. old() - 表單驗證失敗時保留使用者輸入
                        // 2. 編輯訂單時使用訂單的值
                        // 3. 新增訂單時使用 controller 傳來的預設值（可能因客戶黑名單而設為 blacklist）
                        // 4. 最後預設為 'open'
                        $defaultOrderStatus = old('status',
                            isset($order) ? $order->status : ($defaultStatus ?? 'open')
                        );
                    @endphp
                    <select name="status" class="form-select form-control-custom">
                        <option value="open" {{ $defaultOrderStatus == 'open' ? 'selected' : '' }}>可派遣</option>
                        <option value="assigned" {{ $defaultOrderStatus == 'assigned' ? 'selected' : '' }}>已指派</option>
                        <option value="bkorder" {{ $defaultOrderStatus == 'bkorder' ? 'selected' : '' }}>已候補</option>
                        <option value="blocked" {{ $defaultOrderStatus == 'blocked' ? 'selected' : '' }}>無人承接</option>
                        <option value="no_send" {{ $defaultOrderStatus == 'no_send' ? 'selected' : '' }}>不派遣</option>
                        <option value="blacklist" {{ $defaultOrderStatus == 'blacklist' ? 'selected' : '' }}>黑名單</option>
                        <option value="regular_sedans" {{ $defaultOrderStatus == 'regular_sedans' ? 'selected' : '' }}>一般車</option>
                        <option value="no_car" {{ $defaultOrderStatus == 'no_car' ? 'selected' : '' }}>傳沒車</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">建立者</label>
                    <input type="text" name="created_by" class="form-control form-control-custom"
                    value="{{ old('created_by', isset($order) ? $order->created_by : optional(auth()->user())->name) }}" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">訂單備註</label>
                    <textarea name="remark" class="form-control form-control-custom" rows="3">{{ old('remark', isset($order) ? $order->remark : '') }}</textarea>
                </div>
            </div>
        </div>
    </div>
    
    {{-- 共乘資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white"
                data-bs-toggle="collapse"
                data-bs-target="#carpoolInfoCollapse"
                style="cursor: pointer;">
            <h5 class="mb-0 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-users me-2"></i>共乘資訊</span>
                <i class="fas fa-chevron-down"></i>
            </h5>
        </div>
        <div class="collapse class" id="carpoolInfoCollapse">
            <div class="card-body">
                @if(!isset($order))
                    {{-- 新增模式：顯示共乘搜尋功能 --}}
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">共乘對象搜尋</label>
                            <div class="input-group">
                                <input type="text" id="carpoolSearchInput" class="form-control form-control-custom"
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
                @endif

                {{-- 共乘資訊欄位（新增和編輯模式都顯示） --}}
                <div class="row g-3 mt-2">
                    <div class="col-md-3">
                        <label class="form-label">共乘姓名</label>
                        <input type="text" name="carpool_with" id="carpool_with" class="form-control form-control-custom" readonly
                        value="{{ old('carpool_with', isset($order) ? $order->carpool_name : '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">共乘身分證</label>
                        <input type="text" name="carpool_id_number" id="carpool_id_number" class="form-control form-control-custom" readonly
                        value="{{ old('carpool_id_number', isset($order) ? $order->carpool_id : '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">共乘電話</label>
                        <input type="text" name="carpool_phone_number" id="carpool_phone_number" class="form-control form-control-custom" readonly
                        value="{{ old('carpool_phone_number')}}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">共乘地址</label>
                        <input type="text" name="carpool_addresses" id="carpool_addresses" class="form-control form-control-custom" readonly
                        value="{{ old('carpool_addresses')}}">
                    </div>
                </div>
                <input type="hidden" name="carpool_customer_id" id="carpool_customer_id" value="{{ old('carpool_customer_id') }}">
            </div>
        </div>
    </div>

    {{-- 駕駛資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white"
            data-bs-toggle="collapse"
            data-bs-target="#driverInfoCollapse"
            style="cursor: pointer;">
            <h5 class="mb-0 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-tie me-2"></i>駕駛資訊</span>
                <i class="fas fa-chevron-down"></i>
            </h5>
        </div>
        <div class="collapse class" id="driverInfoCollapse">
            <div class="card-body">
                {{-- 去程駕駛資訊 --}}
                <div class="mb-4">
                    <h6 class="mb-3" style="color: rgb(183, 110, 20)">
                        <i class="fas fa-arrow-right me-2"></i>去程駕駛
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">駕駛隊編</label>
                            <div class="input-group">
                                <input type="text" name="driver_fleet_number" id="driver_fleet_number" class="form-control form-control-custom"
                                    value="{{ old('driver_fleet_number', isset($order) ? $order->driver_fleet_number : '') }}">
                                <button type="button" class="btn btn-success" id="searchDriverBtn" data-target="outbound">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="clearDriverBtn" data-target="outbound">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">駕駛姓名</label>
                            <input type="text" name="driver_name" id="driver_name" class="form-control form-control-custom" readonly
                            value="{{ old('driver_name', isset($order) ? $order->driver_name : '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">車牌號碼</label>
                            <input type="text" name="driver_plate_number" id="driver_plate_number" class="form-control form-control-custom" readonly
                            value="{{ old('driver_plate_number', isset($order) ? $order->driver_plate_number : '') }}">
                        </div>
                    </div>
                    <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id', isset($order) ? $order->driver_id : '') }}">
                </div>

                {{-- 回程駕駛資訊 --}}
                <div class="mb-3">
                    <div class="row justify-content-between align-items-center mb-3">
                        <div class ="col-md-5">
                        <h6 class="mb-0" style="color: rgb(183, 110, 20)">
                            <i class="fas fa-arrow-left me-2"></i>回程駕駛
                        </h6>
                        </div>
                        <div class ="col-md-7">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="copyOutboundDriverBtn">
                            <i class="fas fa-copy me-1"></i>複製去程駕駛
                        </button>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">駕駛隊編</label>
                            <div class="input-group">
                                <input type="text" name="return_driver_fleet_number" id="return_driver_fleet_number" class="form-control form-control-custom"
                                    value="{{ old('return_driver_fleet_number', '') }}">
                                <button type="button" class="btn btn-success" id="searchReturnDriverBtn" data-target="return">
                                    <i class="fas fa-search"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" id="clearReturnDriverBtn" data-target="return">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">駕駛姓名</label>
                            <input type="text" name="return_driver_name" id="return_driver_name" class="form-control form-control-custom" readonly
                            value="{{ old('return_driver_name', '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">車牌號碼</label>
                            <input type="text" name="return_driver_plate_number" id="return_driver_plate_number" class="form-control form-control-custom" readonly
                            value="{{ old('return_driver_plate_number', '') }}">
                        </div>
                    </div>
                    <input type="hidden" name="return_driver_id" id="return_driver_id" value="{{ old('return_driver_id', '') }}">

                </div>
            </div>
        </div>
    </div>

    {{-- 提交按鈕 --}}
    <div class="text-center py-4">
        <div id="singleOrderActions">
            <button type="submit" class="btn btn-primary btn-lg px-5 me-3" id="singleOrderSubmitBtn">
                <i class="fas fa-save me-2"></i>
                {{ isset($order) ? '更新訂單' : '建立單日訂單' }}
            </button>
            <button type="button" class="btn btn-outline-info btn-lg px-4 copyOrderInfoBtn">
                <i class="fas fa-copy me-2"></i>
                複製訂單資訊
            </button>
        </div>
    </div>
</form>

{{-- 訂單資訊複製 Modal --}}
<div class="modal fade" id="orderInfoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-copy me-2"></i>複製訂單資訊
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- 單程顯示區域 --}}
                <div id="singleTripArea">
                    <div class="mb-3">
                        <label class="form-label">訂單資訊文字</label>
                        <textarea id="orderInfoText" class="form-control form-control-custom" rows="8" readonly
                                  style="font-family: monospace; background-color: #f8f9fa;"></textarea>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        點擊下方「複製」按鈕將訂單資訊複製到剪貼板
                    </div>
                </div>

                {{-- 去回程分離顯示區域 --}}
                <div id="roundTripArea" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-primary">
                                <i class="fas fa-arrow-right me-1"></i>去程資訊
                            </label>
                            <textarea id="outboundInfoText" class="form-control form-control-custom" rows="6" readonly
                                      style="font-family: monospace; background-color: #f8f9fa;"></textarea>
                            <button type="button" class="btn btn-primary btn-sm mt-2 w-100" id="copyOutboundBtn">
                                <i class="fas fa-copy me-1"></i>複製去程資訊
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-success">
                                <i class="fas fa-arrow-left me-1"></i>回程資訊
                            </label>
                            <textarea id="returnInfoText" class="form-control form-control-custom" rows="6" readonly
                                      style="font-family: monospace; background-color: #f8f9fa;"></textarea>
                            <button type="button" class="btn btn-success btn-sm mt-2 w-100" id="copyReturnBtn">
                                <i class="fas fa-copy me-1"></i>複製回程資訊
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        您可以分別複製去程和回程資訊，或使用下方按鈕複製完整資訊
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>關閉
                </button>
                <button type="button" class="btn btn-primary" id="copyToClipboardBtn">
                    <i class="fas fa-copy me-2"></i>複製到剪貼板
                </button>
                <button type="button" class="btn btn-outline-primary" id="copyAllBtn" style="display: none;">
                    <i class="fas fa-copy me-2"></i>複製完整資訊
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// 控制「允許過去日期」開關
function togglePastDateRestriction() {
    const checkbox = document.getElementById('allow_past_dates');
    const dateInput = document.getElementById('ride_date');

    if (!checkbox || !dateInput) return;

    if (checkbox.checked) {
        // 勾選：移除 min 限制，允許選擇任何過去日期
        dateInput.removeAttribute('min');
    } else {
        // 未勾選：恢復限制只能選今天或未來
        dateInput.setAttribute('min', '{{ \Carbon\Carbon::today()->toDateString() }}');
    }
}
</script>
