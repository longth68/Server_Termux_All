@echo off
setlocal
chcp 65001 >nul
title Stop HTTH Localhost Server

set "FOUND=0"
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /R /C:"127.0.0.1:2236 .*LISTENING"') do (
    if not "%%P"=="0" (
        set "FOUND=1"
        echo Dang tat game server PID %%P...
        taskkill /PID %%P /T /F >nul 2>nul
    )
)

if "%FOUND%"=="0" echo Game server localhost hien khong chay.
timeout /t 2 /nobreak >nul
exit /b 0
