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
                            <input type="text" name="pickup_address" id="pickup_address" class="form-control landmark-input rounded-pill"
                                   value="{{ old('pickup_address', $order->pickup_address ?? ($customer->addresses[0] ?? '')) }}"
                                   placeholder="輸入地址或搜尋地標（使用*觸發搜尋，如：台北*）">
                            <button type="button" class="btn btn-outline-secondary landmark-btn" 
                                    onclick="openOrderLandmarkModal('pickup')">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
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
                            <input type="text" name="dropoff_address" id="dropoff_address" class="form-control landmark-input rounded-pill"
                                   value="{{ old('dropoff_address', $order->dropoff_address ?? '') }}"
                                   placeholder="輸入地址或搜尋地標（使用*觸發搜尋，如：台北*）">
                            <button type="button" class="btn btn-outline-secondary landmark-btn" 
                                    onclick="openOrderLandmarkModal('dropoff')">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
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

    <!-- 地標選擇 Modal -->
    <div class="modal fade" id="orderLandmarkModal" tabindex="-1" aria-labelledby="orderLandmarkModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header text-white" id="orderLandmarkModalHeader">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-map-marker-alt me-3 fs-4"></i>
                        <div>
                            <h5 class="modal-title mb-0" id="orderLandmarkModalLabel">選擇地標</h5>
                            <small class="text-light opacity-75" id="orderLandmarkModalSubtitle">快速填入常用地址</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="關閉"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- 搜尋區域 -->
                    <div class="landmark-search-area p-3 bg-light border-bottom">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="orderModalLandmarkSearch" class="form-control border-start-0 border-end-0" 
                                   placeholder="搜尋地標名稱或地址...">
                            <button class="btn btn-primary" type="button" onclick="searchOrderLandmarksInModal()">
                                <i class="fas fa-search me-1"></i>搜尋
                            </button>
                        </div>
                    </div>

                    <!-- 分類篩選區域 -->
                    <div class="landmark-categories px-3 py-2 border-bottom bg-light">
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm category-filter active" 
                                    data-category="all" onclick="filterLandmarksByCategory('all', this)">
                                <i class="fas fa-th me-1"></i>全部
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm category-filter" 
                                    data-category="medical" onclick="filterLandmarksByCategory('medical', this)">
                                <i class="fas fa-hospital me-1"></i>醫療
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm category-filter" 
                                    data-category="transport" onclick="filterLandmarksByCategory('transport', this)">
                                <i class="fas fa-bus me-1"></i>交通
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm category-filter" 
                                    data-category="education" onclick="filterLandmarksByCategory('education', this)">
                                <i class="fas fa-school me-1"></i>教育
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm category-filter" 
                                    data-category="government" onclick="filterLandmarksByCategory('government', this)">
                                <i class="fas fa-building me-1"></i>政府
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm category-filter" 
                                    data-category="commercial" onclick="filterLandmarksByCategory('commercial', this)">
                                <i class="fas fa-store me-1"></i>商業
                            </button>
                        </div>
                    </div>

                    <!-- 分頁標籤 -->
                    <div class="landmark-tabs">
                        <ul class="nav nav-pills nav-justified bg-light" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="modal-search-tab" data-bs-toggle="pill" 
                                        data-bs-target="#modal-search-content" type="button" role="tab">
                                    <i class="fas fa-search me-1"></i>搜尋結果
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="modal-popular-tab" data-bs-toggle="pill" 
                                        data-bs-target="#modal-popular-content" type="button" role="tab"
                                        onclick="loadOrderPopularLandmarksInModal()">
                                    <i class="fas fa-fire me-1"></i>熱門地標
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="modal-recent-tab" data-bs-toggle="pill" 
                                        data-bs-target="#modal-recent-content" type="button" role="tab"
                                        onclick="loadOrderRecentLandmarksInModal()">
                                    <i class="fas fa-history me-1"></i>最近使用
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- 內容區域 -->
                    <div class="landmark-content" style="max-height: 400px; overflow-y: auto;">
                        <div class="tab-content">
                            <!-- 搜尋結果 -->
                            <div class="tab-pane fade show active" id="modal-search-content" role="tabpanel">
                                <div id="orderModalLandmarkResults" class="p-3">
                                    <div class="text-center py-4">
                                        <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
                                        <p class="text-muted mb-0">請輸入關鍵字搜尋地標</p>
                                        <small class="text-muted">或直接在地址欄輸入關鍵字加上 * 觸發搜尋</small>
                                    </div>
                                </div>
                            </div>
                            <!-- 熱門地標 -->
                            <div class="tab-pane fade" id="modal-popular-content" role="tabpanel">
                                <div id="orderModalPopularLandmarks" class="p-3"></div>
                            </div>
                            <!-- 最近使用 -->
                            <div class="tab-pane fade" id="modal-recent-content" role="tabpanel">
                                <div id="orderModalRecentLandmarks" class="p-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <small class="text-muted me-auto">
                        <i class="fas fa-lightbulb me-1"></i>
                        提示：點擊地標快速填入地址
                    </small>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                </div>
            </div>
        </div>
    </div>

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

    /* Modal 動畫簡化 */
    #orderLandmarkModal .modal-dialog {
        transition: none;
    }

    /* 地標項目選擇效果 - 簡化版本 */
    .landmark-item {
        cursor: pointer;
    }

    .landmark-item:hover {
        background-color: #f8f9fa;
    }

    /* Modal 載入動畫 */
    .modal-loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* 簡化過渡效果 */
    .modal-header {
        transition: none;
    }

    .nav-pills .nav-link {
        transition: none;
    }

    .category-filter {
        transition: none;
    }

    /* 手機版優化 */
    @media (max-width: 768px) {
        #orderLandmarkModal .modal-dialog {
            margin: 0.5rem;
        }
        
        .landmark-categories .btn {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .landmark-content {
            max-height: 350px !important;
        }
    }

    @media (max-width: 576px) {
        #orderLandmarkModal .modal-dialog {
            margin: 0.25rem;
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
    // 全域變數
    let currentAddressType = ''; // 'pickup' 或 'dropoff'
    let landmarkModalInstance = null; // 單例 Modal 實例
    let landmarkModalElement = null; // Modal DOM 元素

    // 初始化函數
    function initializeLandmarkModal() {
        landmarkModalElement = document.getElementById('orderLandmarkModal');
        if (!landmarkModalElement) {
            console.error('找不到地標 Modal 元素: #orderLandmarkModal');
            return;
        }

        // 創建單一 Modal 實例
        landmarkModalInstance = new bootstrap.Modal(landmarkModalElement);

        // 綁定事件監聽器
        addEventListeners();
    }

    // 統一管理事件監聽器
    function addEventListeners() {
        // 處理地址輸入框的 * 觸發搜尋
        document.querySelectorAll('.landmark-input').forEach(input => {
            input.removeEventListener('input', handleLandmarkInputStar);
            input.addEventListener('input', handleLandmarkInputStar);
        });

        // Modal 搜尋輸入框 Enter 鍵
        const modalSearchInput = document.getElementById('orderModalLandmarkSearch');
        if (modalSearchInput) {
            modalSearchInput.removeEventListener('keypress', handleModalSearchKeypress);
            modalSearchInput.addEventListener('keypress', handleModalSearchKeypress);
        }

        // Modal 顯示時的處理
        landmarkModalElement.addEventListener('show.bs.modal', function (event) {
            updateModalUI();
        });

        // Modal 隱藏後的清理
        landmarkModalElement.addEventListener('hidden.bs.modal', function () {
            resetModalState();
        });
    }

    // 開啟地標 Modal
    function openOrderLandmarkModal(addressType) {
        if (!landmarkModalInstance) {
            console.error('Modal 實例未初始化');
            return;
        }
        currentAddressType = addressType;
        landmarkModalInstance.show();
    }

    // 更新 Modal 介面
    function updateModalUI() {
        const modalHeader = document.getElementById('orderLandmarkModalHeader');
        const modalTitle = document.getElementById('orderLandmarkModalLabel');
        const modalSubtitle = document.getElementById('orderLandmarkModalSubtitle');

        if (currentAddressType === 'pickup') {
            modalHeader.className = 'modal-header text-white bg-gradient-success';
            modalTitle.innerHTML = '<i class="fas fa-play-circle me-2"></i>選擇上車地標';
            modalSubtitle.textContent = '選擇常用的上車地點';
        } else {
            modalHeader.className = 'modal-header text-white bg-gradient-danger';
            modalTitle.innerHTML = '<i class="fas fa-stop-circle me-2"></i>選擇下車地標';
            modalSubtitle.textContent = '選擇常用的下車地點';
        }
    }

    // 重設 Modal 狀態
    function resetModalState() {
        currentAddressType = '';
        // 清空搜尋框和結果
        const searchInput = document.getElementById('orderModalLandmarkSearch');
        if (searchInput) searchInput.value = '';
        
        const resultsContainer = document.getElementById('orderModalLandmarkResults');
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">請輸入關鍵字搜尋地標</p>
                </div>
            `;
        }
        // 重設分頁到第一個
        const firstTab = document.querySelector('#modal-search-tab');
        if (firstTab) {
            new bootstrap.Tab(firstTab).show();
        }
    }

    // 處理地址輸入框星號觸發
    function handleLandmarkInputStar(e) {
        const inputValue = e.target.value;
        if (inputValue.includes('*')) {
            const keyword = inputValue.replace('*', '').trim();
            e.target.value = keyword;
            
            const addressType = e.target.name === 'pickup_address' ? 'pickup' : 'dropoff';
            openOrderLandmarkModal(addressType);

            // 等待 Modal 顯示後再搜尋
            landmarkModalElement.addEventListener('shown.bs.modal', () => {
                const modalSearchInput = document.getElementById('orderModalLandmarkSearch');
                if (modalSearchInput) {
                    modalSearchInput.value = keyword;
                    searchOrderLandmarksInModal();
                }
            }, { once: true });
        }
    }

    // 處理 Modal 搜尋輸入框按鍵
    function handleModalSearchKeypress(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchOrderLandmarksInModal();
        }
    }

    // 在 Modal 中搜尋地標
    function searchOrderLandmarksInModal() {
        const keyword = document.getElementById('orderModalLandmarkSearch').value.trim();
        const resultsContainer = document.getElementById('orderModalLandmarkResults');
        
        // 確保搜尋結果 tab 是 active
        new bootstrap.Tab(document.getElementById('modal-search-tab')).show();

        if (!keyword) {
            resultsContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">請輸入關鍵字以搜尋地標</p>
                </div>
            `;
            return;
        }
        
        resultsContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">搜尋中...</p></div>';
        
        fetch(`/landmarks-search?keyword=${encodeURIComponent(keyword)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    displayLandmarkResultsInModal(resultsContainer, data.data);
                } else {
                    resultsContainer.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">查無符合條件的地標</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('搜尋地標錯誤:', error);
                resultsContainer.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle text-danger mb-2" style="font-size: 2rem;"></i>
                        <p class="text-danger mb-0">搜尋失敗</p>
                    </div>
                `;
            });
    }

    // 顯示搜尋結果
    function displayLandmarkResultsInModal(container, landmarks) {
        let html = landmarks.map(landmark => {
            const fullAddress = (landmark.city || '') + (landmark.district || '') + (landmark.address || '');
            const categoryBadge = getCategoryBadge(landmark.category);
            const categoryIcon = getCategoryIcon(landmark.category);
            const usageCount = landmark.usage_count || 0;

            return `
                <div class="landmark-item border rounded-3 mb-2 p-3 landmark-card" 
                     data-category="${landmark.category}"
                     onclick="selectLandmarkFromModal('${fullAddress}', ${landmark.id})">
                    <div class="d-flex align-items-start">
                        <div class="landmark-icon me-3 d-flex align-items-center justify-content-center rounded-circle bg-light" style="width: 40px; height: 40px;">
                            <i class="${categoryIcon} text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-1 fw-bold text-dark">${landmark.name}</h6>
                                <div class="d-flex align-items-center">
                                    ${categoryBadge}
                                    <small class="text-muted ms-2"><i class="fas fa-chart-bar me-1"></i>${usageCount}</small>
                                </div>
                            </div>
                            <p class="text-muted mb-0 small"><i class="fas fa-map-marker-alt me-1"></i>${fullAddress}</p>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        container.innerHTML = html || '<p class="text-center text-muted">沒有結果</p>';
    }

    // 選擇地標
    function selectLandmarkFromModal(address, landmarkId) {
        const targetInput = document.getElementById(currentAddressType + '_address');
        if (targetInput) {
            targetInput.value = address;
            targetInput.setAttribute('data-landmark-id', landmarkId);
        }
        landmarkModalInstance.hide();
        updateLandmarkUsage(landmarkId);
        saveToRecentLandmarks(landmarkId, address);
    }

    // 更新使用次數
    function updateLandmarkUsage(landmarkId) {
        fetch('/landmarks-usage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ landmark_id: landmarkId })
        }).catch(console.error);
    }

    // 分類篩選
    function filterLandmarksByCategory(category, button) {
        document.querySelectorAll('.category-filter').forEach(btn => btn.classList.remove('active'));
        button.classList.add('active');
        
        const allItems = document.querySelectorAll('#orderModalLandmarkResults .landmark-item');
        let hasVisibleItems = false;
        allItems.forEach(item => {
            const isVisible = category === 'all' || item.dataset.category === category;
            item.style.display = isVisible ? 'block' : 'none';
            if (isVisible) hasVisibleItems = true;
        });

        const resultsContainer = document.getElementById('orderModalLandmarkResults');
        if (!hasVisibleItems) {
            resultsContainer.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-filter text-muted mb-2" style="font-size: 2rem;"></i>
                    <p class="text-muted mb-0">此分類下暫無地標</p>
                </div>
            `;
        }
    }

    // 載入熱門地標
    function loadOrderPopularLandmarksInModal() {
        const container = document.getElementById('orderModalPopularLandmarks');
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
        fetch('/landmarks-popular')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    displayLandmarkResultsInModal(container, data.data);
                } else {
                    container.innerHTML = '<p class="text-center text-muted p-3">暫無熱門地標</p>';
                }
            }).catch(() => container.innerHTML = '<p class="text-center text-danger p-3">載入失敗</p>');
    }

    // 載入最近使用
    function loadOrderRecentLandmarksInModal() {
        const container = document.getElementById('orderModalRecentLandmarks');
        const recentLandmarks = JSON.parse(localStorage.getItem('recentLandmarks') || '[]');
        if (recentLandmarks.length === 0) {
            container.innerHTML = '<p class="text-center text-muted p-3">暫無最近使用記錄</p>';
            return;
        }
        
        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
        const landmarkIds = recentLandmarks.map(item => item.id);
        
        fetch('/landmarks-by-ids', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ ids: landmarkIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayLandmarkResultsInModal(container, data.data);
            } else {
                container.innerHTML = '<p class="text-center text-muted p-3">無法載入最近使用記錄</p>';
            }
        }).catch(() => container.innerHTML = '<p class="text-center text-danger p-3">載入失敗</p>');
    }

    // 保存到最近使用
    function saveToRecentLandmarks(landmarkId, address) {
        let recent = JSON.parse(localStorage.getItem('recentLandmarks') || '[]');
        recent = recent.filter(item => item.id !== landmarkId);
        recent.unshift({ id: landmarkId, address: address, timestamp: Date.now() });
        localStorage.setItem('recentLandmarks', JSON.stringify(recent.slice(0, 20)));
    }

    // 輔助函數
    function getCategoryBadge(category) {
        const categories = {
            'medical': { text: '醫療', class: 'bg-danger' },
            'transport': { text: '交通', class: 'bg-primary' },
            'education': { text: '教育', class: 'bg-success' },
            'government': { text: '政府', class: 'bg-warning' },
            'commercial': { text: '商業', class: 'bg-info' }
        };
        const cat = categories[category] || { text: '一般', class: 'bg-secondary' };
        return `<span class="badge ${cat.class} rounded-pill">${cat.text}</span>`;
    }

    function getCategoryIcon(category) {
        const icons = {
            'medical': 'fas fa-hospital',
            'transport': 'fas fa-bus',
            'education': 'fas fa-school',
            'government': 'fas fa-building',
            'commercial': 'fas fa-store'
        };
        return icons[category] || 'fas fa-map-marker-alt';
    }

    // DOM 載入完成後初始化
    document.addEventListener('DOMContentLoaded', initializeLandmarkModal);
    </script>


