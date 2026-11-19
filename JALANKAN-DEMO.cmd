@echo off
setlocal
cd /d "%~dp0"
set "DEMO_PHP=php"
if exist "..\work\php83\php.exe" set "DEMO_PHP=%~dp0..\work\php83\php.exe"
"%DEMO_PHP%" -r "exit(PHP_VERSION_ID >= 80200 ? 0 : 1);"
if errorlevel 1 (
  echo Diperlukan PHP 8.2 atau lebih baru. Lihat README.md.
  pause
  exit /b 1
)
if not exist "vendor\autoload.php" (
  echo Jalankan composer install dahulu. Lihat README.md.
  pause
  exit /b 1
)
if not exist ".env" (
  copy ".env.example" ".env" >nul
  "%DEMO_PHP%" artisan key:generate
)
echo Buka http://127.0.0.1:8017/login di browser setelah server aktif.
"%DEMO_PHP%" artisan serve --host=127.0.0.1 --port=8017
pause
