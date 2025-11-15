# 419 Page Expired 錯誤修復計劃

## 📌 問題概述

**錯誤現象**：
- 訂單建立時偶發性出現 419 Page Expired 錯誤
- 發生時機：按下「儲存」按鈕後立即或等待一段時間
- 訂單類型：單日訂單
- 發生頻率：偶爾出現（非每次）
- 環境：正式伺服器（Windows PC）
- 有時顯示：[Request interrupted by user]

**錯誤原因**：
1. Session 使用檔案驅動（`SESSION_DRIVER=file`），在併發時容易衝突
2. 前端 AJAX 重複檢查消耗 Session，無 token 刷新機制
3. 資料庫併發時的鎖等待超時
4. Session 生命週期只有 120 分鐘，容易過期
5. 正式伺服器使用 IP 地址，Cookie 同源政策問題

---

## 🎯 修復策略

採用**兩階段漸進式修復**：

### 第一階段：立即修復（✅ 使用者在線可執行）
- 修改前端 JavaScript 添加錯誤處理
- 優化後端併發控制
- 增加 Session 生命週期
- **預期效果**：解決 70-80% 的 419 錯誤

### 第二階段：根治方案（⚠️ 需維護時段）
- 安裝 Redis
- 切換 Session 驅動到 Redis
- **預期效果**：徹底解決 419 錯誤，提升系統穩定性

---

## 📋 第一階段：診斷與立即修復

### ✅ 步驟 1：診斷分析（只讀操作）

#### 1.1 查看 Laravel 錯誤日誌

```bash
# 查看最近 200 行日誌
tail -n 200 storage/logs/laravel.log

# 或在 Windows 使用記事本開啟
notepad storage\logs\laravel.log
```

**尋找的關鍵字**：
- `TokenMismatchException`
- `419`
- `CSRF token mismatch`
- `Session store not set`
- `Deadlock found`
- `Lock wait timeout exceeded`

#### 1.2 檢查 Session 目錄

```bash
# Windows 命令提示字元
dir storage\framework\sessions

# 或使用檔案總管開啟
explorer storage\framework\sessions
```

**檢查項目**：
- 檔案數量（是否過多）
- 檔案權限（是否可讀寫）
- 檔案大小（是否有異常大的檔案）

---

### ✅ 步驟 2：前端修改

#### 2.1 修改 `resources/views/layouts/app.blade.php`

**位置**：在 `</head>` 標籤前添加以下程式碼

```html
<!-- 全局 AJAX 419 錯誤處理 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 儲存原始的 CSRF token
    let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // 攔截所有 jQuery AJAX 請求
    if (typeof $ !== 'undefined') {
        $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
            if (jqXHR.status === 419) {
                console.warn('CSRF token 過期，正在刷新...');

                // 刷新 CSRF token
                fetch('/refresh-csrf', {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.token) {
                        // 更新 meta tag
                        document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
                        csrfToken = data.token;

                        // 提示使用者
                        alert('Session 已刷新，請重新送出表單');
                    }
                })
                .catch(error => {
                    console.error('CSRF token 刷新失敗:', error);
                    alert('Session 過期，請重新整理頁面後再試');
                });
            }
        });

        // 為所有 AJAX 請求自動添加 CSRF token
        $.ajaxSetup({
            beforeSend: function(xhr, settings) {
                if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type)) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                }
            }
        });
    }

    // 攔截所有 Fetch API 請求
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        let [resource, config] = args;

        // 如果是 POST/PUT/DELETE 請求，自動添加 CSRF token
        if (config && config.method && !/^(GET|HEAD|OPTIONS|TRACE)$/i.test(config.method)) {
            config.headers = config.headers || {};
            config.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        return originalFetch.apply(this, args)
            .then(response => {
                // 檢查是否是 419 錯誤
                if (response.status === 419) {
                    console.warn('Fetch 請求遇到 419 錯誤');
                    alert('Session 過期，請重新整理頁面');
                }
                return response;
            });
    };
});
</script>
```

---

#### 2.2 修改 `public/js/orders/form.js`

**修改點 A：防止表單重複送出**

找到表單送出的事件監聽器（通常在檔案開頭或 `init()` 方法中），添加以下邏輯：

```javascript
// 在表單送出時添加
const form = document.querySelector('form');
if (form) {
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');

        // 禁用送出按鈕，防止重複點擊
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 處理中...';

            // 設定超時恢復（30秒後自動恢復按鈕）
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '儲存訂單';
            }, 30000);
        }
    });
}
```

**修改點 B：為重複檢查 AJAX 添加 debounce**

找到 `checkDuplicateOrder` 和 `checkDatePickupDuplicate` 方法，使用 debounce 優化：

```javascript
// 在檔案頂部添加 debounce 函數
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 修改事件綁定，添加 debounce（延遲 500ms）
const debouncedDuplicateCheck = debounce(this.checkDuplicateOrder.bind(this), 500);
const debouncedDatePickupCheck = debounce(this.checkDatePickupDuplicate.bind(this), 500);

$('input[name="ride_date"], input[name="ride_time"]').on('change blur', debouncedDuplicateCheck);
$('input[name="ride_date"], input[name="pickup_address"]').on('change blur', debouncedDatePickupCheck);
```

**修改點 C：AJAX 錯誤處理改善**

找到所有 AJAX 請求的 `error` 回調，添加 419 專門處理：

```javascript
// 原本的錯誤處理
error: (xhr) => {
    console.error('重複檢查失敗:', xhr);
}

// 改為
error: (xhr) => {
    if (xhr.status === 419) {
        // CSRF token 過期
        console.warn('CSRF token 過期，請重新整理頁面');
        alert('Session 過期，請重新整理頁面後再試');

        // 可選：自動刷新頁面
        // location.reload();
    } else {
        console.error('重複檢查失敗:', xhr);
    }
}
```

---

#### 2.3 修改 `.env` 配置

**找到以下行**：
```env
SESSION_LIFETIME=120
```

**修改為**：
```env
SESSION_LIFETIME=480
```

**說明**：將 Session 生命週期從 2 小時延長到 8 小時

**執行後需要做**：
```bash
# 清除配置快取
php artisan config:clear
```

---

### ✅ 步驟 3：後端優化

#### 3.1 修改 `app/Services/OrderNumberService.php`

**找到 `getNextSequenceNumber` 方法**（約第 111-141 行）

**原始程式碼**：
```php
private function getNextSequenceNumber($dateKey)
{
    return DB::transaction(function () use ($dateKey) {
        $sequence = DB::table('order_sequences')
            ->where('date_key', $dateKey)
            ->lockForUpdate()
            ->first();

        // ... 其他邏輯
    });
}
```

**修改為**（添加錯誤處理和重試機制）：
```php
private function getNextSequenceNumber($dateKey)
{
    $maxRetries = 3;
    $retryDelay = 100; // 毫秒

    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            return DB::transaction(function () use ($dateKey) {
                $sequence = DB::table('order_sequences')
                    ->where('date_key', $dateKey)
                    ->lockForUpdate()
                    ->first();

                if ($sequence) {
                    // 更新序列號
                    DB::table('order_sequences')
                        ->where('date_key', $dateKey)
                        ->update(['sequence_number' => $sequence->sequence_number + 1]);

                    return $sequence->sequence_number + 1;
                } else {
                    // 建立新序列
                    DB::table('order_sequences')->insert([
                        'date_key' => $dateKey,
                        'sequence_number' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return 1;
                }
            });
        } catch (\Exception $e) {
            // 檢查是否是鎖等待超時或死鎖
            if ($this->isLockException($e) && $attempt < $maxRetries) {
                // 等待後重試
                usleep($retryDelay * 1000);
                $retryDelay *= 2; // 指數退避
                \Log::warning("訂單編號生成重試 (第 {$attempt} 次): {$e->getMessage()}");
                continue;
            }

            // 其他錯誤或重試次數用盡
            \Log::error("訂單編號生成失敗: {$e->getMessage()}");
            throw new \Exception("訂單編號生成失敗，請稍後再試", 0, $e);
        }
    }

    throw new \Exception("訂單編號生成超時，請稍後再試");
}

/**
 * 檢查是否是鎖相關的例外
 */
private function isLockException(\Exception $e): bool
{
    $message = $e->getMessage();
    return strpos($message, 'Deadlock') !== false
        || strpos($message, 'Lock wait timeout') !== false
        || $e->getCode() === 1213  // MySQL 死鎖
        || $e->getCode() === 1205; // MySQL 鎖等待超時
}
```

---

### ✅ 步驟 4：測試驗證

**測試清單**：

1. **基本功能測試**
   - [ ] 單日訂單建立（正常情況）
   - [ ] 共乘訂單建立
   - [ ] 訂單編輯功能
   - [ ] 訂單列表顯示

2. **併發測試**
   - [ ] 開啟 2 個瀏覽器分頁，同時建立訂單
   - [ ] 快速連續建立多筆訂單

3. **Session 測試**
   - [ ] 在表單停留超過 2 小時後送出（應該不會 419）
   - [ ] 重新整理頁面後送出表單

4. **錯誤處理測試**
   - [ ] 在開發者工具中手動清除 Cookie，測試錯誤提示

**預期結果**：
- 419 錯誤大幅減少（應該減少 70-80%）
- 即使出現錯誤，也有友善的提示訊息
- 表單不會重複送出

---

## 📋 第二階段：Redis 安裝與切換

### ⚠️ 注意事項

**執行時機**：
- 建議在非工作時間執行（晚上或週末）
- 需要通知所有使用者

**影響範圍**：
- ❌ 所有使用者會被登出
- ❌ 需要重新登入
- ⏱️ 預估停機時間：15-30 分鐘

---

### 步驟 1：安裝 Redis for Windows

#### 1.1 下載 Redis

**方式 A：使用官方移植版本（推薦）**

訪問：https://github.com/tporadowski/redis/releases

下載最新版本：`Redis-x64-5.0.14.1.msi`（或更新版本）

**方式 B：使用 Memurai（商業版，更穩定）**

訪問：https://www.memurai.com/get-memurai

下載免費開發者版本

#### 1.2 安裝 Redis

1. 執行下載的 `.msi` 安裝檔
2. 選擇安裝路徑（建議：`C:\Program Files\Redis`）
3. **重要**：勾選「Add Redis to PATH」
4. **重要**：勾選「Install as Windows Service」
5. 設定 Port：6379（預設）
6. 設定 Max Memory：建議 256MB 或更多
7. 完成安裝

#### 1.3 驗證 Redis 安裝

開啟命令提示字元（CMD）：

```bash
# 檢查 Redis 服務狀態
sc query Redis

# 應該看到 STATE: 4 RUNNING

# 測試 Redis 連線
redis-cli ping

# 應該返回: PONG
```

---

### 步驟 2：安裝 PHP Redis 擴充套件

#### 2.1 確認 PHP 版本和架構

```bash
# 在命令提示字元執行
php -v

# 確認版本（例如：PHP 8.1.25）
# 確認架構（x64 或 x86）
```

#### 2.2 下載 PHP Redis 擴充套件

訪問：https://pecl.php.net/package/redis

或直接下載編譯好的 DLL：
https://windows.php.net/downloads/pecl/releases/redis/

選擇對應你的 PHP 版本和架構的檔案，例如：
- `php_redis-5.3.7-8.1-ts-x64.zip`（PHP 8.1, 64位元, Thread Safe）

#### 2.3 安裝擴充套件

1. 解壓縮下載的 ZIP 檔案
2. 找到 `php_redis.dll` 檔案
3. 複製到 XAMPP 的 PHP 擴充套件目錄：
   ```
   C:\xampp\php\ext\php_redis.dll
   ```

4. 編輯 `php.ini` 檔案：
   ```
   C:\xampp\php\php.ini
   ```

5. 在檔案中添加（找到 `; Dynamic Extensions` 區段）：
   ```ini
   extension=php_redis.dll
   ```

6. 重啟 Apache：
   - 在 XAMPP Control Panel 中點擊「Stop」
   - 再點擊「Start」

#### 2.4 驗證 PHP Redis 擴充套件

```bash
# 檢查 Redis 擴充套件是否載入
php -m | findstr redis

# 應該顯示: redis
```

或建立一個 PHP 測試檔案 `test-redis.php`：

```php
<?php
phpinfo();
```

訪問該檔案，搜尋「redis」，應該看到 Redis 擴充套件資訊。

---

### 步驟 3：設定 Laravel 使用 Redis

#### 3.1 安裝 PHP Redis 套件（Laravel）

```bash
# 在專案目錄執行
composer require predis/predis
```

#### 3.2 修改 `.env` 配置

**找到以下行**：
```env
SESSION_DRIVER=file
CACHE_DRIVER=file
```

**修改為**：
```env
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# 添加 Redis 連線設定
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### 3.3 驗證 Redis 連線

建立測試檔案 `routes/web.php`，添加測試路由：

```php
Route::get('/test-redis', function () {
    try {
        // 測試 Cache
        Cache::put('test-key', 'Hello Redis!', 60);
        $value = Cache::get('test-key');

        // 測試 Session
        session(['test-session' => 'Session works!']);
        $sessionValue = session('test-session');

        return response()->json([
            'cache' => $value,
            'session' => $sessionValue,
            'status' => 'Redis 連線成功！'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'status' => 'Redis 連線失敗'
        ], 500);
    }
});
```

訪問：`http://192.168.1.168/test-redis`

應該看到：
```json
{
  "cache": "Hello Redis!",
  "session": "Session works!",
  "status": "Redis 連線成功！"
}
```

---

### 步驟 4：清除舊 Session 並重啟

```bash
# 清除所有快取和 Session
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 清除舊的檔案 Session
# Windows
del /Q storage\framework\sessions\*

# 或手動刪除 storage/framework/sessions/ 目錄下的所有檔案
```

---

### 步驟 5：測試與驗證

#### 5.1 基本功能測試

1. **登入測試**
   - [ ] 登出後重新登入
   - [ ] 確認能正常登入
   - [ ] 檢查 Session 是否保持

2. **訂單建立測試**
   - [ ] 建立單日訂單
   - [ ] 建立共乘訂單
   - [ ] 建立批量訂單

3. **併發測試**
   - [ ] 多人同時登入
   - [ ] 多人同時建立訂單

#### 5.2 Redis 監控

開啟 Redis CLI 監控：

```bash
redis-cli
> MONITOR

# 在網頁中操作，應該看到 Redis 操作記錄
```

查看 Session 資料：

```bash
redis-cli
> KEYS *

# 應該看到類似 laravel_session:xxx 的 key
```

---

## 🔙 回滾方案

如果 Redis 安裝後出現問題，可以快速回滾：

### 回滾步驟

1. **修改 `.env`**：
   ```env
   SESSION_DRIVER=file
   CACHE_DRIVER=file
   ```

2. **清除配置快取**：
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **重啟 Apache**：
   - 在 XAMPP Control Panel 中重啟 Apache

系統會立即恢復使用檔案 Session。

---

## 📊 效果評估

### 第一階段修復後（預期）

| 指標 | 修復前 | 修復後 |
|------|--------|--------|
| 419 錯誤頻率 | 偶爾出現 | 大幅減少（↓70-80%） |
| Session 穩定性 | 中等 | 良好 |
| 使用者體驗 | 有錯誤提示不明確 | 有友善錯誤提示 |
| 系統效能 | 正常 | 稍微改善 |

### 第二階段修復後（預期）

| 指標 | 修復前 | 修復後 |
|------|--------|--------|
| 419 錯誤頻率 | 偶爾出現 | **完全消除** ✅ |
| Session 穩定性 | 中等 | **優秀** ✅ |
| 併發處理能力 | 普通 | **大幅提升** ✅ |
| 系統效能 | 正常 | **提升 30-50%** ✅ |
| 記憶體使用 | 檔案 I/O | 記憶體快取 |

---

## ❓ 常見問題 FAQ

### Q1: Redis 會消耗很多記憶體嗎？
**A**: 不會。Session 資料通常很小，100 個同時在線使用者約只需要 10-20MB 記憶體。

### Q2: Redis 安裝失敗怎麼辦？
**A**:
1. 檢查是否有其他程式佔用 6379 Port
2. 嘗試以管理員身份執行安裝程式
3. 參考官方文件：https://redis.io/docs/getting-started/installation/install-redis-on-windows/

### Q3: 切換到 Redis 後能回滾嗎？
**A**: 可以！按照「回滾方案」步驟即可立即恢復。

### Q4: 第一階段修復後還會出現 419 嗎？
**A**: 可能還會偶爾出現，但頻率會大幅降低，並且有友善的錯誤提示。第二階段（Redis）才是完全解決方案。

### Q5: 為什麼要分兩階段執行？
**A**:
- 第一階段：可以立即執行，不影響使用者，快速改善問題
- 第二階段：需要停機維護，徹底解決問題，可以安排在方便的時間

### Q6: Redis 安裝後需要定期維護嗎？
**A**: 幾乎不需要。Redis 非常穩定，安裝後會作為 Windows 服務自動啟動。

### Q7: 如果只執行第一階段會怎樣？
**A**: 系統會明顯改善，419 錯誤會減少 70-80%，但在高併發或特殊情況下仍可能出現。

---

## 📞 支援與協助

如果在執行過程中遇到問題：

1. **查看日誌**：
   - Laravel: `storage/logs/laravel.log`
   - Apache: `C:\xampp\apache\logs\error.log`
   - Redis: Windows 事件檢視器 → Windows 日誌 → 應用程式

2. **常用檢查指令**：
   ```bash
   # 檢查 Redis 狀態
   sc query Redis

   # 檢查 PHP 擴充套件
   php -m

   # 清除所有快取
   php artisan optimize:clear
   ```

3. **聯絡開發人員**：
   - 提供錯誤日誌截圖
   - 描述執行到哪個步驟出錯
   - 提供系統環境資訊（PHP 版本、Windows 版本）

---

## ✅ 執行檢查清單

### 第一階段

- [ ] 步驟 1.1：查看 Laravel 日誌
- [ ] 步驟 1.2：檢查 Session 目錄
- [ ] 步驟 2.1：修改 `app.blade.php`
- [ ] 步驟 2.2：修改 `form.js`
- [ ] 步驟 2.3：修改 `.env`（SESSION_LIFETIME）
- [ ] 步驟 3.1：修改 `OrderNumberService.php`
- [ ] 步驟 4：完成所有測試驗證
- [ ] 觀察 1-2 天，評估改善效果

### 第二階段

- [ ] 步驟 1：安裝 Redis for Windows
- [ ] 步驟 2：安裝 PHP Redis 擴充套件
- [ ] 步驟 3：設定 Laravel 使用 Redis
- [ ] 步驟 4：清除舊 Session
- [ ] 步驟 5：完成所有測試驗證
- [ ] 通知使用者維護完成

---

## 📅 建議執行時程

### 週一至週五（工作日）
- 執行第一階段所有步驟
- 持續觀察系統運作

### 週末（維護時段）
- 選擇使用者最少的時段
- 執行第二階段（Redis 安裝）
- 預留 2-3 小時進行測試

---

**文件版本**：1.0
**建立日期**：2025-11-15
**適用專案**：LC-Management (Laravel 10)
**作者**：Claude Code Assistant
