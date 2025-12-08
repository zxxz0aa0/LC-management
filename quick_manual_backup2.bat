@echo off
cd /d "C:\xampp\htdocs\LC-management"

echo ============================================
echo LC-management 資料庫備份
echo ============================================
echo.

"C:\xampp\php\php.exe" artisan db:backup --type=full --compress --output=storage/backups/manual
if %ERRORLEVEL% neq 0 (
    echo.
    echo [錯誤] 備份失敗
    pause
    exit /b 1
)

explorer "C:\xampp\htdocs\LC-management\storage\backups\manual"
pause
