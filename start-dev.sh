#!/bin/bash

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 啟動 LC Management 開發環境...${NC}"

# 檢查 Docker 是否運行
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker 未運行，請先啟動 Docker Desktop${NC}"
    exit 1
fi

# 檢查並複製 .env 文件
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo -e "${YELLOW}📝 複製 .env.example 到 .env...${NC}"
        cp .env.example .env
    else
        echo -e "${RED}❌ 找不到 .env.example 文件${NC}"
        exit 1
    fi
fi

# 啟動 Docker 容器
echo -e "${YELLOW}📦 啟動 Docker 容器...${NC}"
./vendor/bin/sail up -d

# 等待容器啟動
echo -e "${YELLOW}⏳ 等待容器啟動...${NC}"
sleep 5

# 檢查容器狀態
if ! ./vendor/bin/sail ps | grep -q "Up"; then
    echo -e "${RED}❌ 容器啟動失敗${NC}"
    exit 1
fi

# 安裝/更新依賴
echo -e "${YELLOW}📦 檢查並安裝依賴...${NC}"
if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}📦 安裝 Composer 依賴...${NC}"
    ./vendor/bin/sail composer install --no-interaction
else
    echo -e "${GREEN}✅ Composer 依賴已存在${NC}"
fi

if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}📦 安裝 npm 依賴...${NC}"
    ./vendor/bin/sail npm install
else
    echo -e "${GREEN}✅ npm 依賴已存在${NC}"
fi

# 執行資料庫遷移
echo -e "${YELLOW}🗄️ 執行資料庫遷移...${NC}"
./vendor/bin/sail artisan migrate --force

# 生成 APP_KEY（如果不存在）
if grep -q "APP_KEY=$" .env; then
    echo -e "${YELLOW}🔑 生成 APP_KEY...${NC}"
    ./vendor/bin/sail artisan key:generate
fi

# 創建 storage link
echo -e "${YELLOW}🔗 創建 storage link...${NC}"
./vendor/bin/sail artisan storage:link

# 啟動前端開發服務器
echo -e "${YELLOW}🎨 啟動前端開發服務器...${NC}"
./vendor/bin/sail npm run dev &

# 等待前端服務器啟動
sleep 3

echo -e "${GREEN}✅ 開發環境啟動完成！${NC}"
echo -e "${GREEN}🌐 應用程式: http://localhost${NC}"
echo -e "${GREEN}🎨 前端熱重載: http://localhost:5173${NC}"
echo -e "${GREEN}📧 郵件測試: http://localhost:8025${NC}"
echo -e "${GREEN}🗄️ 資料庫: localhost:3306${NC}"
echo ""
echo -e "${YELLOW}💡 提示：${NC}"
echo -e "  • 按 Ctrl+C 停止前端服務器"
echo -e "  • 使用 './vendor/bin/sail down' 停止所有容器"
echo -e "  • 使用 './vendor/bin/sail logs' 查看日誌"
echo ""
echo -e "${GREEN}🎉 開發愉快！${NC}"

# 保持腳本運行，這樣前端服務器會持續運行
wait