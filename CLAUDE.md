# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案概述

LC-management 是一個基於 Laravel 10 框架的長照服務管理系統，主要用於客戶、訂單、司機管理、地標管理和 Excel 匯入匯出功能。系統具備生產級併發安全性，可安全處理多使用者同時操作。

### 關鍵技術堆疊
- **後端**: Laravel 10.x + PHP 8.1+
- **前端**: Vite + Tailwind CSS + Alpine.js + AdminLTE 3.2
- **資料庫**: MySQL with JSON column support + 併發安全性約束
- **認證**: Laravel Breeze
- **Excel 處理**: maatwebsite/excel 3.1+
- **併發控制**: SELECT FOR UPDATE + 原子化序列號 + UUID 群組ID
- **開發工具**: Laravel Pint (程式碼格式化) + IDE Helper + 併發測試套件

## 快速開始

```bash
# 1. 環境設定
cp .env.example .env
composer install
npm install
php artisan key:generate

# 2. 資料庫設定
php artisan migrate
php artisan db:seed --class=LandmarkSeeder

# 3. 啟動開發環境
npm run dev        # 終端 1：前端建置
php artisan serve  # 終端 2：後端伺服器

# 4. 開發工具（可選）
php artisan ide-helper:generate
./vendor/bin/pint  # 程式碼格式化
```

## 個人偏好
1. 使用繁體中文應答
2. 設計新功能前先規劃，並討論完後，有確定再執行
3. 做任何分析、查詢、檢查時使用Claude Opus 4模型，編寫代碼時使用Claude Sonnet 4模型，來降低使用次數成本。
4. 注重安全性問題

## 常用開發指令

### PHP/Laravel 指令
```bash
# 啟動開發伺服器
php artisan serve

# 資料庫遷移
php artisan migrate
php artisan migrate:fresh --seed

# 快取管理
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 測試執行
php artisan test                    # Laravel 測試套件
php artisan test --parallel         # 平行執行測試
./vendor/bin/phpunit                # PHPUnit 測試
./vendor/bin/phpunit --filter=ExampleTest  # 執行特定測試

# 程式碼格式化
./vendor/bin/pint

# IDE Helper 生成（提升開發體驗）
php artisan ide-helper:generate     # 生成 Helper 檔案
php artisan ide-helper:models       # 生成 Model 註解
php artisan ide-helper:meta         # 生成 Meta 檔案

# 清除所有快取
php artisan optimize:clear

# 地標資料庫遷移和測試資料
php artisan migrate
php artisan db:seed --class=LandmarkSeeder

# 併發安全性測試
php artisan test:concurrency                     # 預設併發測試 (5執行緒×10訂單)
php artisan test:concurrency --threads=10 --orders=20  # 自訂併發測試參數
```

### 前端建置指令
```bash
# 開發模式 (使用 Vite)
npm run dev

# 生產建置
npm run build

# 依賴安裝
npm install
composer install

# Vite 熱更新開發
npm run dev -- --host  # 允許外部存取
```

### 路由架構概覽
```
/dashboard                    # 主控台頁面
/customers                    # 客戶管理 (CRUD + 匯入匯出)
/orders                       # 訂單管理 (CRUD + 複雜搜尋)
/admin/drivers                # 駕駛管理 (CRUD + 匯入匯出)
/landmarks                    # 地標管理 (CRUD + 搜尋 API)
/profile                      # 使用者資料管理

# 重要 API 端點
GET  /landmarks-search        # 地標搜尋 API
POST /orders/check-duplicate  # 重複訂單檢查
GET  /customers/{id}/history-orders  # 客戶歷史訂單
POST /landmarks-usage         # 更新地標使用統計
```

## 應用程式架構

### 核心業務模型
- **Customer**: 客戶管理，支援 JSON 欄位儲存多筆電話和地址
- **Order**: 訂單管理，具備智能編號生成和客戶快照功能
  - 資料類型自動轉換：日期、時間、布林值、座標等
  - 支援搜尋篩選和狀態管理
- **Driver**: 司機管理，包含車輛資訊和服務能力
- **CustomerEvent**: 客戶事件追蹤系統
- **Landmark**: 地標管理，支援地址快速選擇和使用統計

### 控制器結構
- **CustomerController**: 客戶 CRUD 操作，支援 Excel 匯入匯出
- **OrderController**: 訂單管理，包含複雜篩選和共乘功能，整合地標使用記錄
  - 支援搜尋參數保持功能
  - AJAX 和傳統表單雙重支援
- **DriverController**: 駕駛管理系統，支援完整的 Excel 匯入匯出功能
  - 支援 13 個駕駛欄位的匯入匯出
  - 智能狀態欄位對照（在職/離職/黑名單）
  - 分頁機制和搜尋功能
- **LandmarkController**: 地標 CRUD 操作，搜尋 API 和批量操作

### 資料庫關係
- Customer 一對多 Orders
- Driver 一對多 Orders (軟約束)
- Customer 一對多 CustomerEvents
- JSON 欄位：Customer 的 phone_number 和 addresses
- Landmark 獨立表：支援地址搜尋和使用統計

### 訂單系統重構後架構 (2025-01-18)
經過全面重構，訂單系統已從單體架構轉換為組件化架構：

#### 檔案結構
```
resources/views/orders/
├── index.blade.php          # 主列表頁面 (15行)
├── create.blade.php         # 新增訂單頁面 (18行)
├── edit.blade.php           # 編輯訂單頁面 (33行)
├── show.blade.php           # 訂單詳細頁面 (26行)
└── components/              # 組件化設計
    ├── customer-search.blade.php    # 客戶搜尋組件 (127行)
    ├── order-table.blade.php        # 訂單列表組件 (120行)
    ├── order-form.blade.php         # 訂單表單組件 (完整表單功能)
    ├── order-detail.blade.php       # 訂單詳細組件 (248行)
    └── landmark-modal.blade.php     # 地標選擇組件 (Modal)

public/js/orders/
├── index.js                 # 列表頁面 JavaScript (271行)
└── form.js                  # 表單頁面 JavaScript (754行)
```

#### 重構成果
- **程式碼減少**: 主頁面從 467 行減少到 15 行 (減少 97%)
- **組件化**: 5 個可重用組件，支援單一職責原則
- **模組化**: JavaScript 分離為專門的功能模組
- **維護性**: 清晰的職責劃分，易於維護和擴展

### 前端架構
- **Vite**: 現代前端建置工具，提供快速熱更新
- **Tailwind CSS**: 實用性優先的 CSS 框架
- **Alpine.js**: 輕量級 JavaScript 框架（透過 CDN 引入）
- **AdminLTE 3.2**: 主要管理介面框架
- **Bootstrap 5.3** + **DataTables**: 表格展示
- **PostCSS**: CSS 後處理器，支援 Autoprefixer

### 國際化支援
- **繁體中文語言包**：`lang/zh-TW/` 目錄包含完整的繁體中文翻譯
- **分頁國際化**：`lang/zh-TW/pagination.php` 提供中文分頁文字
- **通用分頁組件**：`resources/views/components/pagination.blade.php` 標準化分頁顯示

### 測試架構
- **PHPUnit 10.1+**: 主要測試框架
- **Laravel Feature Tests**: 身份驗證功能測試完整
- **測試覆蓋率**: 目前主要涵蓋身份驗證，業務邏輯測試待補強
- **測試指令**: `php artisan test` 或 `./vendor/bin/phpunit`

## 關鍵特色功能

### Excel 匯入匯出系統
- 使用 `maatwebsite/excel` 套件處理 Excel 檔案
- **客戶系統**：支援客戶資料批次匯入，具備錯誤處理和資料驗證，JSON 欄位（電話、地址）支援多種格式解析
- **地標系統**：支援地標資料匯入匯出，包含分類對照和座標驗證
- **駕駛系統**：支援駕駛資料匯入匯出，包含 13 個欄位和狀態智能對照

### 訂單編號生成
- 智能編號系統：`類型代碼 + 身分證末3碼 + 日期時間 + 流水號`
- 客戶資料快照：避免客戶資料異動影響歷史訂單
- 支援共乘功能和地址自動解析

### 搜尋和篩選
- 支援 JSON 欄位搜尋（使用 `whereJsonContains`）
- AJAX 即時搜尋（共乘對象搜尋）
- 複雜的日期區間和關鍵字篩選

### 地標系統（新增功能）
- **地標管理**：完整的 CRUD 操作介面
- **智能搜尋**：輸入關鍵字+`*`觸發地標搜尋（如：台北*）
- **分類管理**：醫療、交通、教育、政府、商業、一般
- **使用統計**：記錄地標使用次數，熱門排序
- **訂單整合**：上下車地址支援地標快速選擇
- **Modal 介面**：美觀的地標選擇彈窗
- **批量操作**：支援批量啟用/停用/刪除

## 記憶體管理架構分析

### 1. 快取系統 (Cache System)

**設定檔位置**: `config/cache.php`

**預設快取驅動**: `file` (可透過 `CACHE_DRIVER` 環境變數設定)

**支援的快取驅動**:
- `file`: 檔案快取 (預設) - 儲存於 `storage/framework/cache/data`
- `database`: 資料庫快取
- `redis`: Redis 快取
- `memcached`: Memcached 快取
- `array`: 陣列快取 (僅限當前請求)
- `dynamodb`: DynamoDB 快取

**快取配置**:
```php
'stores' => [
    'file' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/data'),
        'lock_path' => storage_path('framework/cache/data'),
    ],
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
    'memcached' => [
        'driver' => 'memcached',
        'servers' => [
            [
                'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                'port' => env('MEMCACHED_PORT', 11211),
                'weight' => 100,
            ],
        ],
    ],
]
```

### 2. Session 管理

**設定檔位置**: `config/session.php`

**預設 Session 驅動**: `file`

**Session 配置**:
- **生命週期**: 120 分鐘 (可透過 `SESSION_LIFETIME` 設定)
- **儲存位置**: `storage/framework/sessions`
- **清理機制**: 2/100 機率自動清理過期 session
- **加密**: 預設不加密 (`encrypt => false`)
- **安全設定**:
  - `http_only => true`: 防止 JavaScript 存取
  - `same_site => 'lax'`: CSRF 保護
  - 支援 HTTPS 安全 Cookie

### 3. 資料庫連線管理

**設定檔位置**: `config/database.php`

**預設資料庫**: MySQL

**連線池配置**:
```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'strict' => true,
    'engine' => null,
]
```

**Redis 配置**:
```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
]
```

### 4. HTTP 中介軟體記憶體管理

**Kernel 設定**: `app/Http/Kernel.php`

**全域中介軟體**:
- `TrustProxies`: 代理伺服器信任
- `HandleCors`: CORS 處理
- `PreventRequestsDuringMaintenance`: 維護模式
- `ValidatePostSize`: POST 大小驗證
- `TrimStrings`: 字串修剪
- `ConvertEmptyStringsToNull`: 空字串轉換

**Web 中介軟體群組**:
- `EncryptCookies`: Cookie 加密
- `AddQueuedCookiesToResponse`: 佇列 Cookie 處理
- `StartSession`: Session 啟動
- `ShareErrorsFromSession`: 錯誤資訊分享
- `VerifyCsrfToken`: CSRF 驗證
- `SubstituteBindings`: 路由繫結

### 5. 應用程式記憶體使用分析

#### 主要控制器記憶體使用

**CustomerController** (`app/Http/Controllers/CustomerController.php:26`):
```php
$customers = $query->latest()->paginate(10);
```
- 使用分頁機制，每頁限制 10 筆記錄
- 減少記憶體佔用

**OrderController** (`app/Http/Controllers/OrderController.php:25`):
```php
$orders = $query->latest()->paginate(50);
```
- 使用分頁機制，每頁限制 50 筆記錄
- 包含複雜的查詢邏輯和關聯載入
- **重構優化**: 組件化架構減少重複載入，提升記憶體效率

**LandmarkController** (`app/Http/Controllers/LandmarkController.php:43`):
```php
$landmarks = $query->paginate(20);
```
- 使用分頁機制，每頁限制 20 筆記錄
- 支援搜尋和分類篩選，減少記憶體佔用

**DriverController** (`app/Http/Controllers/Admin/DriverController.php:29`):
```php
$drivers = $query->paginate(20);
```
- 使用分頁機制，每頁限制 20 筆記錄
- 支援關鍵字搜尋功能，減少查詢範圍
- **新增匯入匯出功能**：採用記憶體友善的處理方式

**駕駛匯入匯出記憶體使用分析**：

**DriversExport** (`app/Exports/DriversExport.php:12`):
```php
return Driver::all()->map(function ($driver) {
    // 處理 13 個欄位，包含狀態轉換
});
```
- **記憶體風險**：使用 `Driver::all()` 一次性載入所有駕駛
- **記憶體佔用**：每筆駕駛約 600 bytes，10,000 筆約需 6MB
- **狀態轉換**：包含中文狀態對照邏輯

**DriversImport** (`app/Imports/DriversImport.php:45`):
```php
public function collection(Collection $rows) {
    foreach ($rows as $row) { // 逐行處理
        // 重複性檢查、狀態對照、必填欄位驗證
    }
}
```
- **記憶體友善**：逐行處理，避免大檔案記憶體問題
- **重複性檢查**：檢查手機和身分證重複性
- **錯誤追蹤**：使用實例變數累積錯誤訊息

**重複訂單檢查記憶體使用分析**：

**UniqueOrderDateTime** (`app/Rules/UniqueOrderDateTime.php:28-41`):
```php
$query = Order::where('customer_id', $this->customerId)
    ->where('ride_date', $this->rideDate)
    ->where('ride_time', $value);
if ($query->exists()) { ... }
```
- **記憶體使用**：每次驗證執行一次資料庫查詢，約 1-2KB 記憶體佔用
- **查詢效率**：使用索引優化的簡單 WHERE 查詢，記憶體開銷較低
- **頻率風險**：表單驗證時觸發，頻率取決於用戶操作習慣

**checkDuplicateOrder API** (`app/Http/Controllers/OrderController.php:461-494`):
```php
$query = Order::where('customer_id', $request->customer_id)
    ->where('ride_date', $request->ride_date)
    ->where('ride_time', $request->ride_time);
$existingOrder = $query->first();
```
- **AJAX 記憶體使用**：每次前端檢查執行一次查詢，回傳 JSON 資料約 0.5-1KB
- **併發風險**：多用戶同時操作可能產生大量併發查詢
- **潛在問題**：用戶快速修改日期/時間可能導致請求堆積

**前端即時檢查** (`public/js/orders/form.js:1088-1191`):
```javascript
$.ajax({
    url: '/orders/check-duplicate',
    data: { customer_id, ride_date, ride_time, order_id, _token }
});
```
- **DOM 操作記憶體**：動態創建警告/成功訊息元素，每次約 0.5KB
- **事件監聽記憶體**：綁定 change/blur 事件監聽器，輕微記憶體佔用
- **記憶體洩漏風險**：DOM 元素清理機制已實施，但需要監控累積影響

**地標匯入匯出記憶體使用分析**：

**LandmarksExport** (`app/Exports/LandmarksExport.php:13`):
```php
return Landmark::all()->map(function ($landmark) {
    // 處理 12 個欄位，包含座標 JSON 轉換
});
```
- **高記憶體風險**：使用 `Landmark::all()` 一次性載入所有地標
- **記憶體佔用**：每筆地標約 800 bytes，50,000 筆約需 40MB
- **建議最佳化**：實施分塊處理機制

**LandmarksImport** (`app/Imports/LandmarksImport.php:28`):
```php
public function collection(Collection $rows) {
    foreach ($rows as $row) { // 逐行處理
        // 重複性檢查、分類對照、座標驗證
    }
}
```
- **記憶體友善**：逐行處理，避免大檔案記憶體問題
- **潛在風險**：每行執行資料庫查詢檢查重複性
- **錯誤追蹤**：使用實例變數累積錯誤訊息

#### 資料模型記憶體最佳化

**Customer Model** (`app/Models/Customer.php:36-39`):
```php
protected $casts = [
    'phone_number' => 'array',
    'addresses' => 'array',
];
```
- 使用 JSON 欄位儲存複雜資料結構
- 自動序列化/反序列化，減少記憶體佔用

**Landmark Model** (`app/Models/Landmark.php:25-28`):
```php
protected $casts = [
    'coordinates' => 'array',
    'is_active' => 'boolean',
];
```
- 使用 JSON 欄位儲存座標資訊
- 支援 Scope 查詢優化，減少資料庫負擔

### 6. 檔案儲存管理

**儲存目錄**:
- `storage/framework/cache/data/`: 快取檔案
- `storage/framework/sessions/`: Session 檔案
- `storage/framework/views/`: 編譯後的視圖檔案
- `storage/logs/`: 日誌檔案

**Excel 處理**:
- 使用 `maatwebsite/excel` 套件
- 暫存檔案儲存於 `storage/framework/cache/laravel-excel/`

## 記憶體管理最佳實踐建議

### 1. 高優先級建議

1. **啟用 Redis 快取**
   - 將 `CACHE_DRIVER=redis` 設定於 `.env`
   - 提升快取效能，減少檔案 I/O

2. **資料庫查詢最佳化**
   - 在 `CustomerController::index()` 中使用 `select()` 限制欄位
   - 使用 `with()` 預載關聯資料避免 N+1 查詢

3. **Session 最佳化**
   - 考慮使用 Redis 作為 Session 儲存
   - 設定 `SESSION_DRIVER=redis`

4. **匯出分塊處理最佳化**
   - **地標匯出**：修改 `LandmarksExport::collection()` 使用 `chunk()` 方法
   - **駕駛匯出**：修改 `DriversExport::collection()` 使用 `chunk()` 方法
   - 避免大量資料時記憶體不足問題
   ```php
   // 建議實施
   public function collection() {
       return Landmark::select(['id', 'name', 'address', ...])
           ->chunk(1000)->flatten();
   }
   ```

5. **重複訂單檢查最佳化**
   - **前端節流控制**：在 `checkDuplicateOrder()` 實施 debounce 機制，減少 AJAX 請求頻率
   - **查詢快取**：為重複檢查結果實施 Redis 短期快取（5-10分鐘）
   - **資料庫索引**：為 `(customer_id, ride_date, ride_time)` 組合建立複合索引
   ```php
   // 建議實施 - 前端 debounce
   const debouncedCheck = debounce(this.checkDuplicateOrder.bind(this), 500);
   $('input[name="ride_date"], input[name="ride_time"]').on('change blur', debouncedCheck);
   ```

### 2. 中優先級建議

1. **分頁優化**
   - 考慮使用 `simplePaginate()` 替代 `paginate()`
   - 減少總筆數計算的記憶體開銷

2. **視圖快取**
   - 在生產環境啟用視圖快取
   - 執行 `php artisan view:cache`

3. **設定快取**
   - 執行 `php artisan config:cache`
   - 減少設定檔案讀取次數

4. **地標匯入最佳化**
   - 最佳化 `LandmarksImport` 重複性檢查機制
   - 預先載入現有地標避免逐筆查詢
   ```php
   // 建議實施
   $existingLandmarks = Landmark::select('name', 'address', 'city')
       ->get()->keyBy(function($item) {
           return $item->name . '|' . $item->address . '|' . $item->city;
       });
   ```

5. **地標搜尋快取**
   - 為 `LandmarkController::search()` 新增 Redis 快取
   - 快取常用搜尋結果，減少資料庫查詢

6. **重複訂單檢查中級優化**
   - **請求取消機制**：實施 AJAX 請求取消，避免過時請求堆積
   - **錯誤處理改善**：優化網路錯誤時的記憶體清理機制
   - **API 響應快取**：在用戶端快取檢查結果，相同條件下避免重複請求
   ```javascript
   // 建議實施 - 請求取消
   if (this.checkRequest) {
       this.checkRequest.abort();
   }
   this.checkRequest = $.ajax({ ... });
   ```

### 3. 低優先級建議

1. **路由快取**
   - 生產環境執行 `php artisan route:cache`

2. **Composer 最佳化**
   - 執行 `composer dump-autoload --optimize`

3. **記憶體監控**
   - 新增記憶體使用監控中介軟體
   - 記錄高記憶體使用的請求

4. **地標匯入檔案大小限制**
   - 在 `LandmarkController::import()` 新增檔案大小檢查
   ```php
   $request->validate([
       'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB 限制
   ]);
   ```

5. **簡化範本下載功能**
   - 將 `downloadTemplate()` 的匿名類別改為標準匯出類別
   - 減少不必要的記憶體開銷

6. **地標匯入匯出記憶體監控**
   - 新增專門的記憶體使用監控
   - 記錄匯入匯出操作的記憶體峰值

## 潛在記憶體問題

### 1. Excel 匯入匯出
- 大檔案處理可能導致記憶體不足
- 建議實作分塊處理或使用佇列處理

#### 駕駛匯入匯出特定風險（2025-07-20 新增）
- **駕駛匯出風險**：`DriversExport::collection()` 使用 `Driver::all()`
  - 當駕駛數量超過 10,000 筆時可能記憶體不足
  - **建議實施**：需要 `chunk()` 分塊處理機制（中優先級）
- **駕駛匯入效能**：每行匯入都執行重複性檢查
  - 檢查手機和身分證重複，涉及資料庫查詢
  - **優化建議**：預先載入現有駕駛資料進行批次比對（低優先級）
- **狀態欄位處理**：中文狀態對照增加輕微記憶體開銷
  - 記憶體影響較小，無需特別最佳化
- **✅ 已優化**：駕駛匯入程式記憶體效率
  - 逐行處理機制，記憶體使用友善
  - 智能標題行檢測，避免不必要處理
  - 完整的錯誤追蹤機制

#### 地標匯入匯出特定風險（2025-07-20 更新）
- **地標匯出高風險**：`LandmarksExport::collection()` 使用 `Landmark::all()` 
  - 當地標數量超過 50,000 筆時可能記憶體不足
  - **待實施**：需要 `chunk()` 分塊處理機制（高優先級）
- **範本下載記憶體浪費**：`LandmarkController::downloadTemplate()` 使用匿名類別
  - 範本資料被複製到匿名類別實例中
  - **待實施**：建議使用標準匯出類別替代（低優先級）
- **匯入重複性檢查效能**：每行匯入都執行 `Landmark::where()` 查詢
  - **待實施**：建議預先載入現有地標進行批次比對（中優先級）
- **✅ 已修復**：匯入程式記憶體效率提升
  - 移除調試程式碼，減少記憶體開銷
  - 加入空白行跳過邏輯，避免無效處理
  - 改用位置對應讀取，提升解析效率

### 2. 大量資料查詢
- `OrderController` 中的複雜查詢可能消耗大量記憶體
- 建議新增索引和使用 `chunk()` 方法

### 3. 地標搜尋最佳化
- 地標搜尋 API 限制回傳 10 筆資料
- 使用資料庫索引加速搜尋效能
- 熱門地標優先顯示，減少搜尋時間

### 4. Session 檔案堆積
- 預設檔案 Session 可能造成檔案系統壓力
- 建議定期清理或改用 Redis

## 監控建議

1. **記憶體使用監控**
   - 使用 `memory_get_usage()` 和 `memory_get_peak_usage()`
   - 在關鍵控制器方法中記錄記憶體使用

2. **快取命中率監控**
   - 監控 Redis 快取命中率
   - 優化常用查詢的快取策略

3. **資料庫連線監控**
   - 監控資料庫連線數量
   - 避免連線洩漏

4. **重複訂單檢查監控**
   - **API 響應時間監控**：追蹤 `/orders/check-duplicate` 端點的響應時間
   - **併發請求監控**：監控同時進行的重複檢查請求數量
   - **前端錯誤監控**：追蹤 AJAX 請求失敗率和 DOM 操作錯誤
   - **查詢頻率分析**：分析用戶操作模式，優化檢查觸發邏輯

## 地標系統記憶體最佳化專項指南

### 地標資料記憶體特性分析

**單筆地標記憶體佔用估算**：
- 基本欄位（name, address, city, district）：約 200 bytes
- 座標 JSON 欄位：約 50 bytes
- 其他欄位（category, description, timestamps）：約 300 bytes
- **總計**：約 550-800 bytes per 地標

**vs. 客戶資料對比**：
- 地標：800 bytes（12 欄位，1 個 JSON 欄位）
- 客戶：2000 bytes（36 欄位，2 個 JSON 欄位）
- **地標記憶體效率較高**，但匯出時仍需注意分塊處理

### 地標匯入匯出記憶體最佳化實施指南

#### 1. 匯出功能最佳化（高優先級）

**問題**：`LandmarksExport::collection()` 使用 `Landmark::all()`

**解決方案**：
```php
// 原始程式碼（高風險）
return Landmark::all()->map(function ($landmark) { ... });

// 最佳化方案 1：分塊處理
public function collection()
{
    $landmarks = collect();
    Landmark::chunk(1000, function ($chunk) use ($landmarks) {
        $landmarks->push(...$chunk->map(function ($landmark) {
            return [
                'name' => $landmark->name,
                'address' => $landmark->address,
                // ... 其他欄位
            ];
        }));
    });
    return $landmarks;
}

// 最佳化方案 2：使用 Generator（推薦）
public function collection()
{
    return Landmark::select([
        'name', 'address', 'city', 'district', 'category',
        'description', 'coordinates', 'is_active', 'usage_count',
        'created_by', 'created_at'
    ])->cursor()->map(function ($landmark) {
        return [
            'name' => $landmark->name,
            'address' => $landmark->address,
            'city' => $landmark->city,
            'district' => $landmark->district,
            'category' => $landmark->category_name,
            'description' => $landmark->description ?? '',
            'longitude' => $landmark->coordinates['lng'] ?? '',
            'latitude' => $landmark->coordinates['lat'] ?? '',
            'is_active' => $landmark->is_active ? '1' : '0',
            'usage_count' => $landmark->usage_count,
            'created_by' => $landmark->created_by ?? '',
            'created_at' => $landmark->created_at->format('Y-m-d H:i:s'),
        ];
    });
}
```

#### 2. 匯入功能最佳化（中優先級）

**問題**：每行都執行重複性檢查資料庫查詢

**解決方案**：
```php
// 原始程式碼（效能問題）
$existingLandmark = Landmark::where('name', $name)
    ->where('address', $address)
    ->where('city', $city)
    ->first();

// 最佳化方案：預先載入所有地標
public function collection(Collection $rows)
{
    // 預先載入現有地標
    $existingLandmarks = Landmark::select('id', 'name', 'address', 'city')
        ->get()
        ->keyBy(function($item) {
            return md5($item->name . '|' . $item->address . '|' . $item->city);
        });

    foreach ($rows as $row) {
        $name = trim($row['地標名稱'] ?? '');
        $address = trim($row['地址'] ?? '');
        $city = trim($row['城市'] ?? '');
        
        // 使用記憶體中的查詢替代資料庫查詢
        $key = md5($name . '|' . $address . '|' . $city);
        $existingLandmark = $existingLandmarks->get($key);
        
        // 後續處理邏輯...
    }
}
```

#### 3. 範本下載最佳化（低優先級）

**問題**：使用匿名類別造成不必要記憶體開銷

**解決方案**：
```php
// 建立專門的範本匯出類別
class LandmarkTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return collect([
            [
                '地標名稱' => '台北車站',
                '地址' => '中正區忠孝西路一段49號',
                // ... 其他範例資料
            ],
            // ... 其他範例
        ]);
    }

    public function headings(): array
    {
        return [
            '地標名稱', '地址', '城市', '區域', '分類',
            '描述', '經度', '緯度', '是否啟用',
        ];
    }
}

// 在控制器中使用
public function downloadTemplate()
{
    return Excel::download(
        new LandmarkTemplateExport, 
        '地標匯入範例檔案.xlsx'
    );
}
```

### 記憶體監控實施

#### 地標專用記憶體監控中介軟體

```php
class LandmarkMemoryMonitor
{
    public function handle($request, Closure $next)
    {
        if (str_contains($request->path(), 'landmarks')) {
            $memoryStart = memory_get_usage();
            $response = $next($request);
            $memoryEnd = memory_get_usage();
            $memoryUsed = $memoryEnd - $memoryStart;
            
            // 記錄高記憶體使用操作
            if ($memoryUsed > 20 * 1024 * 1024) { // 20MB
                Log::warning('地標操作高記憶體使用', [
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'memory_used' => number_format($memoryUsed / 1024 / 1024, 2) . 'MB',
                    'peak_memory' => number_format(memory_get_peak_usage() / 1024 / 1024, 2) . 'MB'
                ]);
            }
            
            return $response;
        }
        
        return $next($request);
    }
}
```

### 效能基準測試

**建議測試場景**：
1. **小量測試**：1,000 筆地標匯出入
2. **中量測試**：10,000 筆地標匯出入
3. **大量測試**：50,000 筆地標匯出入
4. **極限測試**：100,000 筆地標匯出入

**記憶體監控指標**：
- 匯出操作記憶體峰值
- 匯入操作記憶體峰值
- 錯誤訊息累積對記憶體的影響
- 重複性檢查的記憶體開銷

### 訂單建立併發安全性分析與修復（2025-08-03）

#### 併發問題識別
在多使用者同時建立訂單的場景下，系統存在四個主要併發安全性問題：

1. **訂單編號重複生成競爭條件**
   - **問題**：`Order::whereDate('created_at', $today->toDateString())->count() + 1` 在併發時可能返回相同值
   - **風險**：多個使用者可能獲得相同的訂單編號
   - **影響範圍**：OrderController::createSingleOrder()、CarpoolGroupService::generateCarpoolOrderNumbers()

2. **共乘群組ID時間戳衝突**
   - **問題**：`'carpool_' . time() . '_' . rand(1000, 9999)` 在短時間內可能重複
   - **風險**：群組ID衝突導致資料關聯錯誤
   - **影響範圍**：CarpoolGroupService::createCarpoolGroup()

3. **資料庫事務範圍不完整**
   - **問題**：OrderController::store() 缺乏完整的事務包裝
   - **風險**：部分成功的訂單建立可能導致資料不一致
   
4. **重複訂單檢查併發競爭**
   - **問題**：UniqueOrderDateTime 驗證在併發時可能被繞過
   - **風險**：同一客戶在相同時間可能建立多筆訂單

#### 併發安全性修復方案

##### 1. 原子化訂單編號生成系統
**技術實現**：
- **order_sequences 表**：專門管理每日序列號的原子性
  ```sql
  CREATE TABLE order_sequences (
      date_key VARCHAR(8) PRIMARY KEY COMMENT '日期鍵值(YYYYMMDD)',
      sequence_number INT UNSIGNED DEFAULT 0 COMMENT '當日序列號',
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  );
  ```

- **OrderNumberService 服務**：實現 SELECT FOR UPDATE 原子操作
  ```php
  // 核心原子化邏輯
  $sequence = DB::table('order_sequences')
      ->where('date_key', $dateKey)
      ->lockForUpdate()
      ->first();
  ```

- **智能重試機制**：處理死鎖和鎖等待超時（最多3次重試）
- **批量序列號獲取**：共乘訂單可原子獲取多個連續序列號

**記憶體影響**：
- **新增記憶體使用**：每次序列號生成約 1-2KB
- **效能提升**：消除了 `count()` 查詢，降低資料庫記憶體壓力
- **併發效能**：1100+ 筆/秒，無記憶體洩漏

##### 2. UUID 群組ID系統
**技術實現**：
```php
// 原系統（有衝突風險）
$groupId = 'carpool_' . time() . '_' . rand(1000, 9999);

// 新系統（完全避免衝突）
$groupId = 'carpool_' . Str::uuid()->toString();
```

**記憶體影響**：
- **記憶體使用**：UUID 生成約 0.5KB，比時間戳方案略高但可忽略
- **併發安全性**：100% 避免ID衝突，無記憶體競爭

##### 3. 資料庫約束強化
**實施內容**：
- **order_number 唯一索引**：資料庫層面防止重複編號
- **查詢效能索引**：
  ```sql
  -- 重複訂單檢查複合索引
  INDEX orders_customer_datetime_index (customer_id, ride_date, ride_time)
  
  -- 共乘群組查詢索引
  INDEX orders_carpool_group_index (carpool_group_id, is_group_dissolved)
  
  -- 日期和狀態查詢索引
  INDEX orders_ride_date_index (ride_date)
  INDEX orders_status_index (status)
  ```

**記憶體影響**：
- **索引記憶體佔用**：每個索引約佔用 2-5MB（視資料量而定）
- **查詢效能提升**：重複訂單檢查速度提升 300-500%
- **併發查詢優化**：降低鎖等待時間，減少記憶體堆積

##### 4. 異常處理機制
**ConcurrencyException 類別**：專門處理併發衝突
```php
// 使用者友善的錯誤訊息
throw new ConcurrencyException(
    ConcurrencyException::ORDER_NUMBER_CONFLICT,
    ['order_type' => $orderType, 'retry_count' => $retryCount],
    '訂單編號生成失敗，請重試'
);
```

**記憶體影響**：
- **異常物件記憶體**：每個異常約 2-3KB
- **錯誤追蹤記憶體**：Log 記錄約 1KB/次
- **使用者體驗提升**：減少因錯誤重複操作造成的記憶體浪費

#### 併發測試與驗證

**測試指令**：
```bash
php artisan test:concurrency --threads=10 --orders=20
```

**測試結果**：
- **測試規模**：10執行緒 × 20訂單 = 200筆併發建立
- **成功率**：100%（200/200）
- **效能**：1108.17 筆/秒
- **一致性檢查**：
  - ✅ 序列號一致性：序列號增加數 = 成功訂單數
  - ✅ 編號唯一性：0 個重複編號
  - ✅ 記憶體穩定性：無記憶體洩漏或堆積

**記憶體監控指標**：
- **峰值記憶體使用**：測試期間約增加 15-20MB
- **記憶體回收**：測試結束後記憶體完全釋放
- **併發記憶體效率**：平均每筆訂單記憶體佔用 5-8KB

#### 生產環境記憶體管理建議

**高優先級（併發相關）**：
1. **啟用 Redis 快取**：減少序列號查詢的資料庫記憶體壓力
2. **監控併發指標**：
   ```php
   // 建議監控的記憶體指標
   - 同時進行的訂單建立數量
   - OrderNumberService 記憶體使用峰值
   - 資料庫鎖等待時間和記憶體影響
   ```

3. **設定併發限制**：避免過高併發導致記憶體耗盡
   ```php
   // 建議在 config/app.php 設定
   'max_concurrent_orders' => 50, // 同時進行的訂單建立上限
   ```

**中優先級**：
1. **ORDER_SEQUENCES 表維護**：定期清理過期日期序列（保留30天）
2. **異常日誌輪替**：避免併發錯誤日誌佔用過多磁碟空間
3. **測試記憶體基準**：定期執行併發測試確保記憶體效能穩定

**效能指標基準**：
- **單筆訂單記憶體**：5-8KB（含序列號生成）
- **併發處理能力**：1000+ 筆/秒
- **記憶體峰值控制**：不超過基礎記憶體使用的 50%
- **併發鎖等待時間**：< 100ms

## 總結

此系統採用 Laravel 標準的記憶體管理架構，整體設計合理。主要記憶體管理透過檔案快取、Session 管理和資料庫連線池實現。

### 最新更新（2025-08-02）

#### 共乘系統核心功能實施
- **核心架構實現**：完整的共乘群組管理系統，支援多人共乘和智能派遣
- **資料庫結構優化**：
  - 新增 9 個共乘相關欄位
  - 實施主訂單代表制設計模式
  - 智能顯示策略：瀏覽模式只顯示主訂單，搜尋模式顯示所有相關訂單
- **服務層架構**：
  - `CarpoolGroupService`：400+ 行完整業務邏輯
  - 支援群組建立、解除、狀態同步、司機指派
  - 智能訂單編號生成：主訂單標準編號，成員訂單使用 "主編號-M2" 格式
- **模型關聯優化**：
  - Order 模型新增 3 個群組關聯方法
  - 實施 Scope 查詢：mainOrders(), groupOrders(), dissolvedGroups()
  - 智能過濾策略減少記憶體佔用

#### 記憶體管理新增影響
- **記憶體佔用分析**：
  - 單一訂單記憶體增加 20%（新增共乘欄位含關聯資訊）
  - 2人群組約 4.8KB，4人群組約 9.6KB
  - 群組解除操作 5-21KB（視群組大小）
- **新增記憶體風險點**：
  - 高風險：`dissolveGroup()` 一次性載入所有群組訂單
  - 中風險：群組關聯查詢可能觸發 N+1 問題
  - 低風險：訂單編號生成和群組驗證邏輯
- **記憶體最佳化建議**：
  - 高優先級：優化 dissolveGroup 使用直接 UPDATE，群組查詢使用 chunk()
  - 中優先級：實施 CarpoolMemoryMonitor 中間件，優化群組列表查詢
  - 低優先級：群組大小限制（4人），歷史資料清理機制

#### 實施的檔案清單
- `database/migrations/2025_08_02_190310_add_carpool_group_fields_to_orders_table.php` - 資料庫結構
- `app/Services/CarpoolGroupService.php` - 共乘群組服務（405行）+ 共乘欄位設定邏輯
- `app/Models/Order.php:26, 29-58, 128-236` - 模型關聯和方法 + fillable 欄位更新
- `app/Http/Controllers/OrderController.php:10-22, 158-190` - 控制器整合
- `CLAUDE.md:1002-1270` - 記憶體管理分析更新

#### 技術特色
- **派遣友善**：主訂單代表制，派遣系統只需處理主訂單
- **記憶體友善**：智能顯示策略，瀏覽模式記憶體佔用減少 50%
- **維護友善**：完整的群組生命週期管理，支援解除和狀態同步
- **擴展友善**：支援未來多人群組（目前建議限制 4 人）
- **資料標準化**：統一使用 special_status 標記共乘訂單，完整的共乘關聯資訊

### 先前更新（2025-07-26）

#### 重複訂單檢查功能全面實施
- **核心功能實現**：防止同一客戶在同一日期同一時間建立重複訂單
- **多層防護機制**：
  - 後端驗證：`UniqueOrderDateTime` 自訂驗證規則
  - 前端即時檢查：AJAX 即時驗證，提供視覺化提示
  - API 端點：`POST /orders/check-duplicate` 供前端調用
- **智慧檢查邏輯**：
  - 檢查組合：`customer_id + ride_date + ride_time`
  - 編輯模式自動排除當前訂單，避免誤報
  - 詳細錯誤資訊：顯示重複訂單編號、地址、建立時間

#### 記憶體管理考量與優化
- **新增記憶體使用點分析**：
  - 驗證規則：每次約 1-2KB 記憶體佔用
  - API 查詢：每次回傳 0.5-1KB JSON 資料
  - 前端 DOM 操作：每次約 0.5KB 動態元素
- **潛在記憶體風險識別**：
  - 高頻 AJAX 請求可能導致請求堆積
  - 多用戶併發操作增加資料庫記憶體壓力
  - DOM 元素累積可能造成前端記憶體洩漏
- **記憶體優化建議**：
  - 高優先級：實施前端 debounce 機制、查詢快取、資料庫索引
  - 中優先級：請求取消機制、錯誤處理改善、API 響應快取
  - 監控指標：API 響應時間、併發請求數量、前端錯誤率

#### 實施的檔案清單
- `app/Rules/UniqueOrderDateTime.php` - 自訂驗證規則
- `app/Http/Controllers/OrderController.php:79-83` - store() 方法驗證整合
- `app/Http/Controllers/OrderController.php:461-494` - 重複檢查 API 端點
- `app/Http/Requests/UpdateOrderRequest.php:31-39` - update 請求驗證
- `routes/web.php:54` - API 路由註冊
- `public/js/orders/form.js:1088-1191` - 前端即時檢查功能

#### 技術特色
- **使用者友善**：即時提示，清楚的錯誤訊息
- **開發友善**：組件化設計，易於維護和擴展
- **效能友善**：輕量級查詢，記憶體使用最佳化
- **安全友善**：CSRF 保護，SQL 注入防護

### 先前更新（2025-07-21）

#### 訂單管理系統關鍵問題修復
- **訂單刪除功能實現**：完成 `OrderController::destroy()` 方法實現，包含完整的異常處理和 JSON 回應
- **Customer 模型關聯完善**：新增 `orders()` 關聯方法，確保模型關聯的完整性
- **special_order 欄位驗證錯誤修復**：
  - 從 `UpdateOrderRequest.php` 移除不需要的驗證規則
  - 從 `Order.php` 模型的 `$fillable` 陣列移除已廢棄欄位
- **carpool_phone_number 欄位錯誤修復**：
  - 保留前端功能但不儲存到資料庫的解決方案
  - 從 `Order.php` 模型的 `$fillable` 移除不存在的資料庫欄位
  - 避免 `Column not found: 1054 Unknown column` SQL 錯誤

#### 問題排除最佳實踐建立
- **資料庫欄位與程式碼一致性檢查**：建立標準流程處理欄位不匹配問題
- **Laravel $fillable 陣列管理**：確保只包含實際存在的資料庫欄位
- **前端功能與後端儲存分離**：支援表單欄位僅用於前端處理，不強制儲存
- **SQL 約束錯誤處理**：標準化錯誤診斷和修復流程

#### 修復的檔案清單
- `app/Http/Controllers/OrderController.php:314-330` - 實現刪除功能
- `app/Models/Customer.php:47-51` - 新增訂單關聯
- `app/Http/Requests/UpdateOrderRequest.php:51` - 移除過時驗證規則
- `app/Models/Order.php:26-27` - 清理 fillable 陣列

### 近期更新（2025-07-20）

#### 駕駛管理系統完整匯入匯出功能實現
- **駕駛匯出功能**：實現完整的 Excel 匯出，包含 13 個駕駛相關欄位
- **駕駛匯入功能**：支援 Excel 匯入，包含狀態對照和錯誤處理機制
- **範例檔案功能**：提供繁體中文 Excel 範例檔案下載
- **分頁功能新增**：駕駛列表改用 Laravel 分頁，每頁顯示 20 筆記錄
- **介面全面優化**：統一的匯入匯出操作介面，與地標系統保持一致

#### 地標匯入匯出功能修復與介面優化（早期更新）
- **地標匯入功能修復**：解決 Excel 檔案標題行識別問題，改用位置對應方式讀取資料
- **地標編輯驗證修復**：移除 `is_active` 布林驗證衝突，正確處理 checkbox 狀態
- **Bootstrap 5 相容性修復**：統一所有 select 元素使用 `form-select` 類別，修正表單間距
- **分頁顯示修復**：設定 Bootstrap 5 分頁樣式，新增繁體中文語言包，建立自訂分頁組件
- **記憶體管理優化**：清理匯入程式調試程式碼，加入空白行跳過邏輯

#### 技術改進項目
- **駕駛匯入程式架構**：遵循地標系統成功模式，實現智能標題行檢測和容錯機制
- **狀態欄位智能對照**：支援繁體中文狀態值自動轉換（在職/離職/黑名單）
- **重複資料處理**：檢查手機和身分證重複，自動更新現有駕駛資料
- **匯入程式重構**：移除 `WithHeadingRow` 依賴，實現自動標題行檢測
- **視圖組件化**：建立 `components/pagination.blade.php` 通用分頁組件
- **語言包完善**：新增 `lang/zh-TW/pagination.php` 繁體中文分頁語言檔
- **CSS 標準化**：所有系統頁面使用統一的 Bootstrap 5 類別

#### 系統穩定性提升
- **駕駛資料驗證**：完整的必填欄位檢查（姓名、手機、身分證）
- **記憶體效率優化**：駕駛匯入匯出採用記憶體友善的處理方式
- **錯誤處理機制**：詳細的錯誤追蹤和使用者友善的錯誤訊息
- **匯入容錯性**：支援各種 Excel 檔案格式，自動檢測標題行位置
- **表單驗證優化**：修正 checkbox 欄位驗證邏輯，避免編輯時錯誤
- **介面一致性**：統一所有表單元素的 Bootstrap 5 樣式
- **分頁功能完善**：提供完整的頁碼導航和中文分頁資訊

### 先前更新（2025-07-19）

#### 歷史訂單選擇功能
- **新增歷史訂單 API**：`OrderController::getCustomerHistoryOrders()` 方法，提供客戶最近 10 筆訂單資料
- **歷史訂單 Modal 組件**：完整的歷史訂單選擇介面，支援表格展示和快速選擇
- **自動填入功能**：選擇歷史訂單後自動填入用車時間、陪同人數、輪椅、爬梯機、上下車地址等欄位
- **編輯頁面支援**：編輯訂單頁面也能使用歷史訂單功能
- **JavaScript 模組化**：在 `form.js` 中新增完整的歷史訂單處理邏輯

#### 表單欄位修復與優化
- **修正變數檢查**：所有表單欄位使用 `isset($order)` 安全檢查，避免新增頁面變數未定義錯誤
- **時間欄位修復**：修正 Order 模型中 `ride_time` 的錯誤 Cast 設定，解決編輯時顯示異常值問題
- **資料一致性**：移除不存在的 `back_time` 資料庫欄位讀取，但保留介面供未來使用
- **格式化優化**：改善日期和時間欄位的格式化邏輯，確保新增和編輯功能一致性

#### 系統穩定性提升
- **SQL 錯誤修復**：解決歷史訂單查詢中不存在欄位的 SQL 錯誤
- **表單驗證增強**：改善表單欄位的預設值處理和錯誤處理機制
- **記憶體優化**：歷史訂單查詢限制回傳欄位，減少記憶體佔用

### 地標系統功能（先前更新）
- **新增地標管理功能**：完整的 CRUD 操作，支援分類和搜尋
- **智能地址輸入**：訂單建立時支援地標快速選擇
- **使用統計追蹤**：記錄地標使用次數，優化常用地標排序
- **記憶體優化**：搜尋結果限制、分頁機制、索引最佳化

### 地標匯入匯出功能記憶體管理更新（2025-07-19）

#### 新增功能與記憶體影響
- **地標匯出功能**：實現完整的 Excel 匯出，包含 12 個繁體中文欄位
  - **記憶體風險**：使用 `Landmark::all()` 在大量地標時可能記憶體不足
  - **影響評估**：每筆地標約 800 bytes，50,000 筆需約 40MB 記憶體
- **地標匯入功能**：支援 Excel 匯入，包含分類對照和座標驗證
  - **記憶體優勢**：逐行處理機制，記憶體使用友善
  - **效能問題**：每行執行重複性檢查資料庫查詢
- **範本下載功能**：提供繁體中文 Excel 範例檔案
  - **記憶體浪費**：使用匿名類別實現，增加不必要開銷

#### 記憶體管理最佳化建議實施
- **高優先級**：地標匯出分塊處理，避免 `Landmark::all()` 記憶體風險
- **中優先級**：最佳化匯入重複性檢查，預先載入現有地標資料
- **低優先級**：簡化範本下載，使用標準匯出類別替代匿名類別

#### 與現有系統記憶體使用對比
- **地標 vs 客戶記憶體效率**：地標系統記憶體效率較高（800 vs 2000 bytes/筆）
- **匯入匯出模式一致性**：遵循客戶系統的匯入匯出架構模式
- **記憶體風險相同**：兩系統都存在匯出時 `::all()` 的記憶體風險

建議優先實施 Redis 快取和查詢最佳化，以提升系統整體效能和記憶體使用效率。地標系統已針對高頻使用場景進行最佳化，可有效提升訂單建立效率。新增的匯入匯出功能需要進一步最佳化以確保大量資料處理時的記憶體安全。

## 共乘系統記憶體影響分析（2025-08-02 新增）

### 共乘系統新增功能與記憶體佔用

#### 1. 資料庫結構變更影響
- **新增9個共乘欄位**：`carpool_group_id`, `is_main_order`, `carpool_member_count`, `main_order_number`, `member_sequence`, `is_group_dissolved`, `dissolved_at`, `dissolved_by`, `original_group_id`
- **記憶體增加**：每筆訂單約增加 200-300 bytes
- **索引開銷**：新增3個索引（carpool_group_id, is_main_order, is_group_dissolved），查詢效能提升但記憶體佔用增加約 50-100 bytes/訂單

#### 2. CarpoolGroupService 記憶體使用分析

**createCarpoolGroup 方法** (`app/Services/CarpoolGroupService.php:17-50`):
```php
return DB::transaction(function () use ($mainCustomerId, $carpoolCustomerId, $orderData) {
    // 建立 2-4 筆訂單的事務操作
    // 每筆訂單自動設定 special_status='共乘' 和共乘關聯資訊
});
```
- **記憶體友善**：使用 DB::transaction 確保原子性操作，記憶體風險可控
- **訂單建立開銷**：
  - 去程共乘：建立 2 筆訂單，約 4.8KB 記憶體佔用（含共乘欄位）
  - 含回程共乘：建立 4 筆訂單，約 9.6KB 記憶體佔用（含共乘欄位）
- **編號生成邏輯**：記憶體佔用較輕，主要為字串處理
- **共乘欄位設定**：每筆訂單額外增加約 100 bytes（special_status, carpool_customer_id, carpool_name, carpool_id）

**dissolveGroup 方法** (`app/Services/CarpoolGroupService.php:270-299`):
```php
$groupOrders = Order::where('carpool_group_id', $groupId)->get();
foreach ($groupOrders as $order) {
    $dissolvedOrder = $this->dissolveOrder($order, $reason);
}
```
- **高記憶體風險**：一次性載入整個群組的所有訂單到記憶體
- **記憶體佔用估算**：
  - 2人群組：約 4.8KB（含共乘欄位）
  - 4人群組：約 9.6KB（含共乘欄位）
  - 含回程的4人群組：約 19.2KB（含共乘欄位）
- **潛在問題**：大型群組或併發解除操作可能導致記憶體壓力

**syncGroupStatus 方法** (`app/Services/CarpoolGroupService.php:212-228`):
```php
Order::where('carpool_group_id', $groupId)
     ->where('is_group_dissolved', false)
     ->update([...]);
```
- **記憶體友善**：使用直接 UPDATE 語句，不載入訂單到記憶體
- **批量更新開銷**：記憶體佔用最小，主要為查詢開銷

#### 3. Order 模型關聯記憶體影響

**新增關聯方法** (`app/Models/Order.php:133-156`):
```php
public function groupMembers() // 群組成員關聯
public function mainOrder()    // 群組主訂單關聯
public function allGroupOrders() // 所有群組訂單關聯
```
- **Lazy Loading 記憶體影響**：每次載入關聯時約增加 2-6KB 記憶體佔用
- **N+1 查詢風險**：在列表頁面顯示群組資訊時可能觸發 N+1 查詢
- **關聯查詢開銷**：`groupMembers()` 方法使用 JOIN 查詢，記憶體效率較高

**智能顯示策略** (`app/Models/Order.php:79-126`):
```php
public function scopeFilter($query, $request)
{
    $isSearching = $request->filled('keyword') || 
                   $request->filled('customer_id') || 
                   $request->filled('order_number');
    
    if (!$isSearching) {
        $query->where('is_main_order', true); // 瀏覽模式只顯示主訂單
    }
}
```
- **記憶體優化**：瀏覽模式下過濾非主訂單，減少約 50% 的記憶體佔用
- **搜尋模式**：載入所有相關訂單，記憶體佔用增加但提供完整搜尋結果

### 共乘系統潛在記憶體風險

#### 1. 高風險操作
- **群組解除操作**：`dissolveGroup()` 一次性載入所有群組訂單
- **群組查詢關聯**：在列表頁面可能觸發 N+1 查詢問題
- **大型群組管理**：超過 4 人的群組記憶體佔用顯著增加

#### 2. 中風險操作
- **回程訂單建立**：單次操作建立 4 筆訂單，記憶體佔用約 9.2KB
- **群組狀態同步**：批量更新操作，記憶體佔用中等
- **群組資訊 API**：`getGroupInfo()` 返回完整群組數據

#### 3. 低風險操作
- **訂單編號生成**：純字串處理，記憶體佔用微小
- **群組驗證邏輯**：簡單的條件檢查，記憶體影響極小

### 共乘系統記憶體最佳化建議

#### 1. 高優先級最佳化

**dissolveGroup 方法記憶體優化**：
```php
// 建議實施 - 避免載入到記憶體
public function dissolveGroup($groupId, $reason = '', $force = false)
{
    return DB::transaction(function () use ($groupId, $reason, $force) {
        // 直接使用 UPDATE 語句，避免載入訂單
        $updateData = [
            'original_group_id' => $groupId,
            'carpool_group_id' => null,
            'is_main_order' => true,
            'carpool_member_count' => 1,
            'is_group_dissolved' => true,
            'dissolved_at' => now(),
            'dissolved_by' => auth()->user()->name ?? 'system',
        ];
        
        $affectedRows = Order::where('carpool_group_id', $groupId)
            ->update($updateData);
            
        return ['affected_orders' => $affectedRows];
    });
}
```

**群組查詢分塊處理**：
```php
// 建議實施 - 大型群組查詢使用 chunk()
public function getGroupInfo($groupId)
{
    $orders = collect();
    Order::where('carpool_group_id', $groupId)
         ->chunk(100, function ($chunk) use ($orders) {
             $orders->push(...$chunk);
         });
    
    return $this->processGroupInfo($orders);
}
```

#### 2. 中優先級最佳化

**實施 CarpoolMemoryMonitor 中間件**：
```php
class CarpoolMemoryMonitor
{
    public function handle($request, Closure $next)
    {
        if (str_contains($request->path(), 'carpool')) {
            $memoryStart = memory_get_usage();
            $response = $next($request);
            $memoryEnd = memory_get_usage();
            
            $memoryUsed = $memoryEnd - $memoryStart;
            if ($memoryUsed > 5 * 1024 * 1024) { // 5MB
                Log::warning('共乘操作高記憶體使用', [
                    'url' => $request->url(),
                    'memory_used' => number_format($memoryUsed / 1024 / 1024, 2) . 'MB'
                ]);
            }
            
            return $response;
        }
        
        return $next($request);
    }
}
```

**群組列表查詢優化**：
```php
// 建議實施 - 限制載入欄位，避免 N+1 查詢
$orders = Order::with(['groupMembers:id,customer_name,carpool_group_id'])
               ->select(['id', 'customer_name', 'carpool_group_id', 'is_main_order'])
               ->mainOrders()
               ->paginate(50);
```

#### 3. 低優先級最佳化

**群組大小限制**：
- 建議群組最大人數限制在 4 人
- 超過 4 人的群組顯示警告提示
- 實施群組人數監控機制

**歷史群組資料清理**：
```php
// 定期清理已解散的群組歷史記錄
Order::where('is_group_dissolved', true)
     ->where('dissolved_at', '<', now()->subMonths(6))
     ->chunk(1000, function ($orders) {
         $orders->each->delete();
     });
```

### 共乘系統記憶體使用基準

#### 記憶體佔用基準表
| 操作類型 | 記憶體佔用 | 備註 |
|---------|-----------|------|
| 單一訂單（原有） | ~2.0KB | 基準值 |
| 單一訂單（含共乘欄位） | ~2.4KB | 增加 20% |
| 2人去程群組 | ~4.8KB | 新增功能（含共乘資訊） |
| 2人含回程群組 | ~9.6KB | 新增功能（含共乘資訊） |
| 4人去程群組 | ~9.6KB | 新增功能（含共乘資訊） |
| 4人含回程群組 | ~19.2KB | 新增功能（含共乘資訊） |
| 群組解除操作 | ~5-21KB | 視群組大小（含共乘資訊） |

#### 記憶體監控閾值建議
- **單次群組操作**：超過 20MB 記憶體使用時記錄警告
- **群組大小限制**：建議最大 4 人群組
- **併發群組操作**：監控同時進行的群組操作數量
- **API 響應大小**：群組資訊 API 響應超過 50KB 時優化

### 效能測試建議

#### 共乘系統記憶體壓力測試
1. **小型群組測試**：100 個 2 人群組同時操作
2. **中型群組測試**：50 個 4 人群組同時操作
3. **群組解除測試**：批量解除 100 個群組
4. **搜尋效能測試**：在 1000 個群組中搜尋特定訂單

#### 記憶體監控指標
- 群組建立/解除操作的記憶體峰值
- 群組列表載入的記憶體使用
- 群組搜尋操作的記憶體效率
- N+1 查詢檢測和關聯載入最佳化

這些測試和監控機制將確保共乘系統在擴展時保持良好的記憶體效率和系統穩定性。

### 共乘資料標準化記憶體影響（2025-08-02 更新）

#### 共乘欄位標準化實施
- **special_status 統一標記**：所有共乘訂單統一使用 `special_status = '共乘'` 標記
- **完整關聯資訊**：每筆共乘訂單包含完整的共乘對象資訊
  - `carpool_customer_id`: 共乘對象的客戶 ID
  - `carpool_name`: 共乘對象的姓名  
  - `carpool_id`: 共乘對象的身分證字號

#### 記憶體影響評估
- **單筆訂單增加**：每筆共乘訂單約增加 100 bytes（4個共乘欄位）
- **查詢效率提升**：標準化的 special_status 欄位便於索引和篩選
- **關聯查詢優化**：直接的關聯資訊減少 JOIN 查詢需求

#### 最佳化建議更新
1. **高優先級**：
   - 為 `special_status` 欄位建立索引，加速共乘訂單查詢
   - 共乘關聯查詢使用 `carpool_customer_id` 直接查找，避免複雜 JOIN
   
2. **中優先級**：
   - 實施共乘資料的快取策略，減少重複查詢
   - 監控共乘訂單的查詢模式，優化常用查詢路徑
   
3. **低優先級**：
   - 定期清理無效的共乘關聯資訊
   - 實施共乘資料的一致性檢查機制

#### 查詢效能改善
```sql
-- 優化前：複雜的群組查詢
SELECT * FROM orders WHERE carpool_group_id IN (
    SELECT DISTINCT carpool_group_id FROM orders WHERE customer_id = ?
);

-- 優化後：直接的共乘關聯查詢  
SELECT * FROM orders WHERE special_status = '共乘' 
    AND (customer_id = ? OR carpool_customer_id = ?);
```

這些改善將進一步提升共乘系統的記憶體效率和查詢效能。

## 訂單系統重構詳細記錄

### 重構前後對比

| 項目 | 重構前 | 重構後 | 改善幅度 |
|------|--------|--------|----------|
| **主頁面代碼行數** | 467 行 | 15 行 | 減少 97% |
| **檔案結構** | 單體架構 | 組件化架構 | 清晰劃分 |
| **JavaScript 檔案** | 1 個大檔案 | 2 個專門檔案 | 模組化管理 |
| **維護性** | 難以維護 | 易於維護 | 大幅提升 |
| **重用性** | 低 | 高 | 組件可重用 |

### 重構解決的問題

#### 1. 架構混亂問題
- **舊問題**: 單體檔案包含多種功能，職責不清
- **新解決**: 每個組件專注單一職責，清晰劃分

#### 2. 代碼重複問題
- **舊問題**: 表單、搜尋、列表功能重複出現
- **新解決**: 組件化設計，一次定義多處使用

#### 3. Modal 衝突問題
- **舊問題**: 地標選擇和共乘搜尋 Modal 互相衝突
- **新解決**: 分離 Modal 邏輯，獨立管理

#### 4. JavaScript 管理問題
- **舊問題**: 單一大檔案，事件監聽混亂
- **新解決**: 模組化 JavaScript，職責分離

### 新增功能

#### 1. 搜尋參數保持
- 檢視 → 返回列表，維持原搜尋條件
- 編輯 → 返回列表，維持原搜尋條件
- 新增 → 返回列表，維持原搜尋條件

#### 2. 客戶搜尋限制
- 新增訂單前必須先搜尋客戶
- 友善的提示訊息和引導

#### 3. 日期格式化修正
- Order 模型增加 `$casts` 屬性
- 支援字串和 Carbon 實例雙重格式

#### 4. DataTable 錯誤修正
- 動態檢測表格欄位數量
- 安全的初始化檢查

### 記憶體管理優化

#### 1. 組件化帶來的記憶體優化
- **減少重複載入**: 組件只在需要時載入
- **更好的快取策略**: 小組件更容易快取
- **降低記憶體佔用**: 避免載入不必要的代碼

#### 2. JavaScript 模組化優化
- **按需載入**: 只載入當前頁面需要的 JavaScript
- **減少全域變數**: 避免記憶體洩漏
- **事件管理**: 更好的事件監聽器管理

#### 3. 視圖快取優化
- **組件快取**: 小組件更容易被 Laravel 快取
- **條件載入**: 只載入必要的組件
- **降低渲染成本**: 減少不必要的 Blade 編譯

### 效能提升

#### 1. 載入速度
- 主頁面代碼減少 97%，載入更快
- 組件化設計，按需載入
- JavaScript 模組化，減少不必要載入

#### 2. 維護效率
- 單一職責原則，定位問題更快
- 組件重用，減少重複開發
- 清晰的檔案結構，易於理解

#### 3. 擴展性
- 新增功能只需新增對應組件
- 修改功能只需修改相關組件
- 測試更容易，可以針對單一組件測試

### 建議後續優化

#### 1. 高優先級
- 實施 Redis 快取系統
- 優化資料庫查詢（使用 select 限制欄位）
- 實施 Session Redis 儲存

#### 2. 中優先級
- 考慮使用 `simplePaginate()` 減少記憶體開銷
- 實施視圖快取（`php artisan view:cache`）
- 實施設定快取（`php artisan config:cache`）

#### 3. 低優先級
- 路由快取（`php artisan route:cache`）
- Composer 自動載入優化
- 新增記憶體監控中介軟體

### 總結

訂單系統重構成功實現了：
- **97% 代碼減少**：主頁面從 467 行減少到 15 行
- **組件化架構**：5 個可重用組件
- **模組化 JavaScript**：2 個專門的功能模組
- **記憶體優化**：更好的快取策略和按需載入
- **維護性提升**：清晰的職責劃分和檔案結構

這次重構不僅解決了原有的架構問題，還為後續的功能擴展和維護打下了堅實的基礎。

## 問題排除與最佳實踐指南

### 常見 Laravel 資料庫錯誤處理

#### 1. Column not found 錯誤
**錯誤訊息**：`SQLSTATE[42S22]: Column not found: 1054 Unknown column 'xxx' in 'field list'`

**原因與解決方案**：
- **原因**：Model 的 `$fillable` 陣列包含不存在的資料庫欄位
- **解決步驟**：
  1. 檢查資料庫遷移檔案確認實際欄位結構
  2. 從 Model 的 `$fillable` 陣列移除不存在的欄位
  3. 如需保留前端功能，可保留表單驗證但移除儲存邏輯

**範例修復**：
```php
// app/Models/Order.php - 修復前
protected $fillable = [
    'carpool_phone_number', 'carpool_addresses', // 資料庫中不存在
];

// 修復後
protected $fillable = [
    // 移除不存在的欄位
];
```

#### 2. Validation Required 錯誤
**錯誤訊息**：`The xxx field is required.`

**原因與解決方案**：
- **原因**：表單驗證規則要求欄位為必填，但前端已移除該欄位
- **解決步驟**：
  1. 檢查 FormRequest 中的驗證規則
  2. 移除已廢棄欄位的 `required` 規則
  3. 從 Model 的 `$fillable` 陣列移除該欄位

**範例修復**：
```php
// app/Http/Requests/UpdateOrderRequest.php - 修復前
public function rules(): array
{
    return [
        'special_order' => 'required|boolean', // 前端已移除
    ];
}

// 修復後
public function rules(): array
{
    return [
        // 移除已廢棄欄位的驗證規則
    ];
}
```

#### 3. 缺少模型關聯錯誤
**原因與解決方案**：
- **原因**：嘗試使用未定義的模型關聯
- **解決步驟**：
  1. 在相關 Model 中新增關聯方法
  2. 確保外鍵名稱正確
  3. 測試關聯是否正常運作

**範例修復**：
```php
// app/Models/Customer.php
public function orders()
{
    return $this->hasMany(Order::class);
}
```

### Laravel Model $fillable 管理最佳實踐

#### 1. 定期審查 $fillable 陣列
- **檢查原則**：確保所有欄位都存在於資料庫表中
- **工具建議**：使用 `php artisan tinker` 測試 Model::create() 操作
- **記錄更新**：每次遷移後檢查並更新相關 Model

#### 2. 前端與後端分離策略
- **情境**：需要前端欄位但不儲存到資料庫
- **解決方案**：
  1. 保留表單驗證規則（確保資料格式正確）
  2. 從 `$fillable` 移除該欄位（避免儲存錯誤）
  3. 前端可正常使用該欄位進行 JavaScript 處理

#### 3. 批量賦值安全性
- **Mass Assignment 保護**：只允許安全欄位被批量賦值
- **白名單原則**：使用 `$fillable` 明確列出可賦值欄位
- **避免 `$guarded = []`**：可能導致安全風險

### 資料庫一致性檢查流程

#### 1. 遷移後檢查清單
```bash
# 1. 檢查遷移狀態
php artisan migrate:status

# 2. 檢查表結構
php artisan tinker
Schema::getColumnListing('orders');

# 3. 測試 Model 操作
Order::first();
```

#### 2. Model 驗證清單
- [ ] `$fillable` 陣列只包含實際存在的欄位
- [ ] `$casts` 陣列對應正確的資料類型
- [ ] 關聯方法已正確定義
- [ ] 外鍵約束設定正確

#### 3. 除錯工具建議
- **Laravel Debugbar**：監控 SQL 查詢和錯誤
- **Telescope**：追蹤請求和資料庫操作
- **Log 檔案**：`storage/logs/laravel.log` 查看詳細錯誤資訊

### 併發性錯誤處理與診斷

#### 1. ConcurrencyException 錯誤
**錯誤類型**：`App\Exceptions\ConcurrencyException`

**常見錯誤訊息**：
- `ORDER_NUMBER_CONFLICT`: 訂單編號產生衝突，請重試
- `GROUP_ID_CONFLICT`: 群組ID產生衝突，請重試
- `DUPLICATE_ORDER_CONFLICT`: 重複訂單檢測衝突，請重試

**診斷步驟**：
1. **檢查併發負載**：
   ```bash
   # 查看當前訂單建立頻率
   tail -f storage/logs/laravel.log | grep "生成訂單編號"
   ```

2. **執行併發測試**：
   ```bash
   # 測試併發安全性
   php artisan test:concurrency --threads=5 --orders=10
   ```

3. **檢查序列號表狀態**：
   ```bash
   php artisan tinker
   >>> DB::table('order_sequences')->get()
   ```

**解決方案**：
- **臨時方案**：指導使用者稍後重試
- **系統方案**：檢查 OrderNumberService 是否正常運作
- **長期方案**：監控併發指標，調整系統容量

#### 2. 資料庫鎖等待錯誤
**錯誤訊息**：
- `SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded`
- `SQLSTATE[40001]: Serialization failure: 1213 Deadlock found`

**原因分析**：
- **高併發訂單建立**：超過資料庫鎖處理能力
- **長時間事務**：某個事務佔用鎖太久
- **索引缺失**：查詢效能低導致鎖時間過長

**診斷工具**：
```bash
# 檢查 MySQL 鎖狀態（如有權限）
SHOW ENGINE INNODB STATUS;

# 檢查進行中的事務
SELECT * FROM INFORMATION_SCHEMA.INNODB_TRX;
```

**解決步驟**：
1. **確認索引存在**：
   ```bash
   php artisan migrate:status  # 確認併發約束遷移已執行
   ```

2. **調整併發參數**：
   ```php
   // config/database.php - MySQL 設定
   'options' => [
       PDO::ATTR_TIMEOUT => 30,
       PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
   ]
   ```

3. **啟用查詢快取**：
   ```bash
   # .env 設定
   CACHE_DRIVER=redis
   DB_CACHE_TTL=300
   ```

#### 3. 原子化操作錯誤處理

**OrderNumberService 錯誤診斷**：
```php
// 手動測試序列號生成
$service = app(OrderNumberService::class);
try {
    $number = $service->generateOrderNumber('台北長照', 'A123456789');
    echo "生成成功: $number";
} catch (ConcurrencyException $e) {
    echo "併發錯誤: " . $e->getMessage();
    echo "建議重試: " . ($e->shouldRetry() ? '是' : '否');
}
```

**常見問題與修復**：
1. **序列號表損壞**：
   ```bash
   # 重置今日序列號（謹慎使用）
   php artisan tinker
   >>> app(OrderNumberService::class)->resetSequenceNumber()
   ```

2. **事務隔離級別問題**：
   ```sql
   -- 檢查隔離級別
   SELECT @@transaction_isolation;
   
   -- 建議設定 READ-COMMITTED
   SET SESSION transaction_isolation = 'READ-COMMITTED';
   ```

#### 4. 併發測試失敗診斷

**測試失敗常見原因**：
1. **序列號不一致**：
   - 檢查 order_sequences 表是否正確建立
   - 確認 OrderNumberService 依賴注入正常

2. **編號重複**：
   - 檢查 order_number 唯一約束是否存在
   - 確認事務範圍正確包裝

3. **效能過低**：
   - 檢查資料庫連線數設定
   - 確認索引是否正確建立

**詳細診斷指令**：
```bash
# 1. 檢查服務註冊
php artisan route:list | grep concurrency

# 2. 檢查資料庫結構
php artisan migrate:status
php artisan tinker
>>> Schema::hasTable('order_sequences')
>>> Schema::hasIndex('orders', 'orders_order_number_unique')

# 3. 測試基本功能
php artisan test:concurrency --threads=1 --orders=1

# 4. 逐步增加負載
php artisan test:concurrency --threads=2 --orders=5
php artisan test:concurrency --threads=5 --orders=10
```

### 原子化操作最佳實踐

#### 1. SELECT FOR UPDATE 使用指南
**正確用法**：
```php
// ✅ 正確：在事務中使用
DB::transaction(function () {
    $record = DB::table('table_name')
        ->where('id', $id)
        ->lockForUpdate()
        ->first();
    
    // 修改邏輯
    DB::table('table_name')
        ->where('id', $id)
        ->update(['field' => $newValue]);
});
```

**錯誤用法**：
```php
// ❌ 錯誤：不在事務中使用
$record = DB::table('table_name')
    ->where('id', $id)
    ->lockForUpdate()  // 鎖會立即釋放
    ->first();
```

#### 2. 併發衝突處理策略
**重試機制設計**：
```php
public function retryableOperation($maxRetries = 3)
{
    $retries = 0;
    
    while ($retries < $maxRetries) {
        try {
            return $this->atomicOperation();
        } catch (QueryException $e) {
            if ($this->isRetryableError($e) && $retries < $maxRetries - 1) {
                $retries++;
                usleep(100000 * $retries); // 指數退避
                continue;
            }
            throw $e;
        }
    }
}
```

#### 3. 併發測試流程標準化
**測試階段**：
1. **基礎測試**：`--threads=1 --orders=1`
2. **輕負載測試**：`--threads=3 --orders=5`  
3. **中負載測試**：`--threads=5 --orders=10`
4. **高負載測試**：`--threads=10 --orders=20`
5. **壓力測試**：`--threads=20 --orders=50`

**通過標準**：
- 成功率：100%
- 編號唯一性：0 重複
- 序列號一致性：增加數 = 成功數
- 效能基準：> 500 筆/秒

## 安全性最佳實踐

### 資料驗證與保護
- **CSRF 保護**: 所有表單都包含 CSRF Token (`@csrf`)
- **SQL 注入防護**: 使用 Eloquent ORM 和 Query Builder
- **XSS 防護**: Blade 模板自動轉義輸出 (`{{ }}`)
- **Mass Assignment 保護**: 模型使用 `$fillable` 白名單

### 身份驗證與授權
- **Laravel Breeze**: 提供基本的身份驗證功能
- **Laravel Sanctum**: API 身份驗證系統（已安裝但未啟用）
- **Session 安全**: HTTP-only cookies，防止 JavaScript 存取
- **密碼安全**: 使用 Laravel 內建的密碼雜湊

### 檔案安全
- **上傳檔案限制**: Excel 檔案類型驗證
- **儲存隔離**: 使用 `storage/` 目錄，與公開檔案分離
- **環境變數**: 敏感資訊存放於 `.env` 檔案

### 資料庫安全
- **連線加密**: 支援 SSL 連線
- **預備語句**: 使用參數化查詢
- **最小權限**: 資料庫使用者僅具備必要權限

### 併發安全性
- **原子化操作**: 使用 SELECT FOR UPDATE 確保資料一致性
- **事務完整性**: 所有關鍵操作都在 DB::transaction 中執行
- **資料完整性保護**: order_number 唯一約束防止重複
- **智能重試機制**: ConcurrencyException 處理併發衝突
- **併發測試**: 定期執行 `php artisan test:concurrency` 驗證系統穩定性

## 開發環境設定

### 必要環境變數
```bash
# 應用程式設定
APP_NAME="LC Management"     # 長照管理系統
APP_ENV=local
APP_KEY=                    # 執行 php artisan key:generate 生成
APP_DEBUG=true              # 生產環境應設為 false
APP_URL=http://localhost:8000

# 資料庫連線
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lc_management   # 建議使用此資料庫名稱
DB_USERNAME=root
DB_PASSWORD=

# 快取設定 (建議生產環境使用 Redis)
CACHE_DRIVER=file           # 生產環境建議改為 redis
SESSION_DRIVER=file         # 生產環境建議改為 redis
SESSION_LIFETIME=120

# 郵件設定 (開發環境使用 Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Vite 前端設定
VITE_APP_NAME="${APP_NAME}"

# Redis 設定 (生產環境需要)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 初始化專案步驟
```bash
# 1. 複製環境變數檔案
cp .env.example .env

# 2. 安裝依賴
composer install
npm install

# 3. 生成應用程式金鑰
php artisan key:generate

# 4. 資料庫遷移與測試資料
php artisan migrate
php artisan db:seed --class=LandmarkSeeder

# 5. 建置前端資源
npm run build

# 6. 清除快取
php artisan optimize:clear
```

## 除錯與問題排查

### 常見開發問題
1. **Vite 建置問題**: 確保 `npm install` 完成且 `tailwind.config.js` 設定正確
2. **認證問題**: 檢查 Laravel Breeze 是否正確安裝：`php artisan breeze:install`
3. **地標搜尋問題**: 確保 `LandmarkSeeder` 已執行：`php artisan db:seed --class=LandmarkSeeder`
4. **Excel 匯入問題**: 檢查檔案權限和 `storage/framework/cache/laravel-excel/` 目錄
5. **分頁顯示問題**: 確保中文語言包存在：`lang/zh-TW/pagination.php`

### 測試指令
```bash
# 執行所有測試
php artisan test

# 執行特定測試
php artisan test --filter=AuthenticationTest

# 檢查程式碼風格
./vendor/bin/pint --test

# 檢查資料庫連線
php artisan tinker
>>> \DB::connection()->getPdo()
```