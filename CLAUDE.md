# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案概述

LC-management 是一個基於 Laravel 10 框架的長照服務管理系統，主要用於客戶、訂單、司機管理和 Excel 匯入匯出功能。

## 個人偏好
1. 使用繁體中文應答
2. 設計新功能前先規劃，並討論完後，有確定再執行
3. 做任何分析、查詢、檢查時使用Claude Opus 4模型，編寫代碼時使用Claude Sonnet 4模型，來降低使用次數成本。

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
php artisan test
./vendor/bin/phpunit

# 程式碼格式化
./vendor/bin/pint

# 清除所有快取
php artisan optimize:clear
```

### 前端建置指令
```bash
# 開發模式
npm run dev

# 生產建置
npm run build

# 依賴安裝
npm install
composer install
```

## 應用程式架構

### 核心業務模型
- **Customer**: 客戶管理，支援 JSON 欄位儲存多筆電話和地址
- **Order**: 訂單管理，具備智能編號生成和客戶快照功能
- **Driver**: 司機管理，包含車輛資訊和服務能力
- **CustomerEvent**: 客戶事件追蹤系統

### 控制器結構
- **CustomerController**: 客戶 CRUD 操作，支援 Excel 匯入匯出
- **OrderController**: 訂單管理，包含複雜篩選和共乘功能
- **DriverController**: 司機管理系統

### 資料庫關係
- Customer 一對多 Orders
- Driver 一對多 Orders (軟約束)
- Customer 一對多 CustomerEvents
- JSON 欄位：Customer 的 phone_number 和 addresses

### 前端架構
- **AdminLTE 3.2**: 主要管理介面框架
- **Bootstrap 5.3** + **DataTables**: 表格展示
- **Alpine.js**: 輕量級 JavaScript 框架
- **Tailwind CSS**: 實用性優先的 CSS 框架

## 關鍵特色功能

### Excel 匯入匯出系統
- 使用 `maatwebsite/excel` 套件處理 Excel 檔案
- 支援客戶資料批次匯入，具備錯誤處理和資料驗證
- JSON 欄位（電話、地址）支援多種格式解析

### 訂單編號生成
- 智能編號系統：`類型代碼 + 身分證末3碼 + 日期時間 + 流水號`
- 客戶資料快照：避免客戶資料異動影響歷史訂單
- 支援共乘功能和地址自動解析

### 搜尋和篩選
- 支援 JSON 欄位搜尋（使用 `whereJsonContains`）
- AJAX 即時搜尋（共乘對象搜尋）
- 複雜的日期區間和關鍵字篩選

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

### 3. 低優先級建議

1. **路由快取**
   - 生產環境執行 `php artisan route:cache`

2. **Composer 最佳化**
   - 執行 `composer dump-autoload --optimize`

3. **記憶體監控**
   - 新增記憶體使用監控中介軟體
   - 記錄高記憶體使用的請求

## 潛在記憶體問題

### 1. Excel 匯入匯出
- 大檔案處理可能導致記憶體不足
- 建議實作分塊處理或使用佇列處理

### 2. 大量資料查詢
- `OrderController` 中的複雜查詢可能消耗大量記憶體
- 建議新增索引和使用 `chunk()` 方法

### 3. Session 檔案堆積
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

## 總結

此系統採用 Laravel 標準的記憶體管理架構，整體設計合理。主要記憶體管理透過檔案快取、Session 管理和資料庫連線池實現。建議優先實施 Redis 快取和查詢最佳化，以提升系統整體效能和記憶體使用效率。