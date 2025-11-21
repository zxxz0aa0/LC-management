@echo off
chcp 65001 >nul
REM ============================================
REM LC-management 互動式手動備份工具
REM ============================================

setlocal EnableDelayedExpansion

:MENU
cls
echo ============================================
echo LC-management 資料庫手動備份工具
echo ============================================
echo.
echo 請選擇備份類型：
echo.
echo [1] 完整備份（推薦，含壓縮）
echo [2] 完整備份（不壓縮）
echo [3] 僅備份關鍵表（orders, customers, drivers）
echo [4] 僅備份資料庫結構（無資料）
echo [5] 查看現有備份
echo [6] 執行備份監控檢查
echo [0] 退出
echo.
echo ============================================
set /p choice="請輸入選項 (0-6): "

if "%choice%"=="1" goto BACKUP_FULL_COMPRESS
if "%choice%"=="2" goto BACKUP_FULL
if "%choice%"=="3" goto BACKUP_TABLES
if "%choice%"=="4" goto BACKUP_SCHEMA
if "%choice%"=="5" goto VIEW_BACKUPS
if "%choice%"=="6" goto MONITOR
if "%choice%"=="0" goto END
goto MENU

:BACKUP_FULL_COMPRESS
cls
echo ============================================
echo 執行完整備份（含壓縮）
echo ============================================
echo.
cd /d "C:\xampp\htdocs\LC-management"
php artisan db:backup --type=full --compress --output=storage/backups/manual
echo.
if %ERRORLEVEL% equ 0 (
    echo [✓] 備份成功完成！
) else (
    echo [✗] 備份失敗！
)
echo.
pause
goto MENU

:BACKUP_FULL
cls
echo ============================================
echo 執行完整備份（不壓縮）
echo ============================================
echo.
cd /d "C:\xampp\htdocs\LC-management"
php artisan db:backup --type=full --output=storage/backups/manual
echo.
if %ERRORLEVEL% equ 0 (
    echo [✓] 備份成功完成！
) else (
    echo [✗] 備份失敗！
)
echo.
pause
goto MENU

:BACKUP_TABLES
cls
echo ============================================
echo 執行關鍵表備份
echo ============================================
echo.
cd /d "C:\xampp\htdocs\LC-management"
php artisan db:backup --type=tables --tables=orders,order_sequences,customers,drivers --compress --output=storage/backups/manual
echo.
if %ERRORLEVEL% equ 0 (
    echo [✓] 關鍵表備份成功完成！
) else (
    echo [✗] 備份失敗！
)
echo.
pause
goto MENU

:BACKUP_SCHEMA
cls
echo ============================================
echo 執行資料庫結構備份（無資料）
echo ============================================
echo.
cd /d "C:\xampp\htdocs\LC-management"
php artisan db:backup --type=schema --output=storage/backups/schema
echo.
if %ERRORLEVEL% equ 0 (
    echo [✓] 結構備份成功完成！
) else (
    echo [✗] 備份失敗！
)
echo.
pause
goto MENU

:VIEW_BACKUPS
cls
echo ============================================
echo 現有備份檔案列表
echo ============================================
echo.
cd /d "C:\xampp\htdocs\LC-management"
echo [手動備份目錄]
dir /o-d storage\backups\manual 2>nul | findstr /v "個目錄" | findstr /v "位元組"
echo.
echo [每日自動備份目錄]
dir /o-d storage\backups\daily 2>nul | findstr /v "個目錄" | findstr /v "位元組"
echo.
echo [關鍵表備份目錄]
dir /o-d storage\backups\critical 2>nul | findstr /v "個目錄" | findstr /v "位元組"
echo.
pause
goto MENU

:MONITOR
cls
echo ============================================
echo 執行備份狀態監控
echo ============================================
echo.
cd /d "C:\xampp\htdocs\LC-management"
php artisan db:monitor-backups --report
echo.
pause
goto MENU

:END
echo.
echo 感謝使用！
exit /b 0
