# Docker 使用指南

## 快速開始

### 1. 環境需求
- Docker Desktop
- Docker Compose

### 2. 啟動開發環境

#### 🚀 一鍵啟動（推薦）
```bash
# 啟動完整開發環境（包含前端熱重載）
./start-dev.sh

# 停止開發環境
./stop-dev.sh
```

#### 手動啟動
```bash
# 啟動所有容器
./vendor/bin/sail up -d

# 停止所有容器
./vendor/bin/sail down

# 檢查容器狀態
./vendor/bin/sail ps
```

### 3. 常用指令

#### Laravel Artisan 指令
```bash
# 執行 artisan 指令
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan tinker
./vendor/bin/sail artisan queue:work

# 清除快取
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
```

#### 前端開發
```bash
# 安裝 npm 依賴
./vendor/bin/sail npm install

# 啟動開發服務器
./vendor/bin/sail npm run dev

# 建構生產版本
./vendor/bin/sail npm run build
```

#### 資料庫操作
```bash
# 執行遷移
./vendor/bin/sail artisan migrate

# 回滾遷移
./vendor/bin/sail artisan migrate:rollback

# 重新建立資料庫
./vendor/bin/sail artisan migrate:fresh --seed
```

#### Composer 指令
```bash
# 安裝 PHP 依賴
./vendor/bin/sail composer install

# 更新依賴
./vendor/bin/sail composer update

# 添加新包
./vendor/bin/sail composer require package-name
```

## 服務訪問

### 主要服務
- **應用程式**: http://localhost
- **Mailpit (郵件測試)**: http://localhost:8025
- **phpMyAdmin (資料庫管理)**: http://localhost:8080
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

### 資料庫連線資訊
- **Host**: mysql (容器內) / localhost (本機)
- **Port**: 3306
- **Database**: laravel
- **Username**: sail
- **Password**: password

### phpMyAdmin 登入資訊
訪問 http://localhost:8080 並使用以下資訊登入：
- **伺服器**: mysql
- **使用者名稱**: sail
- **密碼**: password

## 開發工作流程

### 1. 日常開發

#### 🚀 使用一鍵啟動腳本（推薦）
```bash
# 啟動完整開發環境
./start-dev.sh

# 腳本會自動：
# - 啟動 Docker 容器
# - 安裝/更新依賴
# - 執行資料庫遷移
# - 啟動前端開發服務器（npm run dev）
# - 顯示所有服務的訪問地址

# 停止開發環境
./stop-dev.sh
```

#### 手動啟動
```bash
# 啟動環境
./vendor/bin/sail up -d

# 啟動前端開發服務器
./vendor/bin/sail npm run dev

# 在瀏覽器中訪問 http://localhost
```

### 2. 資料庫變更
```bash
# 建立新的遷移
./vendor/bin/sail artisan make:migration create_new_table

# 執行遷移
./vendor/bin/sail artisan migrate
```

### 3. 停止開發環境
```bash
./vendor/bin/sail down
```

## 故障排除

### 常見問題

#### 1. 容器無法啟動
```bash
# 檢查日誌
./vendor/bin/sail logs

# 重新建構容器
./vendor/bin/sail build --no-cache
```

#### 2. 權限問題
```bash
# 修復權限
sudo chown -R $USER:$USER .
./vendor/bin/sail artisan storage:link
```

#### 3. 資料庫連接問題
- 確保 `.env` 檔案中 `DB_HOST=mysql`
- 重新啟動容器：`./vendor/bin/sail restart`

#### 4. 前端資產無法載入
```bash
# 重新安裝依賴
./vendor/bin/sail npm install

# 重新建構
./vendor/bin/sail npm run build
```

## 自動啟動功能

### 一鍵啟動腳本功能
`start-dev.sh` 腳本提供以下自動化功能：

1. **環境檢查**：檢查 Docker 是否運行
2. **容器啟動**：啟動所有 Docker 容器
3. **依賴安裝**：自動安裝 Composer 和 npm 依賴
4. **資料庫遷移**：執行資料庫遷移
5. **APP_KEY 生成**：如果不存在則自動生成
6. **Storage Link**：創建 storage 符號連結
7. **前端服務器**：自動啟動 `npm run dev`

### 使用方式
```bash
# 啟動完整開發環境
./start-dev.sh

# 停止開發環境
./stop-dev.sh
```

### 自動啟動的服務
- **Laravel 應用**：http://localhost
- **前端開發服務器**：http://localhost:5173（熱重載）
- **Mailpit**：http://localhost:8025
- **phpMyAdmin**：http://localhost:8080 
- **MySQL**：localhost:3306
- **Redis**：localhost:6379

## 快捷指令別名

可以將以下別名加入 `~/.bashrc` 或 `~/.zshrc`：

```bash
# Laravel Sail 別名
alias sail='./vendor/bin/sail'

# 開發環境快捷指令
alias dev-start='./start-dev.sh'
alias dev-stop='./stop-dev.sh'

# 常用指令
alias sail-up='./vendor/bin/sail up -d'
alias sail-down='./vendor/bin/sail down'
alias sail-ps='./vendor/bin/sail ps'
alias sail-logs='./vendor/bin/sail logs'
```

設定別名後，可以直接使用：
```bash
dev-start         # 啟動開發環境
dev-stop          # 停止開發環境
sail artisan migrate
sail npm run build
```

## 生產環境部署

生產環境建議使用專門的 Docker 配置，而不是 Laravel Sail。可以考慮：

1. 使用 Docker Compose 生產配置
2. 使用容器編排工具（如 Kubernetes）
3. 使用雲端服務（如 AWS ECS、Google Cloud Run）

## 備份與恢復

### 資料庫備份
```bash
# 備份資料庫
./vendor/bin/sail exec mysql mysqldump -u sail -p lc_management > backup.sql

# 恢復資料庫
./vendor/bin/sail exec mysql mysql -u sail -p lc_management < backup.sql
```

### 完整備份
```bash
# 備份 volumes
docker volume ls
docker run --rm -v lc-management_sail-mysql:/data -v $(pwd):/backup alpine tar czf /backup/mysql-backup.tar.gz /data
```