<form id="orderForm{{ $customer->id ?? '' }}" class="orderForm" method="POST" action="{{ isset($order) ? route('orders.update', $order->id) : route('orders.store') }}">
    @csrf
    @if(isset($order))
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


        <!--個案資料表ID-->
        <input type="hidden" name="customer_id" value="{{ old('customer_id', $order->customer_id ?? $customer->id ?? '') }}">
        
        <!--保持搜尋關鍵字-->
        @if(request('keyword'))
            <input type="hidden" name="keyword" value="{{ request('keyword') }}">
        @endif

        <!-- 客戶資訊區塊 -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-gradient-primary text-white py-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-user me-2"></i>
                    客戶資訊
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-id-card text-primary me-2"></i>個案姓名
                        </label>
                        <input type="text" name="customer_name" class="form-control rounded-pill bg-light"
                            value="{{ old('customer_name', $order->customer_name ?? $customer->name ?? '') }}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-id-badge text-info me-2"></i>個案身分證字號
                        </label>
                        <input type="text" name="customer_id_number" class="form-control rounded-pill bg-light"
                            value="{{ old('customer_id_number', $order->customer_id_number ?? $customer->id_number ?? '') }}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-phone text-success me-2"></i>個案電話
                        </label>
                        <input type="text" name="customer_phone" class="form-control rounded-pill">
                            
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user-tag text-warning me-2"></i>個案身份別
                        </label>
                        <input type="text" name="identity" class="form-control rounded-pill bg-light"
                            value="{{ old('identity', $order->identity ?? $customer->identity ?? '') }}" readonly>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-building text-purple me-2"></i>交通公司
                        </label>
                        <input type="text" name="service_company" class="form-control rounded-pill bg-light text-primary fw-bold"
                            value="{{ old('service_company', $order->service_company ?? $customer->service_company ?? '') }}" readonly>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-search text-primary me-2"></i>共乘對象
                        </label>
                        <div class="input-group">
                            <input type="text" name="carpoolSearchInput" id="carpoolSearchInput" class="form-control rounded-start-pill" placeholder="名字、ID、電話" value="{{ old('carpoolSearchInput', $order->carpool_name ?? '') }}">
                            <button type="button" class="btn btn-success" id="searchCarpoolBtn">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger rounded-end-pill" id="clearCarpoolBtn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-id-badge text-info me-2"></i>共乘身分證字號
                        </label>
                        <input type="text" name="carpool_id_number" id="carpool_id_number" class="form-control rounded-pill bg-light" readonly onfocus="this.blur();" value="{{ old('carpool_id_number', $order->carpool_id ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-phone text-success me-2"></i>共乘電話
                        </label>
                        <input type="text" name="carpool_phone_number" id="carpool_phone_number" class="form-control rounded-pill bg-light" readonly onfocus="this.blur();" value="{{ old('carpool_phone_number', $order->carpool_phone ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="fas fa-map-marker-alt text-warning me-2"></i>共乘乘客地址
                        </label>
                        <input type="text" name="carpool_addresses" id="carpool_addresses" class="form-control rounded-pill bg-light" readonly onfocus="this.blur();" value="{{ old('carpool_addresses', $order->carpool_addresses ?? '') }}">
                        <!-- 隱藏用於存儲客戶ID -->
                        <input type="hidden" name="carpool_customer_id" id="carpool_customer_id" value="{{ old('carpool_customer_id', $order->carpool_customer_id ?? '') }}">
                        <input type="hidden" name="carpool_with" id="carpool_with" value="{{ old('carpool_with', $order->carpool_name ?? '') }}">
                    </div>
                    <div class="col-12">
                        <div id="carpoolResults"></div>
                    </div>
                </div>
            </div>
        </div>
        


        <!-- 用車資訊區塊 -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-gradient-warning text-dark py-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-car me-2"></i>
                    用車資訊
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-calendar text-primary me-2"></i>用車日期
                        </label>
                        <input type="date" name="ride_date" class="form-control rounded-pill" value="{{ old('ride_date', $order->ride_date ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-clock text-info me-2"></i>用車時間
                        </label>
                        <input type="text" name="ride_time" class="form-control rounded-pill"
                            pattern="^([01]\d|2[0-3]):[0-5]\d$"
                            placeholder="例如：13:45"
                            value="{{ old('ride_time', isset($order) ? substr($order->ride_time, 0, 5) : '') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-undo text-success me-2"></i>回程時間
                        </label>
                        <input type="text" name="back_time" class="form-control rounded-pill"
                            pattern="^([01]\d|2[0-3]):[0-5]\d$"
                            placeholder="例如：13:45"
                            value="{{ old('ride_time') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user-friends text-warning me-2"></i>陪同人數
                        </label>
                        <input type="number" name="companions" class="form-control rounded-pill" min="0" value="{{ old('companions', $order->companions ?? 0) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-wheelchair text-danger me-2"></i>是否輪椅
                        </label>
                        <select name="wheelchair" class="form-select rounded-pill">
                            <option value="0" {{ in_array(old('wheelchair', $order->wheelchair ?? ($customer->wheelchair ?? 0)) ,['0', '否']) ? 'selected' : '' }}>否</option>
                            <option value="1" {{ in_array(old('wheelchair', $order->wheelchair ?? ($customer->wheelchair ?? 0)) ,['1', '是']) ? 'selected' : '' }}>是</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">
                            <i class="fas fa-tools text-purple me-2"></i>爬梯機
                        </label>
                        <select name="stair_machine" class="form-select rounded-pill">
                            <option value="0"{{ in_array(old('stair_machine', $order->stair_machine ?? ($customer->stair_climbing_machine ?? 0)),['0', '否']) ? 'selected' : '' }}>否</option>
                            <option value="1"{{ in_array(old('stair_machine', $order->stair_machine ?? ($customer->stair_climbing_machine ?? 0)),['1', '是']) ? 'selected' : '' }}>是</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <!-- 上車地址 -->
                    <div class="col-12">
                        <label class="form-label fw-bold">
                            <i class="fas fa-play-circle text-success me-2"></i>上車地址
                            <small class="text-muted">(要有XX市XX區)</small>
                        </label>
                        <div class="landmark-input-group">
                            <input type="text" name="pickup_address" class="form-control landmark-input rounded-pill"
                                   value="{{ old('pickup_address', $order->pickup_address ?? ($customer->addresses[0] ?? '')) }}"
                                   placeholder="輸入地址或搜尋地標（使用*觸發搜尋，如：台北*）">
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-secondary landmark-btn dropdown-toggle" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-map-marker-alt"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end landmark-dropdown shadow-lg border-0" style="width: 500px; max-height: 600px; overflow: hidden;">
                                    <!-- 地標彈出視窗標題 -->
                                    <div class="landmark-header bg-gradient-success text-white p-3">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <i class="fas fa-play-circle me-2"></i>
                                            選擇地標
                                            <span class="badge bg-light text-success ms-auto">上車地址</span>
                                        </h6>
                                    </div>

                                    <!-- 搜尋區域 -->
                                    <div class="landmark-search-area p-3 bg-light">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control landmark-search-input border-start-0 border-end-0" placeholder="搜尋地標名稱或地址...">
                                            <button class="btn btn-success landmark-search-btn" type="button">
                                                <i class="fas fa-search me-1"></i>搜尋
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 分類篩選區域 -->
                                    <div class="landmark-categories px-3 py-2 border-bottom">
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-outline-secondary btn-sm category-filter active" data-category="all">
                                                <i class="fas fa-th me-1"></i>全部
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm category-filter" data-category="medical">
                                                <i class="fas fa-hospital me-1"></i>醫療
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-sm category-filter" data-category="transport">
                                                <i class="fas fa-bus me-1"></i>交通
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm category-filter" data-category="education">
                                                <i class="fas fa-school me-1"></i>教育
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-sm category-filter" data-category="government">
                                                <i class="fas fa-building me-1"></i>政府
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm category-filter" data-category="commercial">
                                                <i class="fas fa-store me-1"></i>商業
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 分頁標籤 -->
                                    <div class="landmark-tabs">
                                        <ul class="nav nav-pills nav-justified bg-light" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active small" id="pickup-search-tab" data-bs-toggle="pill" data-bs-target="#pickup-search-content" type="button" role="tab">
                                                    <i class="fas fa-search me-1"></i>搜尋結果
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link small" id="pickup-popular-tab" data-bs-toggle="pill" data-bs-target="#pickup-popular-content" type="button" role="tab">
                                                    <i class="fas fa-fire me-1"></i>熱門地標
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link small" id="pickup-recent-tab" data-bs-toggle="pill" data-bs-target="#pickup-recent-content" type="button" role="tab">
                                                    <i class="fas fa-history me-1"></i>最近使用
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- 內容區域 -->
                                    <div class="landmark-content" style="max-height: 350px; overflow-y: auto;">
                                        <div class="tab-content">
                                            <!-- 搜尋結果 -->
                                            <div class="tab-pane fade show active" id="pickup-search-content" role="tabpanel">
                                                <div class="landmark-results p-2"></div>
                                            </div>
                                            <!-- 熱門地標 -->
                                            <div class="tab-pane fade" id="pickup-popular-content" role="tabpanel">
                                                <div class="landmark-popular p-2"></div>
                                            </div>
                                            <!-- 最近使用 -->
                                            <div class="tab-pane fade" id="pickup-recent-content" role="tabpanel">
                                                <div class="landmark-recent p-2"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 底部提示 -->
                                    <div class="landmark-footer text-center py-2 border-top bg-light">
                                        <small class="text-muted">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            提示：點擊地標快速填入地址
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('pickup_address')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- 地址交換按鈕 -->
                    <div class="col-12 text-center py-2">
                        <button type="button" class="btn btn-outline-info rounded-pill px-4" id="swapAddressBtn">
                            <i class="fas fa-exchange-alt me-2"></i>交換上下車地址
                        </button>
                    </div>

                    <!-- 下車地址 -->
                    <div class="col-12">
                        <label class="form-label fw-bold">
                            <i class="fas fa-stop-circle text-danger me-2"></i>下車地址
                            <small class="text-muted">(要有XX市XX區)</small>
                        </label>
                        <div class="landmark-input-group">
                            <input type="text" name="dropoff_address" class="form-control landmark-input rounded-pill"
                                   value="{{ old('dropoff_address', $order->dropoff_address ?? '') }}"
                                   placeholder="輸入地址或搜尋地標（使用*觸發搜尋，如：台北*）">
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-secondary landmark-btn dropdown-toggle" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-map-marker-alt"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end landmark-dropdown shadow-lg border-0" style="width: 500px; max-height: 600px; overflow: hidden;">
                                    <!-- 地標彈出視窗標題 -->
                                    <div class="landmark-header bg-gradient-danger text-white p-3">
                                        <h6 class="mb-0 d-flex align-items-center">
                                            <i class="fas fa-stop-circle me-2"></i>
                                            選擇地標
                                            <span class="badge bg-light text-danger ms-auto">下車地址</span>
                                        </h6>
                                    </div>

                                    <!-- 搜尋區域 -->
                                    <div class="landmark-search-area p-3 bg-light">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control landmark-search-input border-start-0 border-end-0" placeholder="搜尋地標名稱或地址...">
                                            <button class="btn btn-danger landmark-search-btn" type="button">
                                                <i class="fas fa-search me-1"></i>搜尋
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 分類篩選區域 -->
                                    <div class="landmark-categories px-3 py-2 border-bottom">
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-outline-secondary btn-sm category-filter active" data-category="all">
                                                <i class="fas fa-th me-1"></i>全部
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm category-filter" data-category="medical">
                                                <i class="fas fa-hospital me-1"></i>醫療
                                            </button>
                                            <button type="button" class="btn btn-outline-primary btn-sm category-filter" data-category="transport">
                                                <i class="fas fa-bus me-1"></i>交通
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm category-filter" data-category="education">
                                                <i class="fas fa-school me-1"></i>教育
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-sm category-filter" data-category="government">
                                                <i class="fas fa-building me-1"></i>政府
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm category-filter" data-category="commercial">
                                                <i class="fas fa-store me-1"></i>商業
                                            </button>
                                        </div>
                                    </div>

                                    <!-- 分頁標籤 -->
                                    <div class="landmark-tabs">
                                        <ul class="nav nav-pills nav-justified bg-light" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active small" id="dropoff-search-tab" data-bs-toggle="pill" data-bs-target="#dropoff-search-content" type="button" role="tab">
                                                    <i class="fas fa-search me-1"></i>搜尋結果
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link small" id="dropoff-popular-tab" data-bs-toggle="pill" data-bs-target="#dropoff-popular-content" type="button" role="tab">
                                                    <i class="fas fa-fire me-1"></i>熱門地標
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link small" id="dropoff-recent-tab" data-bs-toggle="pill" data-bs-target="#dropoff-recent-content" type="button" role="tab">
                                                    <i class="fas fa-history me-1"></i>最近使用
                                                </button>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- 內容區域 -->
                                    <div class="landmark-content" style="max-height: 350px; overflow-y: auto;">
                                        <div class="tab-content">
                                            <!-- 搜尋結果 -->
                                            <div class="tab-pane fade show active" id="dropoff-search-content" role="tabpanel">
                                                <div class="landmark-results p-2"></div>
                                            </div>
                                            <!-- 熱門地標 -->
                                            <div class="tab-pane fade" id="dropoff-popular-content" role="tabpanel">
                                                <div class="landmark-popular p-2"></div>
                                            </div>
                                            <!-- 最近使用 -->
                                            <div class="tab-pane fade" id="dropoff-recent-content" role="tabpanel">
                                                <div class="landmark-recent p-2"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 底部提示 -->
                                    <div class="landmark-footer text-center py-2 border-top bg-light">
                                        <small class="text-muted">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            提示：點擊地標快速填入地址
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @error('dropoff_address')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>


        <!-- 特殊資訊區塊 -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-gradient-danger text-white py-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    特殊資訊
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="fas fa-ban text-danger me-2"></i>黑名單個案
                        </label>
                        <select name="special_order" class="form-select rounded-pill">
                            <option value="0" {{ old('special_order', $order->special_order ?? 0) == 0 ? 'selected' : '' }}>否</option>
                            <option value="1" {{ old('special_order', $order->special_order ?? 0) == 1 ? 'selected' : '' }}>是</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="fas fa-flag text-warning me-2"></i>特別狀態訂單
                            <small class="text-muted">(T9的粉紅色)</small>
                        </label>
                        <select name="special_status" class="form-select rounded-pill">
                            <option value="一般" {{ old('special_status', $order->special_status ?? '一般') == '一般' ? 'selected' : '' }}>一般</option>
                            <option value="黑名單" {{ old('special_status', $order->special_status ?? '一般') == '黑名單' ? 'selected' : '' }}>黑名單</option>
                            <option value="個管單" {{ old('special_status', $order->special_status ?? '一般') == '個管單' ? 'selected' : '' }}>個管單</option>
                            <option value="網頁" {{ old('special_status', $order->special_status ?? '一般') == '網頁' ? 'selected' : '' }}>網頁</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">
                            <i class="fas fa-comment text-info me-2"></i>訂單備註
                        </label>
                        <textarea name="remark" rows="3" class="form-control rounded-3" placeholder="請輸入訂單相關備註...">{{ old('remark', $order->remark ?? '') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user-edit text-warning me-2"></i>乘客備註
                        </label>
                        <div class="alert alert-warning border-0 rounded-3">
                            <strong>{{ old('remark2', $customer->note ?? '無備註') }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 駕駛資訊區塊 -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-gradient-secondary text-white py-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-user-tie me-2"></i>
                    駕駛資訊
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-id-card text-primary me-2"></i>駕駛隊編
                        </label>
                        <div class="input-group">
                            <input type="text" name="driver_fleet_number" id="driver_fleet_number" class="form-control rounded-start-pill" placeholder="輸入隊編" value="{{ old('driver_fleet_number', $order->driver_fleet_number ?? '') }}">
                            <button type="button" class="btn btn-success" id="searchDriverBtn">
                                <i class="fas fa-search"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger rounded-end-pill" id="clearDriverBtn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user text-info me-2"></i>駕駛姓名
                        </label>
                        <input type="text" name="driver_name" id="driver_name" class="form-control rounded-pill bg-light" readonly value="{{ old('driver_name', $order->driver_name ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            <i class="fas fa-car text-success me-2"></i>車牌號碼
                        </label>
                        <input type="text" name="driver_plate_number" id="driver_plate_number" class="form-control rounded-pill bg-light" readonly value="{{ old('driver_plate_number', $order->driver_plate_number ?? '') }}">
                    </div>
                    {{-- 隱藏 driver_id --}}
                    <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id', $order->driver_id ?? '') }}">
                </div>
            </div>
        </div>



        <!-- 訂單狀態和提交區塊 -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-gradient-dark text-white py-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-clipboard-check me-2"></i>
                    訂單狀態
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="fas fa-traffic-light text-warning me-2"></i>訂單狀態
                        </label>
                        <select name="status" class="form-select rounded-pill">
                            <option value="open" {{ old('status', $order->status ?? 'open') === 'open' ? 'selected' : '' }}>可派遣</option>
                            <option value="assigned" {{ old('status', $order->status ?? 'open') === 'assigned' ? 'selected' : '' }}>已指派</option>
                            <option value="replacement" {{ old('status', $order->status ?? 'open') === 'replacement' ? 'selected' : '' }}>候補派遣</option>
                            <option value="blocked" {{ old('status', $order->status ?? 'open') === 'blocked' ? 'selected' : '' }}>黑名單</option>
                            <option value="cancelled" {{ old('status', $order->status ?? 'open') === 'cancelled' ? 'selected' : '' }}>已取消</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">
                            <i class="fas fa-user-check text-info me-2"></i>建單人員
                        </label>
                        <input type="text" name="created_by" class="form-control rounded-pill bg-light"
                            value="{{ old('created_by', $order->created_by ?? ($user?->name ?? '')) }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- 提交按鈕區塊 -->
        <div class="text-center py-4">
            <button type="submit" class="btn btn-success btn-lg rounded-pill px-5 shadow">
                <i class="fas fa-check-circle me-2"></i>
                送出訂單
                <i class="fas fa-paper-plane ms-2"></i>
            </button>
        </div>
    </form>

    {{-- 地標選擇功能已改為 Dropdown 方式 --}}

    {{-- 優化樣式 --}}
    <style>
    /* 地標功能樣式 */
    .landmark-input-group {
        position: relative;
    }

    .landmark-btn {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 10;
        border: none;
        background: none;
        padding: 8px 12px;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .landmark-btn:hover {
        background-color: #f8f9fa;
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .landmark-input {
        padding-right: 50px;
        transition: all 0.3s ease;
    }

    .landmark-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        transform: translateY(-1px);
    }

    /* 卡片樣式優化 */
    .card {
        transition: all 0.3s ease;
        border: none !important;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
    }

    /* 漸層背景 */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
    }

    .bg-gradient-success {
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%) !important;
    }

    .bg-gradient-danger {
        background: linear-gradient(135deg, #dc3545 0%, #bd2130 100%) !important;
    }

    .bg-gradient-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #545b62 100%) !important;
    }

    .bg-gradient-dark {
        background: linear-gradient(135deg, #343a40 0%, #23272b 100%) !important;
    }

    /* 表單控件優化 */
    .form-control, .form-select {
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
        transform: translateY(-1px);
    }

    .rounded-pill {
        border-radius: 50px !important;
    }

    /* 按鈕優化 */
    .btn {
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }

    /* 標籤樣式 */
    .form-label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #495057;
    }

    /* 顏色變量 */
    .text-purple {
        color: #6f42c1 !important;
    }

    /* 響應式設計 */
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem !important;
        }
        
        .landmark-input {
            padding-right: 45px;
        }
        
        .landmark-btn {
            padding: 6px 10px;
        }
        
        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .card-body {
            padding: 1rem !important;
        }
        
        .modal-dialog {
            margin: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
    }

    /* 載入動畫 */
    .spinner-border {
        animation: spinner-border 0.75s linear infinite;
    }

    @keyframes spinner-border {
        to {
            transform: rotate(360deg);
        }
    }

    /* Alert 樣式優化 */
    .alert {
        border: none;
        border-radius: 12px;
        font-weight: 500;
    }

    /* Input Group 優化 */
    .input-group .btn {
        border-radius: 0;
        margin: 0;
    }

    .input-group .btn:first-of-type {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .input-group .btn:last-of-type {
        border-top-right-radius: 50px;
        border-bottom-right-radius: 50px;
    }

    .rounded-start-pill {
        border-top-left-radius: 50px !important;
        border-bottom-left-radius: 50px !important;
    }

    .rounded-end-pill {
        border-top-right-radius: 50px !important;
        border-bottom-right-radius: 50px !important;
    }

    /* 地標彈出視窗樣式 */
    .landmark-dropdown {
        border-radius: 12px !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
    }

    .landmark-header {
        border-radius: 12px 12px 0 0 !important;
    }

    .landmark-footer {
        border-radius: 0 0 12px 12px !important;
    }

    .landmark-card {
        border: 1px solid #e9ecef !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
    }

    .landmark-card:hover {
        border-color: #007bff !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        transform: translateY(-2px) !important;
    }

    .landmark-icon {
        transition: all 0.3s ease;
    }

    .landmark-card:hover .landmark-icon {
        transform: scale(1.1);
    }

    .category-filter.active {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: white !important;
    }

    .nav-pills .nav-link {
        border-radius: 0 !important;
        border: none !important;
        color: #6c757d !important;
        transition: all 0.3s ease !important;
    }

    .nav-pills .nav-link.active {
        background-color: #007bff !important;
        color: white !important;
    }

    .nav-pills .nav-link:hover {
        background-color: #f8f9fa !important;
        color: #007bff !important;
    }

    .landmark-content {
        background-color: #fafafa;
    }

    .landmark-content::-webkit-scrollbar {
        width: 6px;
    }

    .landmark-content::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .landmark-content::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .landmark-content::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* 分類按鈕樣式優化 */
    .landmark-categories .btn {
        transition: all 0.3s ease;
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }

    .landmark-categories .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    /* 手機版優化 */
    @media (max-width: 768px) {
        .landmark-dropdown {
            width: 450px !important;
            max-height: 500px !important;
        }
        
        .landmark-categories .btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .landmark-content {
            max-height: 300px !important;
        }
    }

    @media (max-width: 576px) {
        .landmark-dropdown {
            width: 350px !important;
            max-height: 450px !important;
        }
        
        .landmark-card {
            padding: 0.75rem !important;
        }
        
        .landmark-icon {
            width: 32px !important;
            height: 32px !important;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeLandmarkDropdowns();
        
        function initializeLandmarkDropdowns() {
            // 處理 * 觸發搜尋
            const landmarkInputs = document.querySelectorAll('.landmark-input');
            landmarkInputs.forEach(input => {
                input.addEventListener('input', function(e) {
                    const inputValue = e.target.value;
                    if (inputValue.includes('*')) {
                        // 移除星號並觸發搜尋
                        const keyword = inputValue.replace('*', '');
                        e.target.value = keyword;
                        
                        // 開啟對應的 dropdown 並搜尋
                        const dropdown = e.target.closest('.landmark-input-group').querySelector('.dropdown');
                        const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
                        const searchInput = dropdown.querySelector('.landmark-search-input');
                        
                        // 設定搜尋關鍵字
                        searchInput.value = keyword;
                        
                        // 開啟 dropdown
                        const bsDropdown = new bootstrap.Dropdown(dropdownToggle);
                        bsDropdown.show();
                        
                        // 執行搜尋
                        setTimeout(() => {
                            searchLandmarksInDropdown(dropdown, keyword);
                        }, 100);
                    }
                });
            });
            
            // 綁定 dropdown 搜尋按鈕
            document.querySelectorAll('.landmark-search-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const dropdown = this.closest('.dropdown');
                    const searchInput = dropdown.querySelector('.landmark-search-input');
                    const keyword = searchInput.value.trim();
                    
                    if (keyword) {
                        searchLandmarksInDropdown(dropdown, keyword);
                    }
                });
            });
            
            // 搜尋輸入框 Enter 鍵
            document.querySelectorAll('.landmark-search-input').forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const dropdown = this.closest('.dropdown');
                        const keyword = this.value.trim();
                        
                        if (keyword) {
                            searchLandmarksInDropdown(dropdown, keyword);
                        }
                    }
                });
            });
        }
        
        // 在 dropdown 中搜尋地標
        function searchLandmarksInDropdown(dropdown, keyword) {
            const resultsContainer = dropdown.querySelector('.landmark-results');
            resultsContainer.innerHTML = '<div class="text-center py-2">搜尋中...</div>';
            
            fetch(`/landmarks-search?keyword=${encodeURIComponent(keyword)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        displayLandmarkResults(resultsContainer, data.data, dropdown);
                    } else {
                        resultsContainer.innerHTML = '<div class="text-muted py-2">查無符合條件的地標</div>';
                    }
                })
                .catch(error => {
                    console.error('搜尋地標錯誤:', error);
                    resultsContainer.innerHTML = '<div class="text-danger py-2">搜尋失敗，請稍後再試</div>';
                });
        }
        
        // 顯示搜尋結果
        function displayLandmarkResults(container, landmarks, dropdown) {
            let html = '';
            
            landmarks.forEach(landmark => {
                const fullAddress = landmark.city + landmark.district + landmark.address;
                const categoryBadge = getCategoryBadge(landmark.category);
                
                html += `
                    <div class="landmark-item p-2 border-bottom" style="cursor: pointer;" 
                         onclick="selectLandmarkFromDropdown('${fullAddress}', ${landmark.id}, this)">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                    ${landmark.name}
                                    ${categoryBadge}
                                </h6>
                                <small class="text-muted">${fullAddress}</small>
                            </div>
                            <small class="text-muted">${landmark.usage_count || 0}次</small>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // 獲取分類標籤
        function getCategoryBadge(category) {
            const categories = {
                'medical': { text: '醫療', class: 'bg-danger' },
                'transport': { text: '交通', class: 'bg-primary' },
                'education': { text: '教育', class: 'bg-success' },
                'government': { text: '政府機關', class: 'bg-warning' },
                'commercial': { text: '商業', class: 'bg-info' },
                'general': { text: '一般', class: 'bg-secondary' }
            };
            
            const cat = categories[category] || { text: category, class: 'bg-secondary' };
            return `<span class="badge ${cat.class}">${cat.text}</span>`;
        }
    });
    
    // 選擇地標（從 dropdown）
    function selectLandmarkFromDropdown(address, landmarkId, element) {
        const dropdown = element.closest('.dropdown');
        const inputGroup = dropdown.closest('.landmark-input-group');
        const targetInput = inputGroup.querySelector('.landmark-input');
        
        // 填入地址
        targetInput.value = address;
        
        // 儲存地標 ID
        targetInput.setAttribute('data-landmark-id', landmarkId);
        
        // 關閉 dropdown
        const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
        const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
        if (bsDropdown) {
            bsDropdown.hide();
        }
        
        // 更新使用次數
        fetch('/landmarks-usage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ landmark_id: landmarkId })
        }).catch(error => {
            console.error('更新地標使用次數失敗:', error);
        });
    }
    </script>



