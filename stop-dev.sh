#!/bin/bash

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🛑 停止 LC Management 開發環境...${NC}"

# 停止前端開發服務器
echo -e "${YELLOW}🎨 停止前端開發服務器...${NC}"
pkill -f "npm run dev" || true
pkill -f "vite" || true

# 停止 Docker 容器
echo -e "${YELLOW}📦 停止 Docker 容器...${NC}"
./vendor/bin/sail down

echo -e "${GREEN}✅ 開發環境已停止${NC}"
echo -e "${GREEN}💡 下次使用 './start-dev.sh' 重新啟動${NC}"