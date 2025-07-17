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

        <div class="row mb-7">

            <div class="h4 col-md-6 text-danger">
                <label class="form-label">訂單類型：</label>
                <span>{{ old('order_type', $order->order_type ?? $customer->county_care ?? '') }}</span>
                <input type="hidden" name="order_type" value="{{ old('order_type', $order->order_type ?? $customer->county_care ?? '') }}">
            </div>
        </div>
        <!--個案資料表ID-->
        <input type="hidden" name="customer_id" value="{{ old('customer_id', $order->customer_id ?? $customer->id ?? '') }}">

        <div class="card container-fluid" style="border:1px solid DodgerBlue;">
            {{-- 客戶資訊 --}}
            <!--<h5 class="mt-3 text-center">客戶資訊</h5>
            <hr style="border-top: 1px solid #000;">-->
            <div class="row mb-3">
                <div class="col-md-1 mt-3">
                    <label>個案姓名</label>
                    <input type="text" name="customer_name" class="form-control"
                        value="{{ old('customer_name', $order->customer_name ?? $customer->name ?? '') }}" readonly>
                </div>
                <div class="col-md-3 mt-3">
                    <label>個案身分證字號</label>
                    <input type="text" name="customer_id_number" class="form-control"
                        value="{{ old('customer_id_number', $order->customer_id_number ?? $customer->id_number ?? '') }}" readonly>
                </div>
                <div class="col-md-3 mt-3">
                    <label>個案電話</label>
                    <input type="text" name="customer_phone" class="form-control"
                        value="{{ old('customer_phone', $order->customer_phone ?? ($customer->phone_number[0] ?? '')) }}">
                </div>
                <div class="col-md-3 mt-3">
                    <label>個案身份別</label>
                    <input type="text" name="identity" class="form-control"
                        value="{{ old('identity', $order->identity ?? $customer->identity ?? '') }}" readonly>
                </div>
                <div class="col-md-2 mt-3">
                <label>交通公司</label>
                    <input type="text" name="service_company" class="form-control text-primary"
                        value="{{ old('service_company', $order->service_company ?? $customer->service_company ?? '') }}" readonly>
                 </div>
                <div class="col-md-3 mt-3">
                    <label>共乘對象</label>
                    <div class="input-group">
                        <input type="text" name="carpoolSearchInput" id="carpoolSearchInput" class="form-control" placeholder="名字、ID、電話" value="{{ old('carpoolSearchInput', $order->carpool_name ?? '') }}">
                        <button type="button" class="btn btn-success" id="searchCarpoolBtn">查詢</button>
                        <button type="button" class="btn btn-outline-danger" id="clearCarpoolBtn">清除</button>
                    </div>
                </div>

                <div class="col-md-2 mt-3">
                    <label>共乘身分證字號</label>
                    <div class="input-group">
                        <input type="text" name="carpool_id_number" id="carpool_id_number" class="form-control" placeholder="" readonly onfocus="this.blur();" value="{{ old('carpool_id_number', $order->carpool_id ?? '') }}">
                    </div>
                </div>
                <div class="col-md-2 mt-3">
                    <label>共乘電話</label>
                    <div class="input-group">
                        <input type="text" name="carpool_phone_number" id="carpool_phone_number" class="form-control" placeholder="" readonly onfocus="this.blur();" value="{{ old('carpool_phone_number', $order->carpool_phone ?? '') }}">
                    </div>
                </div>
                <div class="col-md-5 mt-3">
                    <label>共乘乘客地址</label>
                    <div class="input-group">
                        <input type="text" name="carpool_addresses" id="carpool_addresses" class="form-control" placeholder="" readonly onfocus="this.blur();" value="{{ old('carpool_addresses', $order->carpool_addresses ?? '') }}">
                        <!-- 隱藏用於存儲客戶ID -->
                        <input type="hidden" name="carpool_customer_id" id="carpool_customer_id" class="form-control" placeholder="" readonly onfocus="this.blur();" value="{{ old('carpool_customer_id', $order->carpool_customer_id ?? '') }}">
                    </div>

                </div>
                    <input type="hidden" name="carpool_with" id="carpool_with" value="{{ old('carpool_with', $order->carpool_name ?? '') }}">
                    <div class="mt-1" id="carpoolResults"></div>
            </div>

    </div>



        <div class="card container-fluid" style="border:1px solid Tomato;">
        {{-- 用車資訊 --}}
        <!--<h5 class="mt-3 text-center">用車資訊</h5>
        <hr style="border-top: 1px solid #000;">-->
        <div class="row mb-3 mt-3">
                <div class="col-md-2">
                    <label>用車日期</label>
                    <input type="date" name="ride_date" class="form-control" value="{{ old('ride_date', $order->ride_date ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label>用車時間（格式：時:分）</label>
                    <input type="text" name="ride_time" class="form-control"
                        pattern="^([01]\d|2[0-3]):[0-5]\d$"
                        placeholder="例如：13:45"
                        value="{{ old('ride_time', isset($order) ? substr($order->ride_time, 0, 5) : '') }}">
                </div>
                <div class="col-md-3">
                    <label>回程時間（格式：時:分）</label>
                    <input type="text" name="back_time" class="form-control"
                        pattern="^([01]\d|2[0-3]):[0-5]\d$"
                        placeholder="例如：13:45"
                        value="{{ old('ride_time') }}">
                </div>
                <div class="col-md-1">
                    <label>陪同人數</label>
                    <input type="number" name="companions" class="form-control" min="0" value="{{ old('companions', $order->companions ?? 0) }}">
                </div>

                <div class="col-md-1">
                    <label>是否輪椅</label>
                    <select name="wheelchair" class="form-select">
                        <option value="0" {{ in_array(old('wheelchair', $order->wheelchair ?? ($customer->wheelchair ?? 0)) ,['0', '否']) ? 'selected' : '' }}>否</option>
                        <option value="1" {{ in_array(old('wheelchair', $order->wheelchair ?? ($customer->wheelchair ?? 0)) ,['1', '是']) ? 'selected' : '' }}>是</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>爬梯機</label>
                    <select name="stair_machine" class="form-select">
                        <option value="0"{{ in_array(old('stair_machine', $order->stair_machine ?? ($customer->stair_climbing_machine ?? 0)),['0', '否']) ? 'selected' : '' }}>否</option>
                        <option value="1"{{ in_array(old('stair_machine', $order->stair_machine ?? ($customer->stair_climbing_machine ?? 0)),['1', '是']) ? 'selected' : '' }}>是</option>
                    </select>
                </div>


            {{-- 上車資訊 --}}
            <!--<h5 class="mt-4">上車地點</h5>-->
            <div class="row mb-3">
                <div class="col-md-12 mt-3">
                    <label>上車地址 (要有XX市XX區)</label>
                    <div class="landmark-input-group">
                        <input type="text" name="pickup_address" class="form-control landmark-input"
                               value="{{ old('pickup_address', $order->pickup_address ?? ($customer->addresses[0] ?? '')) }}"
                               placeholder="輸入地址或搜尋地標（使用*觸發搜尋，如：台北*）">
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-secondary landmark-btn dropdown-toggle" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end landmark-dropdown" style="width: 400px; max-height: 400px; overflow-y: auto;">
                                <div class="p-3">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control landmark-search-input" placeholder="搜尋地標...">
                                        <button class="btn btn-primary landmark-search-btn" type="button">搜尋</button>
                                    </div>
                                    <div class="landmark-results"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small class="form-text text-muted">提示：輸入關鍵字後加上*可搜尋地標，如：台北*</small>
                    @error('pickup_address')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
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
            <div class="row mb-3 mt-1">
                <div class="col-md-12">
                    <label>下車地址  (要有XX市XX區)</label>
                    <div class="landmark-input-group">
                        <input type="text" name="dropoff_address" class="form-control landmark-input"
                               value="{{ old('dropoff_address', $order->dropoff_address ?? '') }}"
                               placeholder="輸入地址或搜尋地標（使用*觸發搜尋，如：台北*）">
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-secondary landmark-btn dropdown-toggle" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end landmark-dropdown" style="width: 400px; max-height: 400px; overflow-y: auto;">
                                <div class="p-3">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control landmark-search-input" placeholder="搜尋地標...">
                                        <button class="btn btn-primary landmark-search-btn" type="button">搜尋</button>
                                    </div>
                                    <div class="landmark-results"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <small class="form-text text-muted">提示：輸入關鍵字後加上*可搜尋地標，如：台北*</small>
                    @error('dropoff_address')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>


            <div class="row">
                {{-- 額外資訊 --}}
                <div class="col-md-6 mb-3">
                    <!--這邊的special_order指的是黑名單狀態-->
                    <label>黑名單個案</label>
                    <select name="special_order" class="form-select">
                        <option value="0" {{ old('special_order', $order->special_order ?? 0) == 0 ? 'selected' : '' }}>否</option>
                        <option value="1" {{ old('special_order', $order->special_order ?? 0) == 1 ? 'selected' : '' }}>是</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>特別狀態訂單 (說明:T9的粉紅色)</label>
                    <select name="special_status" class="form-select">
                    <option value="一般" {{ old('special_status', $order->special_status ?? '一般') == '一般' ? 'selected' : '' }}>一般</option>
                    <option value="黑名單" {{ old('special_status', $order->special_status ?? '一般') == '黑名單' ? 'selected' : '' }}>黑名單</option>
                    <option value="個管單" {{ old('special_status', $order->special_status ?? '一般') == '個管單' ? 'selected' : '' }}>個管單</option>
                    <option value="網頁" {{ old('special_status', $order->special_status ?? '一般') == '網頁' ? 'selected' : '' }}>網頁</option>
                </select>
                </div>
                <div class="mb-3">
                    <label>訂單備註</label>
                    <textarea name="remark" rows="3" class="form-control">{{ old('remark', $order->remark ?? '') }}</textarea>
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
                    <input type="text" name ="driver_fleet_number" id="driver_fleet_number" class="form-control" placeholder="輸入隊編" value="{{ old('driver_fleet_number', $order->driver_fleet_number ?? '') }}">
                    <button type="button" class="btn btn-success" id="searchDriverBtn">查詢</button>
                    <button type="button" class="btn btn-outline-danger" id="clearDriverBtn">清除</button>
                </div>
            </div>
            <div class="col-md-4">
                <label>駕駛姓名</label>
                <input type="text" name="driver_name" id="driver_name" class="form-control" readonly value="{{ old('driver_name', $order->driver_name ?? '') }}">
            </div>
            <div class="col-md-4">
                <label>車牌號碼</label>
                <input type="text" name="driver_plate_number" id="driver_plate_number" class="form-control" readonly value="{{ old('driver_plate_number', $order->driver_plate_number ?? '') }}">
            </div>
            {{-- 隱藏 driver_id --}}
            <input type="hidden" name="driver_id" id="driver_id" value="{{ old('driver_id', $order->driver_id ?? '') }}">
        </div>



                {{-- 基本資料 --}}
        <div class="row mb-3">
            <div class="col-md-4 mt-3">
                <label>訂單狀態</label>
                <select name="status" class="form-select">
                    <option value="open" {{ old('status', $order->status ?? 'open') === 'open' ? 'selected' : '' }}>可派遣</option>
                    <option value="assigned" {{ old('status', $order->status ?? 'open') === 'assigned' ? 'selected' : '' }}>已指派</option>
                    <option value="replacement" {{ old('status', $order->status ?? 'open') === 'replacement' ? 'selected' : '' }}>候補派遣</option>
                    <option value="blocked" {{ old('status', $order->status ?? 'open') === 'blocked' ? 'selected' : '' }}>黑名單</option>
                    <option value="cancelled" {{ old('status', $order->status ?? 'open') === 'cancelled' ? 'selected' : '' }}>已取消</option>
                </select>
            </div>

            <div class="col-md-4 mt-3">
                <label>建單人員</label>
                <input type="text" name="created_by" class="form-control"
                    value="{{ old('created_by', $order->created_by ?? ($user?->name ?? '')) }}" readonly>
            </div>
        </div>

                {{-- 提交按鈕 --}}
        <div class="mb-3 text-end">
            <button type="submit" class="btn btn-success">&#10004送出訂單&#128203;</button>
        </div>
    </form>

    {{-- 地標選擇功能已改為 Dropdown 方式 --}}

    {{-- 地標功能的樣式和 JavaScript --}}
    <style>
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
        padding: 5px 10px;
    }

    .landmark-btn:hover {
        background-color: #f8f9fa;
        border-radius: 4px;
    }

    .landmark-input {
        padding-right: 45px;
    }

    .landmark-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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



