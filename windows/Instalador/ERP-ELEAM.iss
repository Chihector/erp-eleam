; ============================================================================
; ERP ELEAM - Instalador para Windows (Inno Setup 6)
; ============================================================================
; Compilar con windows\Instalador\Preparar-Instalador.ps1, que genera el
; paquete de aplicacion y pasa las definiciones AppVersion, SourceDir,
; OutputDir e IconFile.
; ============================================================================

#ifndef AppVersion
  #define AppVersion "0.2.0"
#endif

#ifndef SourceDir
  #define SourceDir "..\..\dist\instalador-app"
#endif

#ifndef OutputDir
  #define OutputDir "..\..\dist"
#endif

#ifndef IconFile
  #define IconFile "ERP-ELEAM.ico"
#endif

#define AppName "ERP ELEAM"
#define AppPublisher "ERP ELEAM"
#define AppExeName "ERP-ELEAM.vbs"

[Setup]
AppId={{8E5F0C1A-5A7B-4C2D-9E3F-1A2B3C4D5E6F}
AppName={#AppName}
AppVersion={#AppVersion}
AppVerName={#AppName} {#AppVersion}
AppPublisher={#AppPublisher}
DefaultDirName={localappdata}\Programs\ERP-ELEAM
DefaultGroupName={#AppName}
DisableDirPage=yes
DisableProgramGroupPage=yes
PrivilegesRequired=lowest
OutputDir={#OutputDir}
OutputBaseFilename=ERP-ELEAM-Setup-{#AppVersion}
SetupIconFile={#IconFile}
UninstallDisplayIcon={app}\ERP-ELEAM.ico
Compression=lzma2/max
SolidCompression=yes
WizardStyle=modern
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
MinVersion=10.0
CloseApplications=no
RestartApplications=no
UsePreviousAppDir=yes
VersionInfoVersion={#AppVersion}
VersionInfoProductName={#AppName}
VersionInfoDescription=Instalador de ERP ELEAM
VersionInfoCompany={#AppPublisher}

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "desktopicon"; Description: "Crear un acceso directo en el escritorio"; GroupDescription: "Accesos directos:"

[Files]
Source: "{#SourceDir}\*"; DestDir: "{app}\app"; Flags: ignoreversion recursesubdirs createallsubdirs
Source: "{#IconFile}"; DestDir: "{app}"; DestName: "ERP-ELEAM.ico"; Flags: ignoreversion
Source: "ERP-ELEAM.vbs"; DestDir: "{app}"; Flags: ignoreversion
Source: "Actualizar-ERP.ps1"; DestDir: "{app}"; Flags: ignoreversion
Source: "Actualizar-ERP.cmd"; DestDir: "{app}"; Flags: ignoreversion
Source: "Desinstalar-ERP.ps1"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\ERP ELEAM"; Filename: "{sys}\wscript.exe"; Parameters: """{app}\ERP-ELEAM.vbs"""; WorkingDir: "{app}"; IconFilename: "{app}\ERP-ELEAM.ico"
Name: "{group}\Actualizar ERP ELEAM"; Filename: "{app}\Actualizar-ERP.cmd"; WorkingDir: "{app}"; IconFilename: "{app}\ERP-ELEAM.ico"
Name: "{group}\Carpeta de datos"; Filename: "{sys}\explorer.exe"; Parameters: """{localappdata}\ERP-ELEAM\data"""
Name: "{group}\Desinstalar ERP ELEAM"; Filename: "{uninstallexe}"
Name: "{autodesktop}\ERP ELEAM"; Filename: "{sys}\wscript.exe"; Parameters: """{app}\ERP-ELEAM.vbs"""; WorkingDir: "{app}"; IconFilename: "{app}\ERP-ELEAM.ico"; Tasks: desktopicon

[Run]
Filename: "{sys}\wscript.exe"; Parameters: """{app}\ERP-ELEAM.vbs"""; Description: "Abrir ERP ELEAM"; Flags: nowait postinstall skipifsilent

[UninstallRun]
Filename: "{sys}\powershell.exe"; Parameters: "-NoProfile -ExecutionPolicy Bypass -File ""{app}\Desinstalar-ERP.ps1"" -AppDir ""{app}\app"" -DataDir ""{localappdata}\ERP-ELEAM\data"""; Flags: runhidden waituntilterminated; RunOnceId: "DetenerERP"

[Code]
procedure CurUninstallStepChanged(CurUninstallStep: TUninstallStep);
var
  Respuesta: Integer;
begin
  if CurUninstallStep = usPostUninstall then
  begin
    if not UninstallSilent then
    begin
      Respuesta := MsgBox(
        '¿Eliminar también la base de datos, los adjuntos y los respaldos?' + #13#10 + #13#10 +
        'Si eliges No, los datos se conservan en:' + #13#10 +
        ExpandConstant('{localappdata}\ERP-ELEAM\data'),
        mbConfirmation,
        MB_YESNO or MB_DEFBUTTON2
      );

      if Respuesta = IDYES then
      begin
        DelTree(ExpandConstant('{localappdata}\ERP-ELEAM\data'), True, True, True);
      end;
    end;
  end;
end;
