@echo off
setlocal
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Actualizar-ERP.ps1" %*
if errorlevel 1 (
    echo.
    echo La actualizacion no se completo. Revisa el mensaje anterior.
    pause
)
endlocal
