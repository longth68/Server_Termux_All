@echo off
setlocal
chcp 65001 >nul
title Import HTTH Database
cd /d "%~dp0"

set "MYSQL_EXE="
for /f "delims=" %%I in ('where mysql.exe 2^>nul') do if not defined MYSQL_EXE set "MYSQL_EXE=%%I"
if not defined MYSQL_EXE if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"

if not defined MYSQL_EXE (
    echo Khong tim thay mysql.exe.
    echo Hay cai MySQL/MariaDB hoac them thu muc bin cua MySQL vao PATH.
    pause
    exit /b 1
)

netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul
if errorlevel 1 (
    echo MySQL chua chay tren cong 3306.
    pause
    exit /b 1
)

echo Dang nhap database day du tu database\htth_full.sql...
"%MYSQL_EXE%" -h 127.0.0.1 -u root < "database\htth_full.sql"
if errorlevel 1 (
    echo Import that bai. Kiem tra tai khoan root va mat khau MySQL.
    pause
    exit /b 1
)

echo Import database htth thanh cong.
pause
