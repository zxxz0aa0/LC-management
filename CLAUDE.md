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
5. 開始寫程式或修改程式前先做一個to do

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

## 核心功能與架構

### 訂單管理系統
- **多種建立模式**: 單日訂單、手動多日、週期性批量建立
- **併發安全性**: 使用 OrderNumberService 確保訂單編號唯一性
- **共乘功能**: CarpoolGroupService 管理共乘群組，支援主訂單代表制
- **重複檢查**: 前後端雙重檢查防止重複訂單
- **歷史訂單**: 快速選擇客戶歷史訂單進行填入
- **組件化設計**: 5個可重用組件，主頁面代碼減少97%

#### 核心服務
- **OrderNumberService**: 原子化訂單編號生成，使用 SELECT FOR UPDATE 確保併發安全
- **CarpoolGroupService**: 共乘群組管理，支援建立、解除、狀態同步
- **BatchOrderService**: 批量訂單建立，支援手動多日和週期性模式

### 客戶管理系統
- **JSON 欄位**: 支援多筆電話和地址儲存
- **匯入匯出**: Excel 批次處理，含錯誤處理和資料驗證
- **歷史追蹤**: CustomerEvent 模型記錄客戶操作歷史
- **智能搜尋**: 支援姓名、電話、身分證搜尋

### 駕駛管理系統
- **完整資訊**: 13個駕駛相關欄位管理
- **匯入匯出**: 支援 Excel 匯入匯出，含狀態智能對照
- **狀態管理**: 在職/離職/黑名單狀態追蹤
- **車輛資訊**: 車牌號碼、隊編等資訊管理

### 地標管理系統
- **智能搜尋**: 輸入關鍵字+`*`觸發地標搜尋
- **分類管理**: 醫療、交通、教育、政府、商業、一般
- **使用統計**: 記錄地標使用次數，熱門排序
- **訂單整合**: 上下車地址支援地標快速選擇
- **匯入匯出**: Excel 批次處理，含座標驗證

### 資料庫架構
- **Customer**: 客戶管理，支援 JSON 欄位儲存多筆電話和地址
- **Order**: 訂單管理，具備智能編號生成和客戶快照功能，包含共乘群組欄位
- **Driver**: 司機管理，包含車輛資訊和服務能力
- **Landmark**: 地標管理，支援地址快速選擇和使用統計
- **CustomerEvent**: 客戶事件追蹤系統

### 前端架構
- **Vite**: 現代前端建置工具，提供快速熱更新
- **Tailwind CSS**: 實用性優先的 CSS 框架
- **Alpine.js**: 輕量級 JavaScript 框架（透過 CDN 引入）
- **AdminLTE 3.2**: 主要管理介面框架
- **Bootstrap 5.3** + **DataTables**: 表格展示
- **組件化設計**: 訂單系統採用組件化架構，提升維護性

## 記憶體管理要點

### 核心原則
- **分頁機制**: 所有列表頁面使用分頁限制記憶體佔用
- **查詢最佳化**: 使用 select() 限制載入欄位，避免 N+1 查詢
- **快取策略**: 建議使用 Redis 快取提升效能
- **批次處理**: 大量資料處理使用 chunk() 方法

### 重要建議
1. **高優先級**:
   - 啟用 Redis 快取 (`CACHE_DRIVER=redis`)
   - 地標/駕駛匯出使用分塊處理避免記憶體不足
   - 重複訂單檢查實施 debounce 機制

2. **中優先級**:
   - 使用 `simplePaginate()` 減少總筆數計算開銷
   - 實施視圖快取 (`php artisan view:cache`)
   - 最佳化匯入重複性檢查，預先載入現有資料

3. **監控指標**:
   - 匯入匯出操作記憶體峰值
   - 併發訂單建立記憶體使用
   - 大量查詢時的記憶體效率

### 共乘系統記憶體影響
- **記憶體增加**: 單一訂單約增加20%（新增共乘欄位）
- **群組處理**: 2人群組約4.8KB，4人群組約9.6KB
- **最佳化策略**: 智能顯示策略，瀏覽模式記憶體佔用減少50%

## 安全性與併發控制

### 併發安全性
- **原子化操作**: 使用 SELECT FOR UPDATE 確保資料一致性
- **事務完整性**: 所有關鍵操作都在 DB::transaction 中執行
- **資料完整性保護**: order_number 唯一約束防止重複
- **智能重試機制**: ConcurrencyException 處理併發衝突
- **併發測試**: 定期執行 `php artisan test:concurrency` 驗證系統穩定性

### 資料安全
- **CSRF 保護**: 所有表單都包含 CSRF Token
- **SQL 注入防護**: 使用 Eloquent ORM 和 Query Builder
- **XSS 防護**: Blade 模板自動轉義輸出
- **Mass Assignment 保護**: 模型使用 `$fillable` 白名單
- **身份驗證**: Laravel Breeze 提供基本身份驗證

## 開發環境設定

### 必要環境變數
```bash
# 應用程式設定
APP_NAME="LC Management"
APP_ENV=local
APP_KEY=                    # 執行 php artisan key:generate 生成
APP_DEBUG=true              # 生產環境應設為 false
APP_URL=http://localhost:8000

# 資料庫連線
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lc_management
DB_USERNAME=root
DB_PASSWORD=

# 快取設定 (建議生產環境使用 Redis)
CACHE_DRIVER=file           # 生產環境建議改为 redis
SESSION_DRIVER=file         # 生產環境建議改为 redis
SESSION_LIFETIME=120

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

### 併發錯誤處理
1. **ConcurrencyException**: 訂單編號衝突，建議重試
2. **資料庫鎖等待**: 檢查併發負載，確認索引存在
3. **測試失敗**: 執行 `php artisan test:concurrency` 診斷併發問題

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

## 重要提醒

### Model $fillable 管理
- **定期審查**: 確保 $fillable 陣列只包含實際存在的資料庫欄位
- **前後端分離**: 前端欄位不一定要儲存到資料庫
- **安全性**: 使用白名單原則，避免 Mass Assignment 風險

### 效能最佳化
- **查詢優化**: 使用 `with()` 預載關聯避免 N+1 查詢
- **索引使用**: 為常用查詢欄位建立適當索引
- **快取策略**: 熱門查詢結果使用 Redis 快取
- **分塊處理**: 大量資料操作使用 `chunk()` 方法

### 維護要點
- **代碼風格**: 定期執行 `./vendor/bin/pint` 格式化代碼
- **依賴更新**: 定期檢查並更新 Composer 和 NPM 依賴
- **安全性**: 定期檢查並修復安全漏洞
- **備份策略**: 定期備份資料庫和重要檔案