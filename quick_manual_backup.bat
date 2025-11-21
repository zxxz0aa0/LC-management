@echo off
REM ============================================
REM LC-management 快速手動備份腳本
REM 雙擊此檔案即可執行手動備份
REM ============================================

echo ============================================
echo LC-management 資料庫手動備份工具
echo ============================================
echo.

REM 切換到專案目錄
cd /d "C:\xampp\htdocs\LC-management"

echo [1/2] 正在執行完整備份（含壓縮）...
echo.

REM 執行完整備份到 manual 目錄
php artisan db:backup --type=full --compress --output=storage/backups/manual

if %ERRORLEVEL% neq 0 (
    echo.
    echo [錯誤] 備份失敗！
    echo 請檢查：
    echo 1. MySQL 是否正在運行
    echo 2. 資料庫連線設定是否正確（.env 檔案）
    echo 3. storage/backups/manual 目錄是否可寫入
    echo.
    pause
    exit /b 1
)

echo.
echo ============================================
echo [成功] 手動備份完成！
echo ============================================
echo.
echo 備份檔案位置：storage\backups\manual\
echo.
echo 此備份將永久保存，不會被自動清理。
echo.

REM 開啟備份目錄
explorer "C:\xampp\htdocs\LC-management\storage\backups\manual"

pause
