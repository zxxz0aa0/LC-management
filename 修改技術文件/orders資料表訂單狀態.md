# Orders 資料表狀態欄位 (status) 說明文件

## 基本資訊

- **建立日期**: 2025-11-28
- **最後更新**: 2025-11-28
- **維護者**: Claude Code
- **資料表名稱**: `orders`
- **欄位名稱**: `status` (ENUM)

---

## 概述

`orders` 資料表的 `status` 欄位用於標記訂單的狀態。此欄位使用 MySQL ENUM 型態定義，限制只能使用預定義的狀態值。

---

## 目前狀態值列表

*截至 2025-11-28，`status` 欄位共有 **12 個狀態值***

| 狀態值 | 中文名稱 | Badge 顏色 | 使用說明 |
|--------|---------|-----------|---------|
| `open` | 可派遣 | 綠色 (bg-success) | 訂單可派遣狀態 |
| `assigned` | 已指派 | 藍色 (bg-primary) | 訂單已指派給司機 |
| `bkorder` | 已候補 | 黃色 (bg-warning) | 訂單已候補 |
| `blocked` | 無人承接 | 淡藍 (bg-info) | 訂單無人承接中 |
| `cancelled` | 已取消 | 紅色 (bg-danger) | 一般取消狀態 |
| `cancelledOOC` | 已取消-9999 | 紅色 (bg-danger) | 別家有車而取消 |
| `cancelledNOC` | 取消! | 紅色 (bg-danger) | 來不及通知而取消狀態 |
| `cancelledCOTD` | 取消 X | 紅色 (bg-danger) | 當天一來不及通知而取消狀態 |
| `blacklist` | 黑名單 | 深灰色 (bg-dark) | 客戶黑名單狀態 |
| `no_send` | 不派遣 | 紅色 (bg-danger) | 訂單不派遣 |
| `regular_sedans` | 一般車 | 灰色 (bg-secondary) | 需使用一般車訂單 *(新增於 2025-11-28)* |
| `no_car` | 沒車 | 灰色 (bg-secondary) | 目前沒車訂單 *(新增於 2025-11-28)* |

**預設值**: `'open'`

---

## 最近一次修改紀錄 (2025-11-28)

### 修改目的
新增兩個新狀態值供公司人員辨識訂單處理方式：
- `regular_sedans` (一般車)
- `no_car` (沒車)

### 修改說明
這兩個狀態是獨立的識別狀態，用於幫助工作人員判斷訂單的處理方式。

### Migration 檔案
**檔案名稱**: `2025_11_28_124944_add_regular_sedans_and_no_car_to_orders_status_enum.php`

**Up 方法** (新增狀態):
```php
DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('open', 'assigned', 'bkorder', 'blocked', 'cancelled', 'cancelledOOC', 'cancelledNOC', 'cancelledCOTD', 'blacklist', 'no_send', 'regular_sedans', 'no_car') NOT NULL DEFAULT 'open'");
```

**Down 方法** (回滾):
```php
DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('open', 'assigned', 'bkorder', 'blocked', 'cancelled', 'cancelledOOC', 'cancelledNOC', 'cancelledCOTD', 'blacklist', 'no_send') NOT NULL DEFAULT 'open'");
```

### 修改的檔案清單

#### 1. 資料庫遷移
- 新增 `database/migrations/2025_11_28_124944_add_regular_sedans_and_no_car_to_orders_status_enum.php` (新檔案)

#### 2. 前端 Blade 樣板
- 修改 `resources/views/orders/components/order-form.blade.php` - 新增兩個新選項
- 修改 `resources/views/orders/components/order-table.blade.php` - 新增兩個 switch case
- 修改 `resources/views/orders/components/order-detail.blade.php` - 新增兩個 switch case (同時補充缺少的
 blacklist 和 no_send)
- 修改 `resources/views/orders/components/order-table-search.blade.php` - 新增兩個 switch case (同時補充缺少的
 blacklist 和 no_send)
- 修改 `resources/views/orders/components/history-modal.blade.php` - 新增兩個 CSS 樣式

#### 3. 後端控制器/服務/驗證
- 修改 `app/Http/Controllers/OrderController.php` - 更新驗證規則 (store 和 update 方法)
- 修改 `app/Http/Requests/UpdateOrderRequest.php` - 更新 status 驗證規則（**重要**）
- 修改 `app/Services/ExcelFieldMapper.php` - 新增中文對應

### 詳細修改內容

#### 前端表單下拉選單 (order-form.blade.php)
```blade
<select name="status" class="form-select form-control-custom">
    <option value="open" {{ $defaultOrderStatus == 'open' ? 'selected' : '' }}>可派遣</option>
    <option value="assigned" {{ $defaultOrderStatus == 'assigned' ? 'selected' : '' }}>已指派</option>
    <option value="bkorder" {{ $defaultOrderStatus == 'bkorder' ? 'selected' : '' }}>已候補</option>
    <option value="blocked" {{ $defaultOrderStatus == 'blocked' ? 'selected' : '' }}>無人承接</option>
    <option value="no_send" {{ $defaultOrderStatus == 'no_send' ? 'selected' : '' }}>不派遣</option>
    <option value="blacklist" {{ $defaultOrderStatus == 'blacklist' ? 'selected' : '' }}>黑名單</option>
    <option value="regular_sedans" {{ $defaultOrderStatus == 'regular_sedans' ? 'selected' : '' }}>一般車</option>
    <option value="no_car" {{ $defaultOrderStatus == 'no_car' ? 'selected' : '' }}>沒車</option>
</select>
```

#### 訂單列表顯示邏輯 (order-table.blade.php, order-detail.blade.php, order-table-search.blade.php)
```blade
@case('regular_sedans')
    <span class="badge bg-secondary">一般車</span>
    @break
@case('no_car')
    <span class="badge bg-secondary">沒車</span>
    @break
```

#### CSS 樣式定義 (history-modal.blade.php)
```css
.status-regular_sedans {
    background-color: #6c757d !important;
    color: white;
}
.status-no_car {
    background-color: #6c757d !important;
    color: white;
}
```

#### 驗證規則更新 (OrderController.php)
```php
'status' => 'required|in:open,assigned,bkorder,blocked,cancelled,cancelledOOC,cancelledNOC,cancelledCOTD,blacklist,no_send,regular_sedans,no_car'
```

#### FormRequest 驗證規則更新 (UpdateOrderRequest.php) **【重要】**
```php
'status' => 'required|in:open,assigned,bkorder,blocked,cancelled,cancelledOOC,cancelledNOC,cancelledCOTD,blacklist,no_send,regular_sedans,no_car'
```

**注意**：編輯訂單功能使用 `UpdateOrderRequest` 進行驗證，如果忘記更新此檔案，編輯訂單時選擇新狀態會出現「The selected status is invalid.」錯誤。

#### Excel 欄位對應 (ExcelFieldMapper.php)
```php
private $statusMappings = [
    // ... 原有的狀態對應 ...
    '一般車' => 'regular_sedans',
    '沒車' => 'no_car',
    // 英文值也支援直接匯入
    'regular_sedans' => 'regular_sedans',
    'no_car' => 'no_car',
];
```

---

## 未來如何新增狀態值

### 新增狀態值的步驟流程

如果未來需要新增其他狀態值，請依照以下步驟執行：

#### 步驟 1：建立 Migration
```bash
php artisan make:migration add_new_status_to_orders_status_enum
```

在 migration 檔案中修改 ENUM：
```php
public function up(): void
{
    DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('現有狀態1', '現有狀態2', ..., '新增狀態') NOT NULL DEFAULT 'open'");
}

public function down(): void
{
    DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('現有狀態1', '現有狀態2', ...) NOT NULL DEFAULT 'open'");
}
```

#### 步驟 2：更新前端表單下拉選單
在 `resources/views/orders/components/order-form.blade.php` 的 `<select>` 中新增選項：
```blade
<option value="新增狀態" {{ $defaultOrderStatus == '新增狀態' ? 'selected' : '' }}>中文名稱</option>
```

#### 步驟 3：更新前端列表顯示邏輯
在以下 4 個檔案的 `@switch($order->status)` 區塊中新增 case：
- `order-table.blade.php`
- `order-detail.blade.php`
- `order-table-search.blade.php`

```blade
@case('新增狀態')
    <span class="badge bg-顏色">中文名稱</span>
    @break
```

#### 步驟 4：更新歷史訂單模態框 CSS (選用)
在 `history-modal.blade.php` 的內嵌 CSS 樣式中新增：
```css
.status-新增狀態 {
    background-color: #顏色代碼 !important;
    color: white;
}
```

#### 步驟 5：更新驗證規則
在 `app/Http/Controllers/OrderController.php` 的驗證規則中新增新狀態：
```php
'status' => 'required|in:現有狀態1,現有狀態2,...,新增狀態'
```

需要更新的位置：
- `store()` 方法 (約第 131 行)
- `update()` 方法 (約第 310 行)

#### 步驟 6：更新 FormRequest 驗證規則 **【重要！容易遺漏】**
在 `app/Http/Requests/UpdateOrderRequest.php` 的驗證規則中新增新狀態：
```php
'status' => 'required|in:現有狀態1,現有狀態2,...,新增狀態'
```

**特別提醒**：此步驟非常重要！如果遺漏此步驟，編輯訂單時選擇新狀態會出現驗證錯誤。

#### 步驟 7：更新 Excel 對應 (選用)
如果需要支援 Excel 匯入匯出，在 `app/Services/ExcelFieldMapper.php` 的 `statusMappings` 中新增：
```php
'中文名稱1' => '新增狀態',
'新增狀態' => '新增狀態',  // 英文值直接對應
```

#### 步驟 8：執行 Migration 和代碼格式化
```bash
php artisan migrate
./vendor/bin/pint
```

---

## 注意事項

1. **ENUM 順序限制**: 修改 ENUM 時必須重新指定所有現有狀態，否則會導致資料遺失或錯誤。

2. **資料庫備份**: 在執行 migration 前務必備份資料庫。

3. **測試完整性**: 新增狀態後需確認所有顯示訂單狀態的前端頁面都已正確更新。

4. **前端的一致性**: 更新訂單列表顯示、訂單詳情、Excel 對應等所有相關功能。

5. **驗證規則完整性 ⚠️ 重要**: 必須同時更新：
   - `OrderController.php` 的 `store()` 和 `update()` 方法
   - `UpdateOrderRequest.php` 的 `rules()` 方法
   - 遺漏任一個會導致功能異常

6. **不要使用 replace_all**: 在 Edit 工具中使用 `replace_all` 選項時需要特別小心，避免誤改其他地方。

7. **編輯功能確認**: `edit.blade.php` 使用 `@include('orders.components.order-form')`，所以修改 `order-form.blade.php` 會自動更新編輯和新增頁面。

---

## 相關檔案位置清單

### 資料庫
- Migration 檔案：`database/migrations/`

### 前端樣板
- 訂單表單：`resources/views/orders/components/order-form.blade.php`
- 訂單列表：`resources/views/orders/components/order-table.blade.php`
- 訂單詳情：`resources/views/orders/components/order-detail.blade.php`
- 搜尋結果列表：`resources/views/orders/components/order-table-search.blade.php`
- 歷史訂單模態框：`resources/views/orders/components/history-modal.blade.php`
- 編輯頁面：`resources/views/orders/edit.blade.php`
- 新增頁面：`resources/views/orders/create.blade.php`

### 後端控制器/服務/驗證
- 訂單控制器：`app/Http/Controllers/OrderController.php`
- 訂單編輯驗證：`app/Http/Requests/UpdateOrderRequest.php` ⚠️
- Excel 欄位對應：`app/Services/ExcelFieldMapper.php`
- 訂單模型：`app/Models/Order.php`

---

## 常見問題 FAQ

### Q1: 為什麼要使用 ENUM 而不是關聯資料表？
**A**: 訂單狀態是固定且不常變動的數值，使用 ENUM 的優點：
- 查詢效能較好，不需要額外 JOIN
- 資料完整性更強
- 節省資料庫儲存空間，程式碼更簡潔

### Q2: 如何查詢特定狀態的訂單？
**A**: 使用 Eloquent ORM：
```php
Order::where('status', 'regular_sedans')->get();
```

### Q3: 如何在 Excel 匯入時對應狀態？
**A**: 在 Excel 檔案的狀態欄位中輸入中文名稱（如「一般車」）即可，系統會自動對應到 `regular_sedans`，詳見 `ExcelFieldMapper` 服務。

### Q4: 新增狀態會影響現有訂單嗎？
**A**: 不會。修改 ENUM 只是擴展允許的數值範圍，不會影響現有訂單的狀態值。

### Q5: 如何檢視資料庫中的 ENUM 值？
**A**: 執行 SQL 查詢：
```sql
SHOW COLUMNS FROM orders WHERE Field = 'status';
```

---

## 版本歷史記錄

| 日期 | 版本 | 狀態數量 | 新增的狀態 | 說明 |
|------|------|---------|---------|------|
| 2025-06-21 | 1.0 | 5 | open, assigned, bkorder, blocked, cancelled | 初始版本 |
| 2025-08-27 | 2.0 | 8 | cancelledOOC, cancelledNOC, cancelledCOTD | 細分取消狀態 |
| 2025-11-13 | 3.0 | 10 | blacklist, no_send | 新增黑名單和不派遣 |
| 2025-11-28 | 4.0 | 12 | regular_sedans, no_car | 新增一般車和沒車狀態 |

---

## 參考資源

- [CLAUDE.md](../CLAUDE.md) - 專案整體說明文件
- [Orders資料表完整說明.md](../Orders資料表完整說明.md) - 訂單資料表詳細說明
- [Laravel Eloquent 文件](https://laravel.com/docs/10.x/eloquent) - Eloquent ORM 官方文件
- [MySQL ENUM 型態](https://dev.mysql.com/doc/refman/8.0/en/enum.html) - MySQL 官方文件

---

**文件結束**
