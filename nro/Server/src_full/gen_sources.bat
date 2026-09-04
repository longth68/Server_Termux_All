@echo off
cd /d "%~dp0"
if exist sources.txt del sources.txt
pushd src
for /r %%f in (*.java) do echo %%f >> ..\sources.txt
popd
