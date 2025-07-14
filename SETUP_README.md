# 🚀 LC Management 專案設置指南

## 新電腦快速設置

### 1. 前置需求
- **Docker Desktop** - [下載安裝](https://www.docker.com/products/docker-desktop/)
- **Git** - [下載安裝](https://git-scm.com/downloads)

### 2. 一鍵設置
```bash
# 1. Clone 專案
git clone <你的專案倉庫URL>
cd LC-management

# 2. 一鍵啟動（會自動處理所有設置）
./start-dev.sh
```

### 3. 腳本會自動處理
- ✅ 檢查 Docker 是否運行
- ✅ 複製 `.env.example` 到 `.env`
- ✅ 啟動 Docker 容器
- ✅ 安裝 Composer 依賴
- ✅ 安裝 npm 依賴
- ✅ 執行資料庫遷移
- ✅ 生成 APP_KEY
- ✅ 創建 storage link
- ✅ 啟動前端開發服務器

### 4. 完成後訪問
- **應用程式**: http://localhost
- **phpMyAdmin**: http://localhost:8080
- **Mailpit**: http://localhost:8025
- **前端開發服務器**: http://localhost:5173

### 5. 停止開發環境
```bash
./stop-dev.sh
```

## 🔧 如果遇到問題

### 問題 1：Docker 未運行
**解決方案**：啟動 Docker Desktop 應用程式

### 問題 2：權限問題
**解決方案**：
```bash
# macOS/Linux
chmod +x start-dev.sh stop-dev.sh

# Windows (Git Bash)
git update-index --chmod=+x start-dev.sh stop-dev.sh
```

### 問題 3：端口被佔用
**解決方案**：修改 `.env` 文件中的端口設置
```env
APP_PORT=8000      # 改為其他端口
VITE_PORT=5174     # 改為其他端口
FORWARD_DB_PORT=3307        # 改為其他端口
FORWARD_PHPMYADMIN_PORT=8081 # 改為其他端口
```

### 問題 4：資料庫連接失敗
**解決方案**：
```bash
# 重新啟動容器
./vendor/bin/sail down
./start-dev.sh
```

## 📁 重要文件說明

- `start-dev.sh` - 一鍵啟動開發環境
- `stop-dev.sh` - 一鍵停止開發環境
- `docker-compose.yml` - Docker 服務配置
- `.env` - 環境變數配置
- `DOCKER_USAGE.md` - 詳細使用說明

## 💡 開發團隊協作

### 1. 新成員加入
新成員只需要：
1. 安裝 Docker Desktop
2. Clone 專案
3. 執行 `./start-dev.sh`

### 2. 更新專案
```bash
# 拉取最新代碼
git pull

# 重新啟動開發環境
./stop-dev.sh
./start-dev.sh
```

### 3. 環境變數管理
- 將 `.env.example` 提交到 Git
- 不要提交 `.env` 文件（已在 .gitignore 中）
- 如有新的環境變數，更新 `.env.example`

## 🌟 優點

- **一鍵設置**：新電腦 5 分鐘內可開始開發
- **環境一致**：Docker 確保所有人環境相同
- **自動化**：腳本處理所有複雜設置
- **易於維護**：更新 Docker 配置，所有人同步

## 📞 需要幫助？

查看 `DOCKER_USAGE.md` 獲取更多詳細指令和故障排除。