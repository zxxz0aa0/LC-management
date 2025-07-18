# Orders 資料夾重構規劃文件

## 📋 **專案資訊**
- **專案名稱**: LC-management 長照服務管理系統
- **重構模組**: Orders 訂單管理系統
- **框架**: Laravel 10 + AdminLTE 3.2 + Bootstrap 5.3
- **規劃日期**: 2025-07-18
- **負責人**: chanraymon
- **文件版本**: v1.0

---

## 🎯 **重構目標**

### 📌 **主要問題**
1. **代碼重複**：相同的 JavaScript 代碼出現在多個文件中
2. **職責不清**：`index.blade.php` 包含不應該有的表單功能
3. **Modal 衝突**：編輯按鈕導致重複初始化，造成閃爍問題
4. **架構混亂**：文件之間的依賴關係複雜
5. **冗餘文件**：`list.blade.php` 和 `show.blade.php` 未被使用

### 🎯 **重構目標**
1. **單一職責原則**：每個文件只負責自己的功能
2. **組件化設計**：可重用的組件，避免代碼重複
3. **模組化 JavaScript**：獨立的 JavaScript 文件，避免衝突
4. **清晰的檔案結構**：容易理解和維護
5. **標準化流程**：統一的開發和維護流程

---

## 🏗️ **新架構設計**

### 📁 **檔案結構**
```
orders/
├── index.blade.php           # 訂單列表頁面 (純列表功能)
├── create.blade.php          # 新增訂單頁面 (完整功能)
├── edit.blade.php            # 編輯訂單頁面 (完整功能)
├── show.blade.php            # 訂單詳細頁面 (新增，替代Modal)
└── components/               # 新增：組件化設計
    ├── customer-search.blade.php    # 客戶搜尋組件
    ├── order-form.blade.php         # 表單組件
    ├── order-table.blade.php        # 表格組件
    ├── order-detail.blade.php       # 訂單詳細組件
    └── landmark-modal.blade.php     # 地標選擇組件
```

### 📄 **JavaScript 檔案結構**
```
public/js/orders/
├── index.js                  # 列表頁面 JavaScript
├── form.js                   # 表單頁面 JavaScript
├── landmark-modal.js         # 地標 Modal JavaScript
└── common.js                 # 共用 JavaScript 函數
```

---

## 📋 **詳細重構計劃**

### 🔄 **階段一：清理和準備** (預計 2-3 天)

#### 📋 **任務清單**
- [ ] 備份現有 orders 資料夾
- [ ] 分析現有功能清單
- [ ] 移除冗餘文件：
  - [ ] `partials/list.blade.php` (未使用)
  - [ ] `partials/show.blade.php` (未使用)
- [ ] 創建新的目錄結構

#### 📊 **功能分析**
| 功能 | 現在位置 | 新位置 | 狀態 |
|------|----------|--------|------|
| 訂單列表 | index.blade.php | index.blade.php | 保留 |
| 客戶搜尋 | index.blade.php | components/customer-search.blade.php | 組件化 |
| 訂單檢視 | index.blade.php (Modal) | show.blade.php | 頁面化 |
| 訂單編輯 | index.blade.php (Modal) | edit.blade.php | 頁面化 |
| 訂單新增 | create.blade.php | create.blade.php | 優化 |
| 表單組件 | partials/form.blade.php | components/order-form.blade.php | 重構 |
| 地標選擇 | partials/form.blade.php | components/landmark-modal.blade.php | 組件化 |

---

### 🔄 **階段二：重新設計各頁面** (預計 3-4 天)

#### 📄 **index.blade.php** (訂單列表頁面)
```php
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 客戶搜尋區塊 --}}
    @include('orders.components.customer-search')
    
    {{-- 訂單列表 --}}
    @include('orders.components.order-table', ['orders' => $orders])
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/orders/index.js') }}"></script>
@endpush
```

**功能範圍**：
- ✅ 客戶搜尋功能
- ✅ 訂單列表顯示
- ✅ 基本篩選功能
- ✅ 分頁功能
- ❌ 不包含表單相關功能
- ❌ 不包含 Modal 編輯功能

#### 📄 **create.blade.php** (新增訂單頁面)
```php
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">新增訂單</h3>
                <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left"></i> 返回列表
                </a>
            </div>
        </div>
        <div class="card-body">
            @include('orders.components.order-form')
        </div>
    </div>
</div>

{{-- 地標選擇 Modal --}}
@include('orders.components.landmark-modal')
@endsection

@push('scripts')
    <script src="{{ asset('js/orders/form.js') }}"></script>
@endpush
```

**功能範圍**：
- ✅ 完整的訂單建立表單
- ✅ 地標選擇功能
- ✅ 共乘查詢功能
- ✅ 駕駛查詢功能
- ✅ 表單驗證

#### 📄 **edit.blade.php** (編輯訂單頁面)
```php
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">編輯訂單 - {{ $order->order_number }}</h3>
                <div>
                    <a href="{{ route('orders.show', $order) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> 檢視詳細
                    </a>
                    <a href="{{ route('orders.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @include('orders.components.order-form', ['order' => $order])
        </div>
    </div>
</div>

{{-- 地標選擇 Modal --}}
@include('orders.components.landmark-modal')
@endsection

@push('scripts')
    <script src="{{ asset('js/orders/form.js') }}"></script>
@endpush
```

**功能範圍**：
- ✅ 完整的訂單編輯表單
- ✅ 預填現有資料
- ✅ 地標選擇功能
- ✅ 共乘查詢功能
- ✅ 駕駛查詢功能

#### 📄 **show.blade.php** (訂單詳細頁面) - 新增
```php
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">訂單詳細資料 - {{ $order->order_number }}</h3>
                <div>
                    <a href="{{ route('orders.edit', $order) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> 編輯訂單
                    </a>
                    <a href="{{ route('orders.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> 返回列表
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @include('orders.components.order-detail', ['order' => $order])
        </div>
    </div>
</div>
@endsection
```

**功能範圍**：
- ✅ 完整的訂單資料顯示
- ✅ 客戶資訊
- ✅ 駕駛資訊
- ✅ 訂單狀態歷史
- ✅ 操作按鈕

---

### 🔄 **階段三：建立組件** (預計 4-5 天)

#### 📄 **components/customer-search.blade.php**
```php
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">客戶搜尋</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
            <div class="col-md-6">
                <label for="keyword" class="form-label">搜尋關鍵字</label>
                <input type="text" name="keyword" id="keyword" class="form-control"
                       placeholder="輸入姓名、電話或身分證字號"
                       value="{{ request('keyword') }}">
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">開始日期</label>
                <input type="date" name="start_date" id="start_date" class="form-control"
                       value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">結束日期</label>
                <input type="date" name="end_date" id="end_date" class="form-control"
                       value="{{ request('end_date') }}">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> 搜尋
                </button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> 清除
                </a>
            </div>
        </form>
    </div>
</div>
```

#### 📄 **components/order-form.blade.php**
```php
<form method="POST" action="{{ isset($order) ? route('orders.update', $order) : route('orders.store') }}" 
      class="order-form">
    @csrf
    @if(isset($order))
        @method('PUT')
    @endif
    
    {{-- 隱藏欄位 --}}
    <input type="hidden" name="customer_id" value="{{ old('customer_id', $order->customer_id ?? $customer->id ?? '') }}">
    
    {{-- 客戶資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-user me-2"></i>客戶資訊
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">客戶姓名</label>
                    <input type="text" name="customer_name" class="form-control" 
                           value="{{ old('customer_name', $order->customer_name ?? $customer->name ?? '') }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">身分證字號</label>
                    <input type="text" name="customer_id_number" class="form-control"
                           value="{{ old('customer_id_number', $order->customer_id_number ?? $customer->id_number ?? '') }}" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">電話</label>
                    <input type="text" name="customer_phone" class="form-control"
                           value="{{ old('customer_phone', $order->customer_phone ?? '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">身份別</label>
                    <input type="text" name="identity" class="form-control"
                           value="{{ old('identity', $order->identity ?? $customer->identity ?? '') }}" readonly>
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
    
    {{-- 用車資訊區塊 --}}
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-car me-2"></i>用車資訊
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">用車日期</label>
                    <input type="date" name="ride_date" class="form-control"
                           value="{{ old('ride_date', $order->ride_date ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">用車時間</label>
                    <input type="time" name="ride_time" class="form-control"
                           value="{{ old('ride_time', isset($order) ? substr($order->ride_time, 0, 5) : '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">回程時間</label>
                    <input type="time" name="back_time" class="form-control"
                           value="{{ old('back_time', isset($order) ? substr($order->back_time, 0, 5) : '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">陪同人數</label>
                    <input type="number" name="companions" class="form-control" min="0"
                           value="{{ old('companions', $order->companions ?? 0) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">輪椅</label>
                    <select name="wheelchair" class="form-select">
                        <option value="0" {{ old('wheelchair', $order->wheelchair ?? 0) == 0 ? 'selected' : '' }}>否</option>
                        <option value="1" {{ old('wheelchair', $order->wheelchair ?? 0) == 1 ? 'selected' : '' }}>是</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">爬梯機</label>
                    <select name="stair_machine" class="form-select">
                        <option value="0" {{ old('stair_machine', $order->stair_machine ?? 0) == 0 ? 'selected' : '' }}>否</option>
                        <option value="1" {{ old('stair_machine', $order->stair_machine ?? 0) == 1 ? 'selected' : '' }}>是</option>
                    </select>
                </div>
            </div>
            
            {{-- 地址資訊 --}}
            <div class="row g-3 mt-3">
                <div class="col-12">
                    <label class="form-label">上車地址</label>
                    <div class="input-group">
                        <input type="text" name="pickup_address" id="pickup_address" class="form-control landmark-input"
                               value="{{ old('pickup_address', $order->pickup_address ?? '') }}"
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
                        <input type="text" name="dropoff_address" id="dropoff_address" class="form-control landmark-input"
                               value="{{ old('dropoff_address', $order->dropoff_address ?? '') }}"
                               placeholder="輸入地址或使用*觸發地標搜尋">
                        <button type="button" class="btn btn-outline-secondary" onclick="openLandmarkModal('dropoff')">
                            <i class="fas fa-map-marker-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
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
                               value="{{ old('driver_fleet_number', $order->driver_fleet_number ?? '') }}">
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
                        <option value="一般" {{ old('special_status', $order->special_status ?? '一般') == '一般' ? 'selected' : '' }}>一般</option>
                        <option value="VIP" {{ old('special_status', $order->special_status ?? '一般') == 'VIP' ? 'selected' : '' }}>VIP</option>
                        <option value="個管單" {{ old('special_status', $order->special_status ?? '一般') == '個管單' ? 'selected' : '' }}>個管單</option>
                        <option value="黑名單" {{ old('special_status', $order->special_status ?? '一般') == '黑名單' ? 'selected' : '' }}>黑名單</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">訂單狀態</label>
                    <select name="status" class="form-select">
                        <option value="open" {{ old('status', $order->status ?? 'open') == 'open' ? 'selected' : '' }}>可派遣</option>
                        <option value="assigned" {{ old('status', $order->status ?? 'open') == 'assigned' ? 'selected' : '' }}>已指派</option>
                        <option value="replacement" {{ old('status', $order->status ?? 'open') == 'replacement' ? 'selected' : '' }}>候補</option>
                        <option value="cancelled" {{ old('status', $order->status ?? 'open') == 'cancelled' ? 'selected' : '' }}>已取消</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">訂單備註</label>
                    <textarea name="remark" class="form-control" rows="3">{{ old('remark', $order->remark ?? '') }}</textarea>
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
```

#### 📄 **components/landmark-modal.blade.php**
```php
<div class="modal fade" id="landmarkModal" tabindex="-1" aria-labelledby="landmarkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" id="landmarkModalHeader">
                <div class="d-flex align-items-center">
                    <i class="fas fa-map-marker-alt me-3 fs-4"></i>
                    <div>
                        <h5 class="modal-title mb-0" id="landmarkModalLabel">選擇地標</h5>
                        <small class="text-light opacity-75" id="landmarkModalSubtitle">快速填入常用地址</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="關閉"></button>
            </div>
            <div class="modal-body p-0">
                {{-- 搜尋區域 --}}
                <div class="landmark-search-area p-3 bg-light border-bottom">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="landmarkSearchInput" class="form-control border-start-0"
                               placeholder="搜尋地標名稱或地址...">
                        <button class="btn btn-primary" type="button" id="searchLandmarkBtn">
                            <i class="fas fa-search me-1"></i>搜尋
                        </button>
                    </div>
                </div>
                
                {{-- 分類篩選 --}}
                <div class="landmark-categories p-3 border-bottom bg-light">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm category-filter active"
                                data-category="all">全部</button>
                        <button type="button" class="btn btn-outline-danger btn-sm category-filter"
                                data-category="medical">醫療</button>
                        <button type="button" class="btn btn-outline-primary btn-sm category-filter"
                                data-category="transport">交通</button>
                        <button type="button" class="btn btn-outline-success btn-sm category-filter"
                                data-category="education">教育</button>
                        <button type="button" class="btn btn-outline-warning btn-sm category-filter"
                                data-category="government">政府</button>
                        <button type="button" class="btn btn-outline-info btn-sm category-filter"
                                data-category="commercial">商業</button>
                    </div>
                </div>
                
                {{-- 分頁標籤 --}}
                <div class="landmark-tabs">
                    <ul class="nav nav-pills nav-justified bg-light" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="search-tab" data-bs-toggle="pill"
                                    data-bs-target="#search-content" type="button" role="tab">
                                <i class="fas fa-search me-1"></i>搜尋結果
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="popular-tab" data-bs-toggle="pill"
                                    data-bs-target="#popular-content" type="button" role="tab">
                                <i class="fas fa-fire me-1"></i>熱門地標
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="recent-tab" data-bs-toggle="pill"
                                    data-bs-target="#recent-content" type="button" role="tab">
                                <i class="fas fa-history me-1"></i>最近使用
                            </button>
                        </li>
                    </ul>
                </div>
                
                {{-- 內容區域 --}}
                <div class="landmark-content" style="max-height: 400px; overflow-y: auto;">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="search-content" role="tabpanel">
                            <div id="landmarkSearchResults" class="p-3">
                                <div class="text-center py-4">
                                    <i class="fas fa-search text-muted mb-2" style="font-size: 2rem;"></i>
                                    <p class="text-muted mb-0">請輸入關鍵字搜尋地標</p>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="popular-content" role="tabpanel">
                            <div id="landmarkPopularResults" class="p-3"></div>
                        </div>
                        <div class="tab-pane fade" id="recent-content" role="tabpanel">
                            <div id="landmarkRecentResults" class="p-3"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <small class="text-muted me-auto">
                    <i class="fas fa-lightbulb me-1"></i>提示：點擊地標快速填入地址
                </small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
            </div>
        </div>
    </div>
</div>
```

#### 📄 **components/order-table.blade.php**
```php
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">訂單列表</h5>
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> 新增訂單
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="ordersTable">
                <thead class="table-dark">
                    <tr>
                        <th>訂單編號</th>
                        <th>客戶姓名</th>
                        <th>用車日期</th>
                        <th>用車時間</th>
                        <th>上車地址</th>
                        <th>下車地址</th>
                        <th>駕駛</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->ride_date ? $order->ride_date->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ $order->ride_time ? $order->ride_time->format('H:i') : 'N/A' }}</td>
                        <td>{{ Str::limit($order->pickup_address, 30) }}</td>
                        <td>{{ Str::limit($order->dropoff_address, 30) }}</td>
                        <td>{{ $order->driver_name ?: '未指派' }}</td>
                        <td>
                            @switch($order->status)
                                @case('open')
                                    <span class="badge bg-success">可派遣</span>
                                    @break
                                @case('assigned')
                                    <span class="badge bg-primary">已指派</span>
                                    @break
                                @case('replacement')
                                    <span class="badge bg-warning">候補</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">已取消</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">未知</span>
                            @endswitch
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('orders.edit', $order) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteOrder({{ $order->id }})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="py-4">
                                <i class="fas fa-inbox text-muted mb-2" style="font-size: 3rem;"></i>
                                <p class="text-muted mb-0">目前沒有訂單資料</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        {{-- 分頁 --}}
        @if(method_exists($orders, 'links'))
            <div class="d-flex justify-content-center mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
```

#### 📄 **components/order-detail.blade.php**
```php
<div class="row">
    <div class="col-md-6">
        {{-- 訂單基本資訊 --}}
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">訂單基本資訊</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>訂單編號：</strong></td>
                        <td>{{ $order->order_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>用車日期：</strong></td>
                        <td>{{ $order->ride_date ? $order->ride_date->format('Y-m-d') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>用車時間：</strong></td>
                        <td>{{ $order->ride_time ? $order->ride_time->format('H:i') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>回程時間：</strong></td>
                        <td>{{ $order->back_time ? $order->back_time->format('H:i') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>是否輪椅：</strong></td>
                        <td>{{ $order->wheelchair ? '是' : '否' }}</td>
                    </tr>
                    <tr>
                        <td><strong>爬梯機：</strong></td>
                        <td>{{ $order->stair_machine ? '是' : '否' }}</td>
                    </tr>
                    <tr>
                        <td><strong>陪同人數：</strong></td>
                        <td>{{ $order->companions }}</td>
                    </tr>
                    <tr>
                        <td><strong>特殊狀態：</strong></td>
                        <td>
                            @switch($order->special_status)
                                @case('VIP')
                                    <span class="badge bg-warning">VIP</span>
                                    @break
                                @case('個管單')
                                    <span class="badge bg-info">個管單</span>
                                    @break
                                @case('黑名單')
                                    <span class="badge bg-danger">黑名單</span>
                                    @break
                                @default
                                    <span class="badge bg-success">一般</span>
                            @endswitch
                        </td>
                    </tr>
                    <tr>
                        <td><strong>訂單狀態：</strong></td>
                        <td>
                            @switch($order->status)
                                @case('open')
                                    <span class="badge bg-success">可派遣</span>
                                    @break
                                @case('assigned')
                                    <span class="badge bg-primary">已指派</span>
                                    @break
                                @case('replacement')
                                    <span class="badge bg-warning">候補</span>
                                    @break
                                @case('cancelled')
                                    <span class="badge bg-danger">已取消</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">未知</span>
                            @endswitch
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        {{-- 客戶資訊 --}}
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">客戶資訊</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>客戶姓名：</strong></td>
                        <td>{{ $order->customer_name }}</td>
                    </tr>
                    <tr>
                        <td><strong>身分證字號：</strong></td>
                        <td>{{ $order->customer_id_number }}</td>
                    </tr>
                    <tr>
                        <td><strong>電話：</strong></td>
                        <td>{{ $order->customer_phone }}</td>
                    </tr>
                    <tr>
                        <td><strong>身份別：</strong></td>
                        <td>{{ $order->identity }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        {{-- 地址資訊 --}}
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">地址資訊</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>上車地址：</strong></td>
                        <td>{{ $order->pickup_address }}</td>
                    </tr>
                    <tr>
                        <td><strong>下車地址：</strong></td>
                        <td>{{ $order->dropoff_address }}</td>
                    </tr>
                </table>
            </div>
        </div>
        
        {{-- 駕駛資訊 --}}
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">駕駛資訊</h5>
            </div>
            <div class="card-body">
                @if($order->driver_name)
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>駕駛姓名：</strong></td>
                            <td>{{ $order->driver_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>隊員編號：</strong></td>
                            <td>{{ $order->driver_fleet_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>車牌號碼：</strong></td>
                            <td>{{ $order->driver_plate_number }}</td>
                        </tr>
                    </table>
                @else
                    <p class="text-muted">尚未指派駕駛</p>
                @endif
            </div>
        </div>
        
        {{-- 共乘資訊 --}}
        @if($order->carpool_with)
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">共乘資訊</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>共乘對象：</strong></td>
                        <td>{{ $order->carpool_with }}</td>
                    </tr>
                    <tr>
                        <td><strong>共乘ID：</strong></td>
                        <td>{{ $order->carpool_id }}</td>
                    </tr>
                    <tr>
                        <td><strong>共乘電話：</strong></td>
                        <td>{{ $order->carpool_phone }}</td>
                    </tr>
                    <tr>
                        <td><strong>共乘地址：</strong></td>
                        <td>{{ $order->carpool_addresses }}</td>
                    </tr>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- 備註資訊 --}}
@if($order->remark)
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">備註資訊</h5>
    </div>
    <div class="card-body">
        <p class="mb-0">{{ $order->remark }}</p>
    </div>
</div>
@endif

{{-- 時間戳記 --}}
<div class="card">
    <div class="card-header bg-light">
        <h5 class="mb-0">系統資訊</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <strong>建立時間：</strong><br>
                {{ $order->created_at->format('Y-m-d H:i:s') }}
            </div>
            <div class="col-md-4">
                <strong>更新時間：</strong><br>
                {{ $order->updated_at->format('Y-m-d H:i:s') }}
            </div>
            <div class="col-md-4">
                <strong>建立人員：</strong><br>
                {{ $order->created_by ?: 'N/A' }}
            </div>
        </div>
    </div>
</div>
```

---

### 🔄 **階段四：JavaScript 重構** (預計 3-4 天)

#### 📄 **public/js/orders/index.js**
```javascript
/**
 * 訂單列表頁面 JavaScript
 * 職責：列表顯示、搜尋、分頁、基本操作
 */
class OrderIndex {
    constructor() {
        this.dataTable = null;
        this.init();
    }
    
    init() {
        this.initializeDataTable();
        this.bindEvents();
    }
    
    /**
     * 初始化 DataTable
     */
    initializeDataTable() {
        this.dataTable = $('#ordersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/zh-HANT.json'
            },
            pageLength: 50,
            order: [[2, 'desc']], // 按用車日期降序
            columnDefs: [
                { targets: [8], orderable: false } // 操作欄不可排序
            ],
            responsive: true
        });
    }
    
    /**
     * 綁定事件
     */
    bindEvents() {
        // 全選功能
        $('#selectAll').on('change', this.handleSelectAll.bind(this));
        
        // 批量操作
        $('#bulkActions').on('click', this.handleBulkActions.bind(this));
        
        // 搜尋表單
        $('#searchForm').on('submit', this.handleSearch.bind(this));
        
        // 清除搜尋
        $('#clearSearch').on('click', this.handleClearSearch.bind(this));
    }
    
    /**
     * 處理全選
     */
    handleSelectAll(e) {
        const isChecked = e.target.checked;
        $('input[name="order_ids[]"]').prop('checked', isChecked);
        this.updateBulkActionButtons();
    }
    
    /**
     * 處理批量操作
     */
    handleBulkActions(e) {
        const selectedIds = $('input[name="order_ids[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            alert('請選擇要操作的訂單');
            return;
        }
        
        const action = e.target.dataset.action;
        
        if (confirm(`確定要${action}選中的 ${selectedIds.length} 筆訂單嗎？`)) {
            this.performBulkAction(action, selectedIds);
        }
    }
    
    /**
     * 執行批量操作
     */
    performBulkAction(action, ids) {
        $.ajax({
            url: `/orders/bulk-${action}`,
            method: 'POST',
            data: {
                ids: ids,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || '操作失敗');
                }
            },
            error: () => {
                alert('操作失敗，請稍後再試');
            }
        });
    }
    
    /**
     * 處理搜尋
     */
    handleSearch(e) {
        // 表單驗證
        const keyword = $('#keyword').val().trim();
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        
        if (!keyword && !startDate && !endDate) {
            alert('請輸入搜尋條件');
            e.preventDefault();
            return;
        }
        
        if (startDate && endDate && startDate > endDate) {
            alert('開始日期不能晚於結束日期');
            e.preventDefault();
            return;
        }
    }
    
    /**
     * 處理清除搜尋
     */
    handleClearSearch(e) {
        e.preventDefault();
        $('#keyword').val('');
        $('#start_date').val('');
        $('#end_date').val('');
        $('#searchForm').submit();
    }
    
    /**
     * 更新批量操作按鈕狀態
     */
    updateBulkActionButtons() {
        const selectedCount = $('input[name="order_ids[]"]:checked').length;
        $('#bulkActions').toggle(selectedCount > 0);
        $('#selectedCount').text(selectedCount);
    }
}

/**
 * 刪除訂單
 */
function deleteOrder(orderId) {
    if (confirm('確定要刪除這筆訂單嗎？此操作無法恢復！')) {
        $.ajax({
            url: `/orders/${orderId}`,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || '刪除失敗');
                }
            },
            error: () => {
                alert('刪除失敗，請稍後再試');
            }
        });
    }
}

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    new OrderIndex();
});
```

#### 📄 **public/js/orders/form.js**
```javascript
/**
 * 訂單表單頁面 JavaScript
 * 職責：表單驗證、共乘查詢、駕駛查詢、地標選擇
 */
class OrderForm {
    constructor() {
        this.currentAddressType = ''; // 'pickup' 或 'dropoff'
        this.landmarkModal = null;
        this.init();
    }
    
    init() {
        this.initializeLandmarkModal();
        this.bindFormEvents();
        this.bindCarpoolEvents();
        this.bindDriverEvents();
        this.bindAddressEvents();
    }
    
    /**
     * 初始化地標 Modal
     */
    initializeLandmarkModal() {
        const modalElement = document.getElementById('landmarkModal');
        if (modalElement) {
            this.landmarkModal = new bootstrap.Modal(modalElement);
            this.bindLandmarkEvents();
        }
    }
    
    /**
     * 綁定表單事件
     */
    bindFormEvents() {
        // 表單提交驗證
        $('.order-form').on('submit', this.handleFormSubmit.bind(this));
        
        // 即時驗證
        $('input[required]').on('blur', this.validateField.bind(this));
        
        // 數字輸入限制
        $('input[type="number"]').on('input', this.handleNumberInput.bind(this));
        
        // 時間格式驗證
        $('input[type="time"]').on('blur', this.validateTimeInput.bind(this));
    }
    
    /**
     * 綁定共乘相關事件
     */
    bindCarpoolEvents() {
        // 共乘搜尋
        $('#searchCarpoolBtn').on('click', this.handleCarpoolSearch.bind(this));
        
        // 清除共乘
        $('#clearCarpoolBtn').on('click', this.handleCarpoolClear.bind(this));
        
        // 共乘搜尋輸入框 Enter 鍵
        $('#carpoolSearchInput').on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleCarpoolSearch();
            }
        });
    }
    
    /**
     * 綁定駕駛相關事件
     */
    bindDriverEvents() {
        // 駕駛搜尋
        $('#searchDriverBtn').on('click', this.handleDriverSearch.bind(this));
        
        // 清除駕駛
        $('#clearDriverBtn').on('click', this.handleDriverClear.bind(this));
        
        // 駕駛隊編輸入監聽
        $('#driver_fleet_number').on('input', this.handleDriverFleetInput.bind(this));
        
        // 駕駛搜尋輸入框 Enter 鍵
        $('#driver_fleet_number').on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleDriverSearch();
            }
        });
    }
    
    /**
     * 綁定地址相關事件
     */
    bindAddressEvents() {
        // 地址交換
        $('#swapAddressBtn').on('click', this.handleAddressSwap.bind(this));
        
        // 地標輸入框星號觸發
        $('.landmark-input').on('input', this.handleLandmarkInput.bind(this));
    }
    
    /**
     * 綁定地標 Modal 事件
     */
    bindLandmarkEvents() {
        // 搜尋按鈕
        $('#searchLandmarkBtn').on('click', this.handleLandmarkSearch.bind(this));
        
        // 搜尋輸入框 Enter 鍵
        $('#landmarkSearchInput').on('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleLandmarkSearch();
            }
        });
        
        // 分類篩選
        $('.category-filter').on('click', this.handleCategoryFilter.bind(this));
        
        // 分頁切換
        $('#popular-tab').on('click', this.loadPopularLandmarks.bind(this));
        $('#recent-tab').on('click', this.loadRecentLandmarks.bind(this));
        
        // Modal 顯示事件
        $('#landmarkModal').on('show.bs.modal', this.handleModalShow.bind(this));
        
        // Modal 隱藏事件
        $('#landmarkModal').on('hidden.bs.modal', this.handleModalHide.bind(this));
    }
    
    /**
     * 處理表單提交
     */
    handleFormSubmit(e) {
        if (!this.validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // 顯示提交狀態
        const submitBtn = $(e.target).find('button[type="submit"]');
        submitBtn.prop('disabled', true)
                 .html('<i class="fas fa-spinner fa-spin me-2"></i>處理中...');
        
        return true;
    }
    
    /**
     * 驗證表單
     */
    validateForm() {
        let isValid = true;
        const errors = [];
        
        // 必填欄位驗證
        $('input[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                errors.push(`${$(this).prev('label').text()} 為必填欄位`);
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        // 日期驗證
        const rideDate = $('#ride_date').val();
        if (rideDate && new Date(rideDate) < new Date().setHours(0,0,0,0)) {
            isValid = false;
            errors.push('用車日期不能早於今天');
            $('#ride_date').addClass('is-invalid');
        }
        
        // 時間驗證
        const rideTime = $('#ride_time').val();
        const backTime = $('#back_time').val();
        if (rideTime && backTime && rideTime >= backTime) {
            isValid = false;
            errors.push('回程時間必須晚於用車時間');
            $('#back_time').addClass('is-invalid');
        }
        
        // 地址驗證
        const pickupAddress = $('#pickup_address').val();
        const dropoffAddress = $('#dropoff_address').val();
        if (pickupAddress && dropoffAddress && pickupAddress === dropoffAddress) {
            isValid = false;
            errors.push('上車地址和下車地址不能相同');
            $('#dropoff_address').addClass('is-invalid');
        }
        
        // 顯示錯誤訊息
        if (!isValid) {
            const errorHtml = errors.map(error => `<li>${error}</li>`).join('');
            $('#formErrors').html(`
                <div class="alert alert-danger">
                    <h6>請修正以下錯誤：</h6>
                    <ul class="mb-0">${errorHtml}</ul>
                </div>
            `);
            $('html, body').animate({ scrollTop: 0 }, 500);
        } else {
            $('#formErrors').empty();
        }
        
        return isValid;
    }
    
    /**
     * 處理共乘搜尋
     */
    handleCarpoolSearch() {
        const keyword = $('#carpoolSearchInput').val().trim();
        if (!keyword) {
            alert('請輸入搜尋關鍵字');
            return;
        }
        
        $('#carpoolResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');
        
        $.ajax({
            url: '/carpool-search',
            method: 'GET',
            data: { keyword: keyword },
            success: (data) => {
                this.displayCarpoolResults(data);
            },
            error: () => {
                $('#carpoolResults').html('<div class="alert alert-danger">搜尋失敗，請稍後再試</div>');
            }
        });
    }
    
    /**
     * 顯示共乘搜尋結果
     */
    displayCarpoolResults(data) {
        if (data.length === 0) {
            $('#carpoolResults').html('<div class="alert alert-warning">查無相符的客戶資料</div>');
            return;
        }
        
        if (data.length === 1 && data[0].id_number === $('#carpoolSearchInput').val()) {
            // 精確匹配，直接填入
            this.selectCarpoolCustomer(data[0]);
            return;
        }
        
        // 顯示選擇列表
        let html = '<div class="list-group">';
        data.forEach(customer => {
            html += `
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${customer.name}</strong> / ${customer.id_number}<br>
                            <small class="text-muted">${customer.phone_number} / ${customer.addresses}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" onclick="orderForm.selectCarpoolCustomer(${JSON.stringify(customer).replace(/"/g, '&quot;')})">
                            選擇
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        $('#carpoolResults').html(html);
    }
    
    /**
     * 選擇共乘客戶
     */
    selectCarpoolCustomer(customer) {
        $('#carpool_with').val(customer.name);
        $('#carpool_id_number').val(customer.id_number);
        $('#carpool_phone_number').val(Array.isArray(customer.phone_number) ? customer.phone_number[0] : customer.phone_number);
        $('#carpool_addresses').val(Array.isArray(customer.addresses) ? customer.addresses[0] : customer.addresses);
        $('#carpool_customer_id').val(customer.id);
        $('#carpoolResults').empty();
        
        // 顯示成功訊息
        this.showSuccessMessage('已選擇共乘客戶：' + customer.name);
    }
    
    /**
     * 清除共乘資料
     */
    handleCarpoolClear() {
        $('#carpoolSearchInput').val('');
        $('#carpool_with').val('');
        $('#carpool_id_number').val('');
        $('#carpool_phone_number').val('');
        $('#carpool_addresses').val('');
        $('#carpool_customer_id').val('');
        $('#carpoolResults').empty();
    }
    
    /**
     * 處理駕駛搜尋
     */
    handleDriverSearch() {
        const fleetNumber = $('#driver_fleet_number').val().trim();
        if (!fleetNumber) {
            alert('請輸入駕駛隊編');
            return;
        }
        
        $.ajax({
            url: '/drivers/fleet-search',
            method: 'GET',
            data: { fleet_number: fleetNumber },
            success: (data) => {
                if (data.error) {
                    alert(data.error);
                } else {
                    $('#driver_id').val(data.id);
                    $('#driver_name').val(data.name);
                    $('#driver_plate_number').val(data.plate_number);
                    
                    // 自動設定為已指派
                    $('select[name="status"]').val('assigned').prop('disabled', true);
                    
                    this.showSuccessMessage('已找到駕駛：' + data.name);
                }
            },
            error: () => {
                alert('查詢失敗，請稍後再試');
            }
        });
    }
    
    /**
     * 清除駕駛資料
     */
    handleDriverClear() {
        $('#driver_fleet_number').val('');
        $('#driver_id').val('');
        $('#driver_name').val('');
        $('#driver_plate_number').val('');
        
        // 恢復狀態選擇
        $('select[name="status"]').val('open').prop('disabled', false);
    }
    
    /**
     * 處理駕駛隊編輸入
     */
    handleDriverFleetInput(e) {
        const fleetNumber = e.target.value.trim();
        const statusSelect = $('select[name="status"]');
        
        if (fleetNumber) {
            statusSelect.val('assigned').prop('disabled', true);
        } else {
            statusSelect.val('open').prop('disabled', false);
        }
    }
    
    /**
     * 處理地址交換
     */
    handleAddressSwap() {
        const pickupAddress = $('#pickup_address').val();
        const dropoffAddress = $('#dropoff_address').val();
        
        $('#pickup_address').val(dropoffAddress);
        $('#dropoff_address').val(pickupAddress);
        
        // 交換地標 ID
        const pickupLandmarkId = $('#pickup_address').attr('data-landmark-id');
        const dropoffLandmarkId = $('#dropoff_address').attr('data-landmark-id');
        
        $('#pickup_address').attr('data-landmark-id', dropoffLandmarkId || '');
        $('#dropoff_address').attr('data-landmark-id', pickupLandmarkId || '');
        
        this.showSuccessMessage('已交換上下車地址');
    }
    
    /**
     * 處理地標輸入
     */
    handleLandmarkInput(e) {
        const inputValue = e.target.value;
        if (inputValue.includes('*')) {
            const keyword = inputValue.replace('*', '').trim();
            e.target.value = keyword;
            
            // 判斷地址類型
            this.currentAddressType = e.target.name === 'pickup_address' ? 'pickup' : 'dropoff';
            
            // 開啟地標 Modal
            this.openLandmarkModal();
            
            // 自動搜尋
            setTimeout(() => {
                $('#landmarkSearchInput').val(keyword);
                this.handleLandmarkSearch();
            }, 300);
        }
    }
    
    /**
     * 開啟地標 Modal
     */
    openLandmarkModal(addressType = null) {
        if (addressType) {
            this.currentAddressType = addressType;
        }
        
        if (this.landmarkModal) {
            this.landmarkModal.show();
        }
    }
    
    /**
     * 處理 Modal 顯示
     */
    handleModalShow() {
        // 設定標題
        const title = this.currentAddressType === 'pickup' ? '選擇上車地標' : '選擇下車地標';
        const color = this.currentAddressType === 'pickup' ? 'bg-success' : 'bg-danger';
        
        $('.modal-header').removeClass('bg-success bg-danger').addClass(color);
        $('.modal-title').text(title);
        
        // 清空搜尋
        $('#landmarkSearchInput').val('');
        $('#landmarkSearchResults').html('<div class="text-center py-4"><p class="text-muted">請輸入關鍵字搜尋地標</p></div>');
        
        // 重設到搜尋頁面
        $('#search-tab').tab('show');
    }
    
    /**
     * 處理 Modal 隱藏
     */
    handleModalHide() {
        this.currentAddressType = '';
    }
    
    /**
     * 處理地標搜尋
     */
    handleLandmarkSearch() {
        const keyword = $('#landmarkSearchInput').val().trim();
        if (!keyword) {
            alert('請輸入搜尋關鍵字');
            return;
        }
        
        $('#landmarkSearchResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');
        
        $.ajax({
            url: '/landmarks-search',
            method: 'GET',
            data: { keyword: keyword },
            success: (response) => {
                if (response.success && response.data.length > 0) {
                    this.displayLandmarkResults(response.data, '#landmarkSearchResults');
                } else {
                    $('#landmarkSearchResults').html('<div class="text-center py-4"><p class="text-muted">查無符合條件的地標</p></div>');
                }
            },
            error: () => {
                $('#landmarkSearchResults').html('<div class="alert alert-danger">搜尋失敗，請稍後再試</div>');
            }
        });
    }
    
    /**
     * 顯示地標搜尋結果
     */
    displayLandmarkResults(landmarks, container) {
        let html = '';
        
        landmarks.forEach(landmark => {
            const fullAddress = `${landmark.city}${landmark.district}${landmark.address}`;
            const categoryBadge = this.getCategoryBadge(landmark.category);
            const categoryIcon = this.getCategoryIcon(landmark.category);
            
            html += `
                <div class="landmark-item border rounded-3 mb-2 p-3" style="cursor: pointer;"
                     onclick="orderForm.selectLandmark('${fullAddress}', ${landmark.id})">
                    <div class="d-flex align-items-start">
                        <div class="me-3">
                            <i class="${categoryIcon} text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-1">${landmark.name}</h6>
                                <div>${categoryBadge}</div>
                            </div>
                            <p class="text-muted mb-0 small">${fullAddress}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $(container).html(html);
    }
    
    /**
     * 選擇地標
     */
    selectLandmark(address, landmarkId) {
        const targetInput = $(`#${this.currentAddressType}_address`);
        targetInput.val(address);
        targetInput.attr('data-landmark-id', landmarkId);
        
        // 關閉 Modal
        this.landmarkModal.hide();
        
        // 更新使用次數
        this.updateLandmarkUsage(landmarkId);
        
        // 保存到最近使用
        this.saveToRecentLandmarks(landmarkId, address);
        
        this.showSuccessMessage('已選擇地標：' + address);
    }
    
    /**
     * 更新地標使用次數
     */
    updateLandmarkUsage(landmarkId) {
        $.ajax({
            url: '/landmarks-usage',
            method: 'POST',
            data: {
                landmark_id: landmarkId,
                _token: $('meta[name="csrf-token"]').attr('content')
            }
        });
    }
    
    /**
     * 保存到最近使用
     */
    saveToRecentLandmarks(landmarkId, address) {
        let recent = JSON.parse(localStorage.getItem('recentLandmarks') || '[]');
        
        // 移除重複
        recent = recent.filter(item => item.id !== landmarkId);
        
        // 添加到開頭
        recent.unshift({
            id: landmarkId,
            address: address,
            timestamp: Date.now()
        });
        
        // 只保留最近 20 個
        recent = recent.slice(0, 20);
        
        localStorage.setItem('recentLandmarks', JSON.stringify(recent));
    }
    
    /**
     * 載入熱門地標
     */
    loadPopularLandmarks() {
        $('#landmarkPopularResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');
        
        $.ajax({
            url: '/landmarks-popular',
            method: 'GET',
            success: (response) => {
                if (response.success && response.data.length > 0) {
                    this.displayLandmarkResults(response.data, '#landmarkPopularResults');
                } else {
                    $('#landmarkPopularResults').html('<div class="text-center py-4"><p class="text-muted">暫無熱門地標</p></div>');
                }
            },
            error: () => {
                $('#landmarkPopularResults').html('<div class="alert alert-danger">載入失敗</div>');
            }
        });
    }
    
    /**
     * 載入最近使用地標
     */
    loadRecentLandmarks() {
        const recent = JSON.parse(localStorage.getItem('recentLandmarks') || '[]');
        
        if (recent.length === 0) {
            $('#landmarkRecentResults').html('<div class="text-center py-4"><p class="text-muted">暫無最近使用記錄</p></div>');
            return;
        }
        
        $('#landmarkRecentResults').html('<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>');
        
        const landmarkIds = recent.map(item => item.id);
        
        $.ajax({
            url: '/landmarks-by-ids',
            method: 'POST',
            data: {
                ids: landmarkIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: (response) => {
                if (response.success && response.data.length > 0) {
                    this.displayLandmarkResults(response.data, '#landmarkRecentResults');
                } else {
                    $('#landmarkRecentResults').html('<div class="text-center py-4"><p class="text-muted">無法載入最近使用記錄</p></div>');
                }
            },
            error: () => {
                $('#landmarkRecentResults').html('<div class="alert alert-danger">載入失敗</div>');
            }
        });
    }
    
    /**
     * 處理分類篩選
     */
    handleCategoryFilter(e) {
        const category = e.target.dataset.category;
        const button = e.target;
        
        // 更新按鈕狀態
        $('.category-filter').removeClass('active');
        $(button).addClass('active');
        
        // 篩選結果
        const allItems = $('.landmark-item');
        
        if (category === 'all') {
            allItems.show();
        } else {
            allItems.each(function() {
                const itemCategory = $(this).data('category');
                if (itemCategory === category) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
    }
    
    /**
     * 獲取分類標籤
     */
    getCategoryBadge(category) {
        const categories = {
            'medical': { text: '醫療', class: 'bg-danger' },
            'transport': { text: '交通', class: 'bg-primary' },
            'education': { text: '教育', class: 'bg-success' },
            'government': { text: '政府', class: 'bg-warning' },
            'commercial': { text: '商業', class: 'bg-info' }
        };
        
        const cat = categories[category] || { text: '一般', class: 'bg-secondary' };
        return `<span class="badge ${cat.class}">${cat.text}</span>`;
    }
    
    /**
     * 獲取分類圖標
     */
    getCategoryIcon(category) {
        const icons = {
            'medical': 'fas fa-hospital',
            'transport': 'fas fa-bus',
            'education': 'fas fa-school',
            'government': 'fas fa-building',
            'commercial': 'fas fa-store'
        };
        
        return icons[category] || 'fas fa-map-marker-alt';
    }
    
    /**
     * 顯示成功訊息
     */
    showSuccessMessage(message) {
        // 創建提示訊息
        const alert = $(`
            <div class="alert alert-success alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999;">
                <i class="fas fa-check-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('body').append(alert);
        
        // 3秒後自動消失
        setTimeout(() => {
            alert.alert('close');
        }, 3000);
    }
    
    /**
     * 欄位驗證
     */
    validateField(e) {
        const field = $(e.target);
        const value = field.val().trim();
        
        if (field.prop('required') && !value) {
            field.addClass('is-invalid');
            this.showFieldError(field, '此欄位為必填');
        } else {
            field.removeClass('is-invalid');
            this.hideFieldError(field);
        }
    }
    
    /**
     * 顯示欄位錯誤
     */
    showFieldError(field, message) {
        const errorDiv = field.siblings('.invalid-feedback');
        if (errorDiv.length === 0) {
            field.after(`<div class="invalid-feedback">${message}</div>`);
        } else {
            errorDiv.text(message);
        }
    }
    
    /**
     * 隱藏欄位錯誤
     */
    hideFieldError(field) {
        field.siblings('.invalid-feedback').remove();
    }
    
    /**
     * 處理數字輸入
     */
    handleNumberInput(e) {
        const field = $(e.target);
        const value = parseInt(field.val());
        const min = parseInt(field.attr('min') || 0);
        const max = parseInt(field.attr('max') || 999);
        
        if (value < min) {
            field.val(min);
        } else if (value > max) {
            field.val(max);
        }
    }
    
    /**
     * 驗證時間輸入
     */
    validateTimeInput(e) {
        const field = $(e.target);
        const value = field.val();
        
        if (value && !/^([01]\d|2[0-3]):[0-5]\d$/.test(value)) {
            field.addClass('is-invalid');
            this.showFieldError(field, '時間格式錯誤，請使用 HH:MM 格式');
        } else {
            field.removeClass('is-invalid');
            this.hideFieldError(field);
        }
    }
}

// 全域變數，供 HTML onclick 使用
let orderForm;

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    orderForm = new OrderForm();
});

// 全域函數，供 HTML 調用
function openLandmarkModal(addressType) {
    orderForm.openLandmarkModal(addressType);
}
```

---

## 📊 **實施時程規劃**

### 📅 **第一週：架構設計和準備**
- **Day 1-2**: 備份現有代碼，創建新目錄結構
- **Day 3-4**: 分析現有功能，規劃組件設計
- **Day 5-7**: 設計 JavaScript 架構，準備共用函數

### 📅 **第二週：核心頁面重構**
- **Day 1-2**: 重構 `index.blade.php` 和 `order-table.blade.php`
- **Day 3-4**: 重構 `create.blade.php` 和 `edit.blade.php`
- **Day 5-7**: 創建 `show.blade.php` 和基本測試

### 📅 **第三週：組件開發**
- **Day 1-2**: 開發 `order-form.blade.php` 組件
- **Day 3-4**: 開發 `landmark-modal.blade.php` 組件
- **Day 5-7**: 開發其他組件和整合測試

### 📅 **第四週：JavaScript 和優化**
- **Day 1-3**: 開發 `index.js` 和 `form.js`
- **Day 4-5**: 整合測試和錯誤修復
- **Day 6-7**: 效能優化和最終測試

---

## 🧪 **測試計劃**

### 🔍 **功能測試**
- [ ] 訂單列表顯示和搜尋
- [ ] 客戶搜尋和選擇
- [ ] 訂單建立流程
- [ ] 訂單編輯流程
- [ ] 訂單檢視功能
- [ ] 共乘查詢功能
- [ ] 駕駛查詢功能
- [ ] 地標選擇功能
- [ ] 地址交換功能
- [ ] 表單驗證功能

### 🔍 **相容性測試**
- [ ] 與現有 Laravel 後端相容
- [ ] 與現有資料庫結構相容
- [ ] 與現有權限系統相容
- [ ] 與現有路由系統相容

### 🔍 **效能測試**
- [ ] 頁面載入速度
- [ ] JavaScript 執行效率
- [ ] 大量資料處理能力
- [ ] 記憶體使用狀況

### 🔍 **用戶體驗測試**
- [ ] 介面流暢度
- [ ] 操作直觀性
- [ ] 錯誤提示清晰度
- [ ] 響應式設計效果

---

## 📋 **驗收標準**

### ✅ **功能驗收**
1. **所有原有功能正常運作**
2. **無 JavaScript 衝突或錯誤**
3. **地標 Modal 無閃爍問題**
4. **表單驗證正確運作**
5. **資料保存無誤**

### ✅ **代碼品質驗收**
1. **無重複代碼**
2. **清晰的文件結構**
3. **統一的命名規範**
4. **完整的錯誤處理**
5. **良好的代碼注釋**

### ✅ **性能驗收**
1. **頁面載入時間 < 2 秒**
2. **JavaScript 執行無延遲**
3. **記憶體使用合理**
4. **無記憶體洩漏**

### ✅ **用戶體驗驗收**
1. **操作流程直觀**
2. **錯誤提示清楚**
3. **響應式設計良好**
4. **載入狀態明確**

---

## 📚 **文檔和維護**

### 📖 **技術文檔**
- [ ] 架構設計文檔
- [ ] API 接口文檔
- [ ] 組件使用說明
- [ ] JavaScript 函數說明
- [ ] 故障排除指南

### 📖 **用戶文檔**
- [ ] 功能使用說明
- [ ] 常見問題解答
- [ ] 操作流程圖
- [ ] 快速入門指南

### 🔧 **維護計劃**
- [ ] 定期代碼審查
- [ ] 性能監控
- [ ] 錯誤日誌分析
- [ ] 用戶反饋收集
- [ ] 功能更新計劃

---

## 🎯 **成功指標**

### 📈 **技術指標**
- **代碼重複度**: < 5%
- **JavaScript 錯誤率**: 0%
- **頁面載入時間**: < 2 秒
- **測試覆蓋率**: > 80%

### 📈 **業務指標**
- **用戶操作效率**: 提升 30%
- **錯誤發生率**: 降低 80%
- **客戶滿意度**: > 90%
- **系統穩定性**: 99.9%

---

## 💡 **風險評估與應對**

### ⚠️ **技術風險**
1. **資料遺失風險**: 
   - 應對：完整備份，分階段遷移
2. **相容性問題**: 
   - 應對：充分測試，保持 API 一致性
3. **性能下降**: 
   - 應對：性能監控，代碼優化

### ⚠️ **業務風險**
1. **用戶適應困難**: 
   - 應對：提供完整文檔，用戶培訓
2. **功能遺漏**: 
   - 應對：詳細功能清單，用戶驗收
3. **上線時間延遲**: 
   - 應對：合理時程規劃，階段性交付

---

## 📝 **總結**

此重構計劃旨在解決現有 Orders 系統的架構問題，提升代碼品質和用戶體驗。通過組件化設計、模組化 JavaScript 和清晰的職責分離，將創建一個更穩定、可維護且易於擴展的訂單管理系統。

預計重構完成後，系統將具備更好的：
- **穩定性**：消除 JavaScript 衝突和 Modal 閃爍
- **可維護性**：清晰的代碼結構和組件化設計
- **擴展性**：標準化的開發流程和架構
- **用戶體驗**：流暢的操作流程和明確的反饋

此計劃為長期技術債務的償還和系統現代化奠定了基礎，將為未來的功能擴展和維護提供堅實的技術支撐。

---

*文件版本：v1.0*  
*最後更新：2025-07-18*  
*文件作者：chanraymon*  
*審核狀態：待審核*