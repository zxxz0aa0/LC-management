# LC-management 與接單系統訂單串接開發規劃

**版本**: 1.0
**最後更新**: 2025-11-20
**狀態**: 開發中

---

## 📋 目錄

1. [系統概況](#系統概況)
2. [業務流程](#業務流程)
3. [技術架構](#技術架構)
4. [API 規格定義](#api-規格定義)
5. [資料庫設計](#資料庫設計)
6. [前端整合方案](#前端整合方案)
7. [安全性設計](#安全性設計)
8. [錯誤處理策略](#錯誤處理策略)
9. [分階段實施計畫](#分階段實施計畫)
10. [測試計畫](#測試計畫)
11. [部署檢查清單](#部署檢查清單)

---

## 系統概況

### 系統介紹

- **LC-management**（當前系統）：訂單管理系統，提供訂單的建立、編輯、查詢功能
- **接單系統**：派遣執行系統，負責訂單派遣和司機指派
- **串接目標**：訂單雙向同步
  - LC-management → 接單系統：匯出訂單
  - 接單系統 → LC-management：批量更新派遣結果

### 技術棧

| 項目 | 技術 |
|------|------|
| 後端框架 | Laravel 10.x |
| PHP 版本 | 8.1+ |
| 資料庫 | MySQL 8.0+ |
| 認證方式 | Laravel Sanctum |
| API 風格 | RESTful |
| 訊息傳輸 | JSON |

### 環境

| 環境 | 網路 | 說明 |
|------|------|------|
| 開發環境 | 內網 | 本地/局域網訪問 |
| 正式環境 | 外網 | HTTPS + Token 認證 |

---

## 業務流程

### 流程 1️⃣：匯出訂單至接單系統

```
LC-management 管理員
  ↓
【選擇用車日期】
  ↓
【點擊「匯出到接單系統」按鈕】
  ↓
API: POST /api/orders/export-to-dispatch-system
  ├─ 查詢條件：ride_date、status='open'、has driver_fleet_number
  ├─ 資料格式：SimpleOrdersExport (16 欄位)
  ├─ 認證：Sanctum Token
  ↓
【接單系統接收訂單資料】
  ├─ 存儲或更新訂單
  ├─ 派遣流程
  ↓
【記錄匯出日誌】
  └─ order_export_logs 表
```

**匯出訂單的過濾條件**：
- `ride_date` = 使用者選擇的日期
- `status` = `open`（可派遣）
- `driver_fleet_number` 不為空（有車隊編號）
- `is_main_order` = `true`（只匯出主訂單，避免共乘重複）

**匯出資料格式**（16 欄位）：
```json
{
  "訂單編號": "order_number",
  "姓名": "customer_name",
  "電話": "customer_phone",
  "身分證": "customer_id_number (格式化)",
  "類型": "order_type",
  "日期": "ride_date (Y-m-d)",
  "時間": "ride_time (H:i)",
  "上車區": "pickup_district",
  "上車地址": "pickup_address",
  "下車區": "dropoff_district",
  "下車地址": "dropoff_address",
  "備註": "remark",
  "隊員編號": "driver_fleet_number",
  "特殊狀態": "special_status",
  "輪椅": "wheelchair",
  "爬梯機": "stair_machine"
}
```

---

### 流程 2️⃣：接單系統批量更新 LC-management 訂單

```
接單系統（派遣完成）
  ↓
【準備批量更新資料】
  ├─ 訂單編號（查詢用）
  ├─ 隊員編號（driver_fleet_number）
  ├─ 媒合時間（match_time）
  ├─ 訂單狀態（status）
  ↓
API: POST /api/webhook/orders/batch-update
  ├─ 認證：Sanctum Token
  ├─ 請求體：批量更新陣列
  ↓
【LC-management 接收更新】
  ├─ 根據訂單編號查詢訂單
  ├─ 找不到則跳過
  ├─ 找到則更新以下欄位：
  │   ├─ driver_fleet_number
  │   ├─ match_time
  │   ├─ status
  │   ├─ driver_id（根據 fleet_number 查詢）
  │   ├─ driver_name
  │   ├─ driver_plate_number
  ↓
【記錄 Webhook 日誌】
  └─ webhook_logs 表
```

**批量更新的欄位對應**（參考 order-table.blade.php 第 388-469 行）：

| 欄位 | 來源 | 說明 |
|------|------|------|
| `order_number` | A 欄（訂單編號） | 用於查詢要更新的訂單，必填 |
| `driver_fleet_number` | E 欄（隊員編號） | 更新駕駛資訊，對應 drivers 表的 fleet_number |
| `match_time` | H 欄（媒合時間） | 更新媒合時間，格式：YYYY-MM-DD HH:MM:SS |
| `status` | O 欄（狀態） | 更新訂單狀態（open/assigned/bkorder/cancelled/...） |

**批量更新請求格式**：
```json
{
  "orders": [
    {
      "order_number": "NTPC123202511181430001",
      "driver_fleet_number": "A001",
      "match_time": "2025-11-18 14:30:00",
      "status": "assigned"
    },
    {
      "order_number": "NTPC456202511181500002",
      "driver_fleet_number": "B002",
      "match_time": "2025-11-18 15:00:00",
      "status": "assigned"
    }
  ]
}
```

---

### 流程 3️⃣：編輯訂單時的同步

❌ **不實現**

原因：
- 避免複雜性增加
- 編輯訂單是低頻操作
- 由接單系統側的批量更新邏輯來處理已存在訂單的更新

---

## 技術架構

### 整體架構圖

```
┌─────────────────────────────────────────────────────────────────┐
│                     LC-management 系統                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────┐                  ┌──────────────────┐    │
│  │  前端介面        │                  │  API 認證層      │    │
│  │  (訂單列表頁面)   │                  │  (Sanctum Token) │    │
│  │                   │                  │                   │    │
│  │ [匯出按鈕]        │                  │                   │    │
│  │ [日期選擇]        │                  │                   │    │
│  │ [操作記錄]        │                  │                   │    │
│  └────────┬──────────┘                  └────────┬──────────┘   │
│           │                                      │               │
│           ▼                                      ▼               │
│  ┌────────────────────────────────────────────────────┐         │
│  │      API 路由層 (routes/api.php)                    │         │
│  │                                                     │         │
│  │  POST /api/orders/export-to-dispatch-system       │         │
│  │  POST /api/webhook/orders/batch-update            │         │
│  └──────────┬──────────────────────────┬─────────────┘         │
│             │                          │                         │
│             ▼                          ▼                         │
│  ┌────────────────────┐    ┌───────────────────────┐            │
│  │  控制器層           │    │  控制器層              │            │
│  │(OrderExportController) │(OrderWebhookController)│            │
│  │                     │    │                       │            │
│  │ - 驗證日期          │    │ - 驗證 Token          │            │
│  │ - 查詢訂單          │    │ - 批量更新訂單        │            │
│  │ - 格式化資料        │    │ - 駕駛資訊查詢        │            │
│  │ - 記錄日誌          │    │ - 記錄 Webhook 日誌   │            │
│  └────────┬───────────┘    └───────────┬──────────┘            │
│           │                            │                         │
│           ▼                            ▼                         │
│  ┌────────────────────────────────────────────────┐             │
│  │        業務邏輯層 (Services)                     │             │
│  │                                                 │             │
│  │  - OrderExportService                          │             │
│  │  - OrderBatchUpdateService                     │             │
│  │  - ExportLogService                            │             │
│  │  - WebhookLogService                           │             │
│  └────────┬──────────────────────────┬───────────┘             │
│           │                          │                         │
│           ▼                          ▼                         │
│  ┌─────────────────────────────────────────────┐               │
│  │         資料庫層 (Models)                     │               │
│  │                                             │               │
│  │  - Order.php                               │               │
│  │  - Driver.php                              │               │
│  │  - ExportLog.php                           │               │
│  │  - WebhookLog.php                          │               │
│  └─────────────────────────────────────────────┘               │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
         │                                    ▲
         │                                    │
         │ 匯出訂單                      批量更新訂單
         │ (16欄位 JSON)               (訂單號+駕駛+狀態)
         │                                    │
         ▼                                    │
┌─────────────────────────────────────────────────────────────────┐
│                       接單系統 (zooserve)                         │
│                                                                   │
│  ┌─────────────────────────────────────────────────────┐       │
│  │  - 接收訂單                                         │       │
│  │  - 派遣處理                                         │       │
│  │  - 司機指派                                         │       │
│  │  - 批量回傳更新結果                                 │       │
│  └─────────────────────────────────────────────────────┘       │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### 認證機制

**Sanctum Token 認證流程**：

1. **Token 生成**（一次性，正式環境進行）
   ```php
   // 在 LC-management 中為接單系統生成 Token
   $token = User::find($userId)->createToken('dispatch-system-token');
   // 返回 Token 值給接單系統保管
   ```

2. **Token 使用**（接單系統每次 API 呼叫）
   ```
   Authorization: Bearer {token}
   Content-Type: application/json
   ```

3. **Token 驗證**（LC-management 中間件）
   ```php
   // routes/api.php
   Route::middleware('auth:sanctum')->group(function () {
       Route::post('/orders/export-to-dispatch-system', ...);
       Route::post('/webhook/orders/batch-update', ...);
   });
   ```

---

## API 規格定義

### API 1：匯出訂單至接單系統

**端點**: `POST /api/orders/export-to-dispatch-system`

**認證**: Sanctum Token (Bearer Token)

**請求頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**請求參數**:
```json
{
  "ride_date": "2025-11-20",
  "per_page": 100
}
```

| 參數 | 型別 | 必填 | 說明 |
|------|------|------|------|
| `ride_date` | String (Y-m-d) | ✅ 是 | 用車日期 |
| `per_page` | Integer | ❌ 否 | 分頁大小（預設 100，最大 1000） |

**成功回應** (200 OK):
```json
{
  "success": true,
  "message": "訂單匯出成功",
  "data": {
    "exported_count": 50,
    "ride_date": "2025-11-20",
    "orders": [
      {
        "訂單編號": "NTPC123202511201430001",
        "姓名": "張三",
        "電話": "0912345678",
        "身分證": "NT12345",
        "類型": "新北長照",
        "日期": "2025-11-20",
        "時間": "14:30",
        "上車區": "板橋區",
        "上車地址": "文化路1號",
        "下車區": "中山區",
        "下車地址": "中山路2號",
        "備註": "輪椅",
        "隊員編號": "A001",
        "特殊狀態": "網頁",
        "輪椅": "是",
        "爬梯機": "否"
      },
      // ... 更多訂單
    ],
    "export_log_id": 123
  }
}
```

**錯誤回應** (400 Bad Request):
```json
{
  "success": false,
  "message": "日期格式錯誤，請使用 Y-m-d 格式",
  "errors": {
    "ride_date": ["日期格式無效"]
  }
}
```

**錯誤回應** (401 Unauthorized):
```json
{
  "success": false,
  "message": "未認證或 Token 無效"
}
```

**錯誤回應** (500 Internal Server Error):
```json
{
  "success": false,
  "message": "伺服器錯誤",
  "error": "database connection failed"
}
```

**cURL 範例**:
```bash
curl -X POST https://lc-management.example.com/api/orders/export-to-dispatch-system \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "ride_date": "2025-11-20"
  }'
```

---

### API 2：批量更新訂單（Webhook）

**端點**: `POST /api/webhook/orders/batch-update`

**認證**: Sanctum Token (Bearer Token)

**請求頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
X-Webhook-Signature: {signature}  // 可選：簽名驗證
```

**請求體**:
```json
{
  "orders": [
    {
      "order_number": "NTPC123202511201430001",
      "driver_fleet_number": "A001",
      "match_time": "2025-11-20 14:30:00",
      "status": "assigned"
    },
    {
      "order_number": "NTPC456202511201500002",
      "driver_fleet_number": "B002",
      "match_time": "2025-11-20 15:00:00",
      "status": "assigned"
    }
  ]
}
```

| 欄位 | 型別 | 必填 | 說明 |
|------|------|------|------|
| `order_number` | String | ✅ 是 | 訂單編號（用於查詢） |
| `driver_fleet_number` | String | ❌ 否 | 隊員編號（更新駕駛資訊） |
| `match_time` | String (Y-m-d H:i:s) | ❌ 否 | 媒合時間 |
| `status` | String | ❌ 否 | 訂單狀態（open/assigned/...） |

**成功回應** (200 OK):
```json
{
  "success": true,
  "message": "批量更新完成",
  "data": {
    "total_requested": 2,
    "total_updated": 2,
    "total_skipped": 0,
    "skipped_orders": [],
    "webhook_log_id": 456
  }
}
```

**部分成功回應** (200 OK):
```json
{
  "success": true,
  "message": "批量更新完成（部分成功）",
  "data": {
    "total_requested": 3,
    "total_updated": 2,
    "total_skipped": 1,
    "skipped_orders": [
      {
        "order_number": "NTPC999202511201430999",
        "reason": "訂單編號不存在"
      }
    ],
    "webhook_log_id": 457
  }
}
```

**錯誤回應** (400 Bad Request):
```json
{
  "success": false,
  "message": "請求格式錯誤",
  "errors": {
    "orders": ["orders 必須是陣列"]
  }
}
```

**錯誤回應** (401 Unauthorized):
```json
{
  "success": false,
  "message": "未認證或 Token 無效"
}
```

**cURL 範例**:
```bash
curl -X POST https://lc-management.example.com/api/webhook/orders/batch-update \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "orders": [
      {
        "order_number": "NTPC123202511201430001",
        "driver_fleet_number": "A001",
        "match_time": "2025-11-20 14:30:00",
        "status": "assigned"
      }
    ]
  }'
```

---

### API 3：查詢匯出日誌（輔助 API）

**端點**: `GET /api/orders/export-logs`

**認證**: Sanctum Token

**查詢參數**:
```
?start_date=2025-11-01&end_date=2025-11-30&per_page=20&page=1
```

**成功回應** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "exported_date": "2025-11-20",
      "order_count": 50,
      "status": "success",
      "exported_by": "admin",
      "created_at": "2025-11-20 10:30:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total": 15,
    "per_page": 20
  }
}
```

---

## 資料庫設計

### Migration 1：建立 order_export_logs 表

**檔案**: `database/migrations/2025_11_20_000001_create_order_export_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_export_logs', function (Blueprint $table) {
            $table->id();

            // 匯出資訊
            $table->date('exported_date')->index('idx_exported_date');
            $table->integer('order_count')->default(0);
            $table->string('system_name')->default('接單系統')->comment('目標系統名稱');

            // 狀態
            $table->enum('status', ['success', 'partial_success', 'failed'])->default('success')
                ->index('idx_status');
            $table->text('error_message')->nullable()->comment('失敗原因');

            // 操作者
            $table->unsignedBigInteger('exported_by')->comment('操作人員 ID');
            $table->foreign('exported_by')->references('id')->on('users')->onDelete('cascade');

            // 時間戳
            $table->timestamps();

            // 索引
            $table->index(['created_at'], 'idx_created_at');
            $table->index(['exported_by'], 'idx_exported_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_export_logs');
    }
};
```

### Migration 2：建立 webhook_logs 表

**檔案**: `database/migrations/2025_11_20_000002_create_webhook_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();

            // 事件資訊
            $table->string('event_type')->index('idx_event_type')->comment('事件類型');
            $table->json('payload')->comment('請求內容');

            // 狀態
            $table->enum('status', ['success', 'failed'])->default('success')
                ->index('idx_webhook_status');
            $table->json('response')->nullable()->comment('回應內容');
            $table->text('error_message')->nullable()->comment('錯誤信息');

            // 統計資訊
            $table->integer('total_requested')->nullable()->comment('請求的訂單總數');
            $table->integer('total_updated')->nullable()->comment('成功更新的訂單數');
            $table->integer('total_skipped')->nullable()->comment('跳過的訂單數');

            // 時間戳
            $table->timestamps();

            // 索引
            $table->index(['created_at'], 'idx_webhook_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
```

### Model 1：ExportLog

**檔案**: `app/Models/ExportLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExportLog extends Model
{
    use HasFactory;

    protected $table = 'order_export_logs';

    protected $fillable = [
        'exported_date',
        'order_count',
        'system_name',
        'status',
        'error_message',
        'exported_by',
    ];

    protected $casts = [
        'exported_date' => 'date',
    ];

    // 關聯：匯出者
    public function exportedBy()
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
```

### Model 2：WebhookLog

**檔案**: `app/Models/WebhookLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebhookLog extends Model
{
    use HasFactory;

    protected $table = 'webhook_logs';

    protected $fillable = [
        'event_type',
        'payload',
        'status',
        'response',
        'error_message',
        'total_requested',
        'total_updated',
        'total_skipped',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];
}
```

---

## 前端整合方案

### 整合 1：訂單列表頁面新增匯出按鈕

**檔案**: `resources/views/orders/components/order-table.blade.php`

**修改位置**: 第 16-32 行（匯出按鈕組）

**新增按鈕**：
```blade
<!-- 新增：匯出到接單系統 -->
<button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#exportToDispatchModal">
    <i class="fas fa-truck me-2"></i>匯出到接單系統
</button>
```

### 整合 2：匯出到接單系統 Modal

**新增 Modal 元素**（添加到 order-table.blade.php 的 Script 區塊之前）：

```blade
{{-- 匯出到接單系統 Modal --}}
<div class="modal fade" id="exportToDispatchModal" tabindex="-1" aria-labelledby="exportToDispatchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="exportToDispatchModalLabel">
                    <i class="fas fa-truck me-2"></i>匯出訂單到接單系統
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="dispatchExportDate" class="form-label">
                        <i class="fas fa-calendar-alt me-2"></i>選擇用車日期
                    </label>
                    <input type="date" class="form-control" id="dispatchExportDate" required>
                    <small class="text-muted">將匯出該日期所有狀態為「可派遣」且有車隊編號的訂單</small>
                </div>
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>匯出說明：</h6>
                    <ul class="mb-0">
                        <li>僅匯出用車日期為選擇日期的訂單</li>
                        <li>僅匯出訂單狀態為「可派遣」(open) 的訂單</li>
                        <li>僅匯出有車隊編號的訂單</li>
                        <li>共乘訂單只匯出主訂單</li>
                        <li>匯出後將記錄操作日誌</li>
                    </ul>
                </div>
                <div id="exportProgress" style="display: none;">
                    <div class="progress" role="progressbar">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                    </div>
                    <small class="text-muted mt-2">正在匯出，請勿關閉此視窗...</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>取消
                </button>
                <button type="button" class="btn btn-primary" id="confirmDispatchExport" onclick="exportToDispatchSystem()">
                    <i class="fas fa-upload me-2"></i>開始匯出
                </button>
            </div>
        </div>
    </div>
</div>
```

### 整合 3：JavaScript 匯出邏輯

**新增到 order-table.blade.php 的 Script 區塊**：

```javascript
// 匯出到接單系統功能
function exportToDispatchSystem() {
    const dateInput = document.getElementById('dispatchExportDate');
    const rideDate = dateInput.value;

    // 驗證日期
    if (!rideDate) {
        alert('請選擇用車日期');
        return;
    }

    // 禁用按鈕，顯示進度條
    const confirmBtn = document.getElementById('confirmDispatchExport');
    const progressDiv = document.getElementById('exportProgress');

    confirmBtn.disabled = true;
    progressDiv.style.display = 'block';

    // 發送匯出請求
    fetch('/api/orders/export-to-dispatch-system', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            ride_date: rideDate
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 成功提示
            alert(`✅ 匯出成功！\n已匯出 ${data.data.exported_count} 筆訂單`);

            // 關閉 Modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('exportToDispatchModal'));
            modal.hide();

            // 重新載入頁面
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            // 錯誤提示
            alert(`❌ 匯出失敗\n${data.message}`);
        }
    })
    .catch(error => {
        console.error('匯出失敗:', error);
        alert('❌ 匯出失敗，請稍後再試');
    })
    .finally(() => {
        // 恢復按鈕狀態
        confirmBtn.disabled = false;
        progressDiv.style.display = 'none';
    });
}

// 初始化日期輸入框（設定預設值為今天）
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('dispatchExportDate');
    if (dateInput) {
        const today = new Date();
        dateInput.value = today.toISOString().split('T')[0];
    }
});
```

### 整合 4：操作日誌查看頁面（新增）

**檔案**: `resources/views/orders/export-logs.blade.php`

```blade
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>
                <i class="fas fa-history me-2"></i>匯出操作日誌
            </h2>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>匯出日期</th>
                            <th>訂單數量</th>
                            <th>目標系統</th>
                            <th>狀態</th>
                            <th>操作人員</th>
                            <th>操作時間</th>
                            <th>備註</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->exported_date->format('Y-m-d') }}</td>
                            <td><span class="badge bg-info">{{ $log->order_count }}</span></td>
                            <td>{{ $log->system_name }}</td>
                            <td>
                                @if($log->status === 'success')
                                    <span class="badge bg-success">成功</span>
                                @elseif($log->status === 'partial_success')
                                    <span class="badge bg-warning">部分成功</span>
                                @else
                                    <span class="badge bg-danger">失敗</span>
                                @endif
                            </td>
                            <td>{{ $log->exportedBy->name }}</td>
                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @if($log->error_message)
                                    <small class="text-danger">{{ Str::limit($log->error_message, 50) }}</small>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">暫無操作日誌</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
```

---

## 安全性設計

### 認證與授權

1. **API Token 生成（一次性，正式環境進行）**
   ```bash
   php artisan tinker
   > $user = User::find(1); // 系統管理員
   > $token = $user->createToken('dispatch-system-token')->plainTextToken;
   > echo $token; // 輸出 Token，複製給接單系統保管
   ```

2. **Token 管理**
   - 在 `.env` 中不要硬編碼 Token
   - 使用環境變數或密鑰管理系統
   - 定期輪換 Token（建議每 6 個月）

3. **正式環境要求**
   - ✅ 強制 HTTPS
   - ✅ Token 有效期設定（需要修改 Sanctum 配置）
   - ✅ IP 白名單（可選，在防火牆層實現）

### 資料驗證

1. **輸入驗證**
   ```php
   // 日期格式驗證
   $validated = $request->validate([
       'ride_date' => 'required|date_format:Y-m-d',
   ]);

   // 批量更新欄位驗證
   $validated = $request->validate([
       'orders' => 'required|array|max:500',
       'orders.*.order_number' => 'required|string|exists:orders,order_number',
       'orders.*.driver_fleet_number' => 'nullable|string|exists:drivers,fleet_number',
       'orders.*.status' => 'nullable|string|in:open,assigned,bkorder,cancelled',
   ]);
   ```

2. **SQL 注入防護**
   - 使用 Eloquent ORM（自動參數化）
   - 避免原始 SQL 查詢

3. **XSS 防護**
   - 所有 JSON 回應自動轉義
   - 前端使用 `innerText` 而非 `innerHTML`

### 日誌與審計

1. **操作日誌**
   - 記錄所有匯出操作
   - 記錄所有 Webhook 調用
   - 包含時間戳、操作人、IP 地址等

2. **錯誤日誌**
   ```php
   // 使用 Laravel 日誌
   Log::channel('dispatch')->info('訂單匯出成功', [
       'ride_date' => $rideDate,
       'count' => $orders->count(),
       'user_id' => auth()->id(),
   ]);
   ```

### 正式環境 .env 配置

```bash
# API 認證
SANCTUM_STATEFUL_DOMAINS=api.lc-management.example.com
SANCTUM_EXPIRATION=31536000  # 1 年（秒）

# HTTPS
APP_URL=https://lc-management.example.com
SESSION_SECURE_COOKIES=true
SESSION_HTTP_ONLY=true

# CORS（如果接單系統在不同域名）
CORS_ALLOWED_ORIGINS=https://dispatch.example.com

# 日誌
LOG_CHANNEL=stack
LOG_LEVEL=info
```

---

## 錯誤處理策略

### 匯出失敗處理

| 場景 | 錯誤碼 | 處理策略 |
|------|--------|--------|
| 日期格式錯誤 | 400 | 驗證失敗，提示使用者 |
| 查詢訂單失敗 | 500 | 記錄日誌，記錄為失敗，允許手動重試 |
| 網路連線失敗 | 503 | 記錄日誌，提示使用者稍後重試 |
| Token 過期 | 401 | 提示管理員重新生成 Token |

### Webhook 失敗處理

| 場景 | 錯誤碼 | 處理策略 |
|------|--------|--------|
| 訂單編號不存在 | 200（部分成功） | 跳過該訂單，記錄日誌 |
| 駕駛隊編不存在 | 200（部分成功） | 跳過該訂單，記錄日誌 |
| 資料格式錯誤 | 400 | 拒絕請求，記錄完整錯誤信息 |
| 資料庫錯誤 | 500 | 記錄日誌，返回 500 錯誤 |

### 重試策略

❌ **不實現自動重試**

原因：
- 人工檢查更安全可靠
- 管理員可以根據失敗原因進行不同處理
- 避免重複更新風險

✅ **手動重試機制**：
- 在操作日誌頁面顯示失敗記錄
- 提供「重新匯出」按鈕
- 接單系統可重新發送 Webhook

---

## 分階段實施計畫

### 第一階段：基礎開發（3-4 天）

**目標**：實現核心 API 和資料庫

**任務清單**：
1. ✅ 建立 Migration：order_export_logs 表
2. ✅ 建立 Migration：webhook_logs 表
3. ✅ 建立 Model：ExportLog、WebhookLog
4. ✅ 建立 Controller：OrderExportController
5. ✅ 建立 Controller：OrderWebhookController
6. ✅ 建立 Service：OrderExportService
7. ✅ 建立 Service：OrderBatchUpdateService
8. ✅ 定義 API 路由（routes/api.php）
9. ✅ 實現 API 1：POST /api/orders/export-to-dispatch-system
10. ✅ 實現 API 2：POST /api/webhook/orders/batch-update
11. ✅ 實現 Sanctum Token 認證

**測試**：
- 單元測試：Service 層邏輯
- 集成測試：Controller + Model

**交付物**：
- 完整的後端 API
- 自動化測試覆蓋率 > 80%

---

### 第二階段：前端整合（2-3 天）

**目標**：實現前端界面和操作日誌

**任務清單**：
1. ✅ 在 order-table.blade.php 新增「匯出到接單系統」按鈕
2. ✅ 建立匯出 Modal（日期選擇）
3. ✅ 實現 JavaScript 匯出邏輯
4. ✅ 新增路由：GET /orders/export-logs
5. ✅ 建立操作日誌查看頁面
6. ✅ 實現進度提示和結果反饋

**測試**：
- 功能測試：匯出流程
- UI 測試：Modal 交互
- 日誌驗證

**交付物**：
- 完整的前端界面
- 用戶友好的匯出體驗

---

### 第三階段：文檔與培訓（1 天）

**目標**：準備部署和文檔

**任務清單**：
1. ✅ 編寫 API 規格書
2. ✅ 編寫部署指南
3. ✅ 編寫 Token 生成步驟
4. ✅ 編寫故障排查指南
5. ✅ 準備培訓資料

**交付物**：
- API 規格書
- 部署手冊
- 運維手冊

---

### 第四階段：測試與部署（2-3 天）

**目標**：正式環境部署

**任務清單**：
1. ✅ 在開發環境完整測試
2. ✅ 與接單系統進行集成測試
3. ✅ 在正式環境進行灰度測試（少量訂單）
4. ✅ 全量上線

**測試**：
- 端對端測試
- 負載測試（100-1000 訂單）
- 安全性測試

**交付物**：
- 測試報告
- 上線檢查清單
- 問題日誌

---

## 測試計畫

### 單元測試

**測試檔案**：`tests/Unit/Services/OrderExportServiceTest.php`

```php
public function test_export_with_valid_date()
{
    $orders = Order::factory()->count(10)->create([
        'ride_date' => '2025-11-20',
        'status' => 'open',
        'driver_fleet_number' => 'A001',
    ]);

    $service = new OrderExportService();
    $result = $service->export('2025-11-20');

    $this->assertEquals(10, count($result));
    $this->assertEquals(10, $orders->count());
}

public function test_export_filters_by_status()
{
    Order::factory()->create(['status' => 'open', 'ride_date' => '2025-11-20']);
    Order::factory()->create(['status' => 'cancelled', 'ride_date' => '2025-11-20']);

    $service = new OrderExportService();
    $result = $service->export('2025-11-20');

    $this->assertEquals(1, count($result));
}

public function test_export_requires_driver_fleet_number()
{
    Order::factory()->create([
        'ride_date' => '2025-11-20',
        'status' => 'open',
        'driver_fleet_number' => null,
    ]);

    $service = new OrderExportService();
    $result = $service->export('2025-11-20');

    $this->assertEquals(0, count($result));
}
```

### 集成測試

**測試檔案**：`tests/Feature/Api/OrderExportApiTest.php`

```php
public function test_export_api_success()
{
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    Order::factory()->count(5)->create([
        'ride_date' => '2025-11-20',
        'status' => 'open',
        'driver_fleet_number' => 'A001',
    ]);

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/orders/export-to-dispatch-system', [
        'ride_date' => '2025-11-20',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.exported_count', 5)
        ->assertJsonPath('success', true);
}

public function test_export_api_requires_auth()
{
    $response = $this->postJson('/api/orders/export-to-dispatch-system', [
        'ride_date' => '2025-11-20',
    ]);

    $response->assertStatus(401);
}

public function test_webhook_batch_update_success()
{
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $order = Order::factory()->create(['order_number' => 'TEST001']);
    $driver = Driver::factory()->create(['fleet_number' => 'A001']);

    $response = $this->withHeaders([
        'Authorization' => "Bearer $token",
    ])->postJson('/api/webhook/orders/batch-update', [
        'orders' => [
            [
                'order_number' => 'TEST001',
                'driver_fleet_number' => 'A001',
                'status' => 'assigned',
            ],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.total_updated', 1);

    $this->assertDatabaseHas('orders', [
        'order_number' => 'TEST001',
        'status' => 'assigned',
    ]);
}
```

### 端對端測試

**場景 1：完整的匯出流程**

```
1. 建立 5 筆訂單（status=open，有 driver_fleet_number）
2. 管理員在前端選擇日期並點擊「匯出到接單系統」
3. 確認：
   - API 返回 200 並包含 5 筆訂單
   - order_export_logs 表記錄了操作
   - 前端顯示「匯出成功」訊息
```

**場景 2：Webhook 批量更新流程**

```
1. 建立 3 筆訂單（不同狀態）
2. 接單系統呼叫 POST /api/webhook/orders/batch-update
3. 確認：
   - API 返回 200
   - 訂單狀態被正確更新
   - webhook_logs 表記錄了操作
   - 不存在的訂單編號被跳過
```

### 負載測試

**目標**：測試系統在大量訂單下的表現

```bash
# 建立 1000 筆測試訂單
php artisan tinker
> Order::factory()->count(1000)->create(['ride_date' => '2025-11-20', 'status' => 'open'])

# 執行 API 測試
> time curl -X POST http://localhost:8000/api/orders/export-to-dispatch-system ...

# 預期結果：< 3 秒內完成
```

---

## 部署檢查清單

### 正式環境部署前

- [ ] **資料庫**
  - [ ] 執行 Migration：`php artisan migrate`
  - [ ] 確認表結構正確
  - [ ] 備份現有資料庫

- [ ] **環境配置**
  - [ ] `.env` 配置正確（HTTPS、CORS、日誌等）
  - [ ] 生成應用金鑰：`php artisan key:generate`
  - [ ] 清除快取：`php artisan optimize:clear`

- [ ] **認證設定**
  - [ ] 為接單系統生成 API Token
  - [ ] Token 安全儲存（不要在 Git 中提交）
  - [ ] 測試 Token 認證流程

- [ ] **API 測試**
  - [ ] 測試匯出 API（小規模：10-50 訂單）
  - [ ] 測試 Webhook API（批量更新）
  - [ ] 測試錯誤情況（無效日期、Token 過期等）

- [ ] **前端驗證**
  - [ ] 驗證前端按鈕和 Modal 正常顯示
  - [ ] 驗證匯出流程順利完成
  - [ ] 驗證操作日誌正確記錄

- [ ] **監控準備**
  - [ ] 配置日誌監控
  - [ ] 配置應用性能監控（APM）
  - [ ] 準備告警規則

- [ ] **文檔準備**
  - [ ] API 規格書已交付接單系統團隊
  - [ ] 運維手冊已準備
  - [ ] 故障排查指南已準備

- [ ] **安全檢查**
  - [ ] 強制 HTTPS
  - [ ] 驗證 CORS 配置
  - [ ] 驗證 Token 不會暴露在日誌中
  - [ ] 進行安全掃描（可選）

### 正式環境部署後

- [ ] **上線驗證**
  - [ ] 測試一個真實的匯出操作
  - [ ] 檢查操作日誌
  - [ ] 確認接單系統接收到訂單

- [ ] **監控檢查**
  - [ ] 檢查應用日誌（無異常錯誤）
  - [ ] 檢查資料庫效能（無緩慢查詢）
  - [ ] 檢查 API 回應時間

- [ ] **應急準備**
  - [ ] 準備回滾計畫
  - [ ] 準備 Token 輪換計畫
  - [ ] 準備故障恢復步驟

---

## 常見問題

### Q1：如何生成 API Token？

**A**：在 Laravel Tinker 中執行：
```bash
php artisan tinker
> $user = User::where('email', 'admin@example.com')->first();
> $token = $user->createToken('dispatch-system-token')->plainTextToken;
> echo $token;
```

然後將 Token 值複製給接單系統團隊。

### Q2：Token 過期了怎麼辦？

**A**：重新生成新的 Token：
```bash
php artisan tinker
> $user = User::where('email', 'admin@example.com')->first();
> $user->tokens()->delete(); // 清除舊 Token
> $token = $user->createToken('dispatch-system-token')->plainTextToken;
> echo $token;
```

### Q3：匯出失敗了怎麼辦？

**A**：
1. 檢查 `order_export_logs` 表中的失敗記錄
2. 查看 error_message 了解失敗原因
3. 解決問題後（例如：日期格式錯誤），重新匯出

### Q4：如何監控 API 呼叫？

**A**：
- 查看 `order_export_logs` 表了解匯出歷史
- 查看 `webhook_logs` 表了解 Webhook 調用
- 檢查 Laravel 日誌：`storage/logs/laravel.log`

### Q5：可以匯出多個日期的訂單嗎？

**A**：目前設計是一次匯出一個日期。如果需要匯出多個日期，可以：
- 手動多次點擊按鈕
- 未來可以擴展為支援日期範圍

---

## 相關文檔

- CLAUDE.md - 專案總體指南
- 訂單建立多天功能.md - 訂單批量建立規劃
- 共乘單方案.md - 共乘功能設計

---

## 更新日誌

| 版本 | 日期 | 更新內容 |
|------|------|--------|
| 1.0 | 2025-11-20 | 初版規劃，包含完整 API、資料庫、前端設計 |

