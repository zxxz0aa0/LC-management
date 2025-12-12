# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 專案概述

LC-management is a long-term care service management system based on the Laravel 10 framework. It primarily handles customer, order, driver, and location management, manual scheduling, data analysis, and Excel import/export functionality. The system features production-grade concurrency security and can safely handle simultaneous operations by multiple users.

### 關鍵技術堆疊
- **後端**: Laravel 10.x + PHP 8.1+
- **前端**: Vite + Tailwind CSS + Alpine.js + AdminLTE 3.2
- **資料庫**: MySQL with JSON column support + 併發安全性約束
- **認證**: Laravel Breeze
- **Excel 處理**: maatwebsite/excel 3.1+
- **併發控制**: SELECT FOR UPDATE + 原子化序列號 + UUID 群組ID
- **開發工具**: Laravel Pint (程式碼格式化) + IDE Helper

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

## Claude 回答方式偏好
1. response in Traditional Chinese.
2. Before designing new features, plan ahead, discuss them thoroughly, and only execute after confirmation.
3. Focus on code security and quality
4. Create a todo list before writing or modifying code
5. When adding new code, add a brief Chinese comment at the beginning of each code segment.

# Token 使用優化規則

## 程式碼回應規則
- 只顯示**需要修改或新增的部分**，不要重複顯示整個檔案
- 如果程式碼超過 50 行，請**分段說明**，不要一次全部貼上
- 使用 `// ... 其他程式碼保持不變` 來省略不需要改動的部分

## 回答方式
- 直接回答問題，**避免過多的開場白或總結**
- 如果我只問一個問題，請**只回答那個問題**
- 範例程式碼請**精簡到能說明概念即可**

## 檔案處理
- 讀取檔案時，如果我沒特別要求，**只讀取只讀取我詢問的功能相關程式碼**
- 當詢問時不知道或不確定程式碼在哪邊時，先提出詢問在哪個頁面裡
- 不要自動讀取整個專案的所有檔案

## 什麼時候可以詳細說明
- 我明確要求「詳細解釋」時
- 我說「我不太懂」時
- 涉及重要概念需要完整說明時

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
php artisan optimize:clear          # 清除所有快取

# 測試執行
php artisan test                    # Laravel 測試套件
php artisan test --parallel         # 平行執行測試
php artisan test --filter=CustomerImportTest  # 特定測試

# 程式碼格式化
./vendor/bin/pint
./vendor/bin/pint --test            # 檢查程式碼風格

# IDE Helper 生成
php artisan ide-helper:generate     # 生成 Helper 檔案
php artisan ide-helper:models       # 生成 Model 註解
php artisan ide-helper:meta         # 生成 Meta 檔案

# 併發安全性測試
php artisan test:concurrency                              # 預設併發測試 (5執行緒×10訂單)
php artisan test:concurrency --threads=10 --orders=20     # 自訂併發測試參數

# 資料庫備份管理
php artisan db:backup                       # 建立資料庫備份
php artisan db:restore {filename}           # 還原資料庫
php artisan db:backup:list                  # 列出所有備份檔案
php artisan db:backup:monitor               # 監控備份狀態
php artisan db:backup:verify {filename}     # 驗證備份檔案

# 排趟記錄管理
php artisan dispatch:clean-old              # 清除舊排趟記錄
```

### 前端建置指令
```bash
# 開發模式 (使用 Vite)
npm run dev
npm run dev -- --host               # 允許外部存取

# 生產建置
npm run build

# 依賴安裝
npm install
composer install
```

### 路由架構概覽
```
# 主要頁面
/dashboard                          # 主控台頁面（重定向至訂單管理）
/customers                          # 客戶管理 (CRUD + 匯入匯出)
/orders                             # 訂單管理 (CRUD + 複雜搜尋)
/admin/drivers                      # 駕駛管理 (CRUD + 匯入匯出)
/landmarks                          # 地標管理 (CRUD + 搜尋 API)
/carpool-groups                     # 共乘群組管理
/manual-dispatch                    # 人工排趟管理
/dispatch-records                   # 排趟記錄查詢
/statistics/geography               # 地理分析報表
/statistics/time-analysis           # 時間分析報表
/statistics/customer-service        # 客戶服務分析報表
/profile                            # 使用者資料管理

# 訂單管理 API
POST /orders/batch                              # 批量建立訂單
POST /orders/batch-update                       # 批量更新訂單
POST /orders/batch-edit                         # 批量編輯訂單
POST /orders/check-duplicate                    # 重複訂單檢查
POST /orders/check-date-pickup-duplicate        # 日期與上車時間重複檢查
POST /orders/check-batch-duplicate              # 批量重複檢查
POST /orders/check-back-time                    # 檢查回程時間
POST /orders/{order}/assign-driver              # 指派司機給訂單
PATCH /orders/{order}/cancel                    # 取消訂單
PATCH /orders/{order}/update-match-time         # 更新用車時間
GET  /orders/export                             # 完整格式訂單匯出
GET  /orders/export-simple                      # 簡化格式訂單匯出（14欄位）
GET  /orders/export-simple-by-date              # 依建立時間範圍匯出
POST /orders/import                             # 訂單匯入

# 客戶管理 API
GET  /customers/{id}/history-orders             # 客戶歷史訂單
POST /customers/batch-delete                    # 批量刪除客戶
PATCH /customers/{customer}/note                # 更新客戶備註
PATCH /customers/{customer}/field               # 更新客戶欄位
GET  /customers/export                          # 客戶匯出
POST /customers/import                          # 客戶匯入

# 地標管理 API
GET  /landmarks-search                          # 地標搜尋 API
GET  /landmarks-popular                         # 熱門地標
POST /landmarks-by-ids                          # 依 ID 批量取得地標
POST /landmarks-usage                           # 更新地標使用統計
POST /landmarks/batch-destroy                   # 批量刪除地標
POST /landmarks/batch-toggle                    # 批量啟用/停用地標

# 共乘群組 API
POST /carpool-groups/{id}/assign-driver         # 指派司機給共乘群組
POST /carpool-groups/{id}/cancel                # 取消共乘群組
POST /carpool-groups/{id}/dissolve              # 解除共乘群組
POST /carpool-groups/{id}/update-status         # 更新共乘群組狀態
POST /carpool-groups/batch-action               # 批量操作共乘群組

# 人工排趟管理 API
POST /manual-dispatch/add                       # 加入排趟列表
DELETE /manual-dispatch/remove                  # 從排趟列表移除
POST /manual-dispatch/clear                     # 清空排趟列表
POST /manual-dispatch/batch-assign              # 批量指派司機

# 排趟記錄 API
GET  /dispatch-records/{id}                     # 排趟記錄詳情
PATCH /dispatch-records/{id}/entry-status       # 更新登打狀態

# 數據分析 API
GET  /statistics/api/geography                  # 地理分析數據
GET  /statistics/api/time-analysis              # 時間分析數據
GET  /statistics/api/customer-service           # 客戶服務分析數據
GET  /statistics/export/geography               # 匯出地理分析報表
GET  /statistics/export/time-analysis           # 匯出時間分析報表
GET  /statistics/export/customer-service        # 匯出客戶服務報表
```

## 核心功能與架構

### 訂單管理系統
- **訂單來源類型**: 新北長照、台北長照
- **訂單狀態**: open（進行中）、completed（已完成）、cancelled（已取消）、no_send（未發送）、regular_sedans（一般轎車）、no_car（沒車）
- **多種建立模式**: 單日訂單、手動多日、週期性批量建立（支援最多50筆）
- **併發安全性**: 使用 OrderNumberService 確保訂單編號唯一性
- **共乘功能**: CarpoolGroupService 管理共乘群組，支援主訂單代表制
- **重複檢查**: 前後端雙重檢查防止重複訂單（日期+上車時間+回程時間檢測）
- **批量編輯**: 支援批量編輯訂單欄位，自動同步共乘群組
- **取消機制**: 訂單可標記為取消狀態，含取消原因記錄
- **歷史訂單**: 快速選擇客戶歷史訂單進行填入
- **司機指派**: 支援單筆和批量指派司機
- **排趟整合**: 訂單可關聯至排趟記錄
- **匯入匯出**: 完整格式、簡化格式、依建立時間範圍匯出

#### 核心服務
- **OrderNumberService**: 原子化訂單編號生成
  - 使用 SELECT FOR UPDATE 確保併發安全
  - 支援重試機制處理併發衝突（最多3次重試）
  - 使用 order_sequences 表管理每日序列號
  - 自動處理 MySQL 死鎖和鎖等待超時錯誤
  - 位置：`app/Services/OrderNumberService.php`

- **CarpoolGroupService**: 共乘群組管理
  - 使用 UUID 生成獨立的群組 ID
  - 支援去程回程獨立群組管理
  - 提供群組狀態同步和司機指派功能
  - 位置：`app/Services/CarpoolGroupService.php`

- **BatchOrderService**: 批量訂單建立
  - 支援最多50筆訂單的批量建立
  - 內建記憶體管理，分批處理避免記憶體溢出
  - 支援多星期幾選擇的週期性日期生成
  - 位置：`app/Services/BatchOrderService.php`

- **BatchEditService**: 批量編輯訂單
  - 支援選擇性欄位更新（只更新填入的欄位）
  - 共乘群組自動同步
  - 地址驗證與拆分
  - 交易完整性保證
  - 位置：`app/Services/BatchEditService.php`

### 客戶管理系統
- **JSON 欄位**: 支援多筆電話和地址儲存
- **匯入匯出**: Excel 批次處理，含錯誤處理和資料驗證
- **歷史追蹤**: CustomerEvent 模型記錄客戶操作歷史
- **智能搜尋**: 支援姓名、電話、身分證搜尋

#### 核心服務
- **CustomerImportService**: 客戶資料匯入
  - 智能欄位對照和資料驗證
  - 重複檢查（姓名、身分證、電話）
  - 錯誤回報與部分成功處理
  - 位置：`app/Services/CustomerImportService.php`

### 駕駛管理系統
- **完整資訊**: 13個駕駛相關欄位管理
- **匯入匯出**: 支援 Excel 匯入匯出，含狀態智能對照
- **狀態管理**: 在職/離職/黑名單狀態追蹤
- **車輛資訊**: 車牌號碼、隊編等資訊管理
- **隊編搜尋**: 支援依隊編快速搜尋司機

### 地標管理系統
- **智能搜尋**: 輸入關鍵字+`*`觸發地標搜尋
- **分類管理**: 醫療、交通、教育、政府、商業、一般
- **使用統計**: 記錄地標使用次數，熱門排序
- **訂單整合**: 上下車地址支援地標快速選擇
- **匯入匯出**: Excel 批次處理，含座標驗證
- **批量操作**: 支援批量刪除、啟用/停用地標

### 人工排趟管理系統
- **排趟列表**: Session 管理待派遣訂單列表
- **搜尋篩選**: 依日期範圍、關鍵字、隊編搜尋訂單
- **批量指派**: 一次指派多筆訂單給司機
- **共乘支援**: 正確處理主訂單與成員訂單
- **排趟記錄**: 自動建立排趟記錄供後續查詢
- **司機資訊**: 顯示司機姓名、隊編、車牌等資訊

### 排趟記錄管理系統
- **歷史查詢**: 查詢過往排趟記錄
- **詳細資訊**: 顯示排趟批次、司機、訂單明細
- **登打狀態**: 追蹤訂單登打進度（未登打、部分登打、全部登打）
- **狀態更新**: 可更新登打狀態並記錄更新人員
- **篩選功能**: 依司機、日期範圍篩選記錄

### 數據分析系統
#### 地理分析
- **區域統計**: 依縣市、區域統計訂單分佈
- **圖表展示**: 視覺化呈現訂單地理分佈
- **報表匯出**: 匯出 Excel 格式地理分析報表
- 服務位置：`app/Services/GeographyAnalysisService.php`

#### 時間分析
- **用車時間分析**: 統計訂單時間分佈
- **尖峰時段識別**: 分析高峰時段
- **報表匯出**: 匯出 Excel 格式時間分析報表
- 服務位置：`app/Services/TimeAnalysisService.php`

#### 客戶服務分析
- **人員績效**: 統計各客服人員建立的訂單數量
- **時間範圍篩選**: 依日期範圍分析績效
- **報表匯出**: 匯出 Excel 格式客服分析報表
- 服務位置：`app/Services/CustomerServiceAnalysisService.php`

### 資料庫備份系統
- **自動備份**: 定時備份資料庫
- **手動備份**: 支援手動觸發備份
- **備份還原**: 還原指定的備份檔案
- **備份驗證**: 驗證備份檔案完整性
- **監控管理**: 監控備份狀態和磁碟空間
- **舊記錄清理**: 自動清理過期的排趟記錄

### 輔助服務
- **DateTimeParser**: 時間格式解析，支援多種日期時間格式（`app/Services/DateTimeParser.php`）
- **TaiwanAddressResolver**: 台灣地址解析，自動拆分縣市區域（`app/Services/TaiwanAddressResolver.php`）
- **AddressValidationService**: 台灣地址格式驗證（`app/Services/AddressValidationService.php`）
- **ExcelFieldMapper**: Excel 欄位對照，統一處理匯入欄位名稱（`app/Services/ExcelFieldMapper.php`）

### 資料庫架構
主要資料表位於 `database/migrations/` 目錄：

- **users**: 使用者認證系統（Laravel Breeze）

- **customers**: 客戶管理
  - 支援 JSON 欄位儲存多筆電話和地址
  - 包含姓名、身分證、電話（JSON）、地址（JSON）、縣市長照、服務公司、轉介日期等

- **orders**: 訂單管理
  - 訂單編號、客戶快照、司機快照、上下車地址、共乘群組欄位
  - 狀態欄位：status、special_status、cancellation_reason
  - 共乘欄位：carpool_group_id、is_main_order、carpool_member_count 等
  - 新增欄位：order_type（訂單來源）、dispatch_record_id（排趟記錄）、updated_by（更新人員）、match_time（用車時間）

- **order_sequences**: 訂單編號序列表
  - 使用 date + sequence_number 管理每日訂單編號
  - 確保併發安全的序列號生成

- **drivers**: 司機管理
  - 包含姓名、電話、車牌號碼、隊編、服務類型、狀態等13個欄位

- **landmarks**: 地標管理
  - 包含名稱、分類、地址、經緯度、使用次數、啟用狀態等

- **dispatch_records**: 排趟記錄
  - 包含 batch_id（批次ID）、dispatch_name（排趟名稱）、driver_id、driver_name、driver_fleet_number
  - order_ids（JSON，訂單ID陣列）、order_details（JSON，訂單詳細資訊）
  - dispatch_date（排趟日期）、performed_by（執行人）、performed_at（執行時間）
  - entry_status（登打狀態）、entry_status_updated_by（狀態更新人）

- **customer_events**: 客戶事件追蹤

- **import_sessions**: 匯入工作階段追蹤

- **import_progresses**: 匯入進度記錄

- **jobs**: 佇列任務（用於大量匯入處理）

- **failed_jobs**: 失敗任務記錄

### 前端架構
- **Vite**: 現代前端建置工具，提供快速熱更新
- **Tailwind CSS**: 實用性優先的 CSS 框架（配置檔：`tailwind.config.js`）
- **Alpine.js**: 輕量級 JavaScript 框架（透過 CDN 引入）
- **AdminLTE 3.2**: 主要管理介面框架
- **Bootstrap 5.3** + **DataTables**: 表格展示和互動功能

#### Blade 組件結構
訂單系統的組件位於 `resources/views/orders/components/`：
- `order-form.blade.php` - 訂單基本資訊表單
- `order-table.blade.php` - 訂單列表表格
- `order-table-edit.blade.php` - 訂單批量編輯表格
- `order-table-search.blade.php` - 訂單表格搜尋
- `order-detail.blade.php` - 訂單詳情顯示
- `customer-search.blade.php` - 客戶搜尋
- `history-modal.blade.php` - 歷史訂單 Modal
- `landmark-modal.blade.php` - 地標選擇 Modal
- `edit-field-modal.blade.php` - 欄位編輯 Modal

通用佈局檔案位於 `resources/views/layouts/`：
- `app.blade.php` - 主要應用程式佈局
- `navigation.blade.php` - 導航選單
- `guest.blade.php` - 訪客佈局（登入/註冊頁面）

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

# 匯入除錯（設為 true 可啟用詳細匯入日誌）
IMPORT_DEBUG_LOG=false

# 資料庫連線
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lc_management
DB_USERNAME=root
DB_PASSWORD=

# 快取設定（生產環境建議使用 Redis）
CACHE_DRIVER=file           # 生產環境建議改為 redis
SESSION_DRIVER=file         # 生產環境建議改為 redis
SESSION_LIFETIME=120

# Redis 設定（生產環境需要）
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

## 最近開發活動

### 最近完成的功能（2025年11月-12月）
1. **訂單來源管理** - 新增訂單來源類型（新北長照、台北長照）
2. **數據分析功能** - 新增地理分析、時間分析、客戶服務分析報表
3. **人工排趟系統** - 手動管理訂單派遣和司機指派
4. **排趟記錄管理** - 追蹤派遣歷史和登打狀態
5. **批量編輯優化** - 多編模式記錄更新人員
6. **回程時間檢查** - 新增回程時間重複檢查功能
7. **區域分析報表** - 調整區域分析報表顯示
8. **資料庫備份系統** - 完整的備份還原功能
9. **上下車地址條件判斷** - 優化地址輸入驗證

### 待處理或規劃中的功能
- 更多的快捷時間範圍選項
- 匯出進度指示器（大量資料匯出時）

## 除錯與問題排查

### 常見開發問題
1. **Vite 建置問題**: 確保 `npm install` 完成且 `tailwind.config.js` 設定正確
2. **認證問題**: 檢查 Laravel Breeze 是否正確安裝：`php artisan breeze:install`
3. **地標搜尋問題**: 確保 `LandmarkSeeder` 已執行：`php artisan db:seed --class=LandmarkSeeder`
4. **Excel 匯入問題**: 檢查檔案權限和 `storage/framework/cache/laravel-excel/` 目錄
5. **分頁顯示問題**: 確保中文語言包存在：`lang/zh-TW/pagination.php`

### 併發錯誤處理
1. **ConcurrencyException**: 訂單編號衝突，建議重試
   - 錯誤碼 1213: MySQL 死鎖檢測
   - 錯誤碼 1205: 鎖等待超時
   - 錯誤碼 1062: 重複鍵衝突
2. **資料庫鎖等待**: 檢查併發負載，確認索引存在
3. **測試失敗**: 執行 `php artisan test:concurrency` 診斷併發問題

## 重要提醒

### Model $fillable 管理
- **定期審查**: 確保 $fillable 陣列只包含實際存在的資料庫欄位
- **安全性**: 使用白名單原則，避免 Mass Assignment 風險
- **關鍵模型位置**:
  - `app/Models/Order.php` - 訂單模型（含共乘群組欄位）
  - `app/Models/Customer.php` - 客戶模型（含 JSON 欄位轉換）
  - `app/Models/Driver.php` - 司機模型
  - `app/Models/Landmark.php` - 地標模型
  - `app/Models/DispatchRecord.php` - 排趟記錄模型

### 效能最佳化
- **查詢優化**: 使用 `with()` 預載關聯避免 N+1 查詢
- **索引使用**: 為常用查詢欄位建立適當索引
  - `orders` 表：order_number (unique)、ride_date、customer_id、driver_id
  - `order_sequences` 表：date (unique)
- **快取策略**: 生產環境建議啟用 Redis 快取
- **分塊處理**: 大量資料操作使用 `chunk()` 方法，建議每批 1000 筆
- **匯出最佳化**: 超過 10000 筆記錄時使用分塊匯出

### 維護要點
- **代碼風格**: 定期執行 `./vendor/bin/pint` 格式化代碼
- **依賴更新**: 定期檢查並更新 Composer 和 NPM 依賴
- **安全性**: 定期檢查並修復安全漏洞
- **備份策略**: 定期備份資料庫和重要檔案
- **文件同步**: 專案包含多個 .md 規劃文件，開發時參考：
  - `AGENTS.md` - AI Agent 使用說明
  - `CLAUDE.md` - 本檔案，專案開發指南
  - `EXCEL_匯入格式規格說明.md` - Excel 匯入格式定義
  - `共乘單方案.md` - 共乘功能設計文件
  - `訂單建立多天功能.md` - 批量訂單建立規劃
  - `訂單批量更新功能說明.md` - 批量更新功能說明
  - `order-export-by-creation-date.md` - 時間範圍匯出功能規劃
  - `簡化格式匯出修改與批量更新調整.md` - 匯出格式調整
  - `資料庫備份開發.md` - 資料庫備份系統開發文件
  - `LC-接單系統訂單串接規劃.md` - 接單系統串接規劃
  - `1141114新增上下車地址條件判斷.md` - 地址條件判斷
  - `客戶管理匯入.md` - 客戶匯入功能文件
  - `修改調整.md` - 歷史修改記錄
