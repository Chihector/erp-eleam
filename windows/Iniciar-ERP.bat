@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Iniciar-ERP.ps1" %*
if errorlevel 1 (
    echo.
    echo No se pudo iniciar el ERP. Revisa el mensaje anterior.
    pause
)
endlocal
