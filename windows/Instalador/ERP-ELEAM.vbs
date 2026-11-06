Option Explicit

' Lanza el ERP ELEAM sin mostrar una ventana de consola.
Dim shell, fso, aqui, script
Set shell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

aqui = fso.GetParentFolderName(WScript.ScriptFullName)
script = aqui & "\app\windows\Instalador\ERP-ELEAM.ps1"

If Not fso.FileExists(script) Then
    MsgBox "No se encontro ERP-ELEAM.ps1. Reinstala la aplicacion.", 16, "ERP ELEAM"
    WScript.Quit 1
End If

shell.Run "powershell.exe -NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & script & """", 0, False
