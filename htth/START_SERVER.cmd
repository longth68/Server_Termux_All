@echo off
setlocal
chcp 65001 >nul
title HTTH Localhost Game Server
cd /d "%~dp0"

where java.exe >nul 2>nul
if errorlevel 1 (
    echo Loi: Chua tim thay Java 17 tro len.
    echo Hay cai Java va mo lai file nay.
    pause
    exit /b 1
)

netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul
if errorlevel 1 (
    echo Loi: MySQL chua chay tren 127.0.0.1:3306.
    echo Hay khoi dong MySQL va chay IMPORT_DATABASE.cmd neu chua nhap du lieu.
    pause
    exit /b 1
)

netstat -ano | findstr /R /C:"127.0.0.1:2236 .*LISTENING" >nul
if not errorlevel 1 (
    echo Game server localhost da dang chay tren 127.0.0.1:2236.
    pause
    exit /b 0
)

echo Khoi dong Hai Tac Ti Hon tai 127.0.0.1:2236...
java -server -Xms512M -Xmx1024M -jar server.jar
echo.
echo Game server da dung.
pause
