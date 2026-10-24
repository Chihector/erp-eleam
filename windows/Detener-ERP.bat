@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Detener-ERP.ps1" %*
if errorlevel 1 (
    echo.
    echo No se pudo detener el ERP. Revisa el mensaje anterior.
    pause
)
endlocal
