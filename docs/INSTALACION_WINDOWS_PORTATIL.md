# Instalación portable del ERP en Windows

> **Nota (2026-11-04):** para el uso diario se recomienda el instalador de
> escritorio [`ERP-ELEAM-Setup-0.2.0.exe`](INSTALACION_WINDOWS.md), que separa
> programa y datos, incluye bandeja del sistema, respaldos automáticos y
> actualizador. El paquete portable de esta guía sigue siendo un **preview**
> sin actualizador automático.

El paquete portable incluye el código de la aplicación, sus dependencias PHP y
un runtime de PHP para Windows. En el PC de uso no se necesitan Composer, Node
ni una instalación previa de PHP. El paquete utiliza SQLite y guarda los datos
en la misma carpeta donde se instala; no es una instalación multiusuario ni un
servidor para internet.

El ZIP `preview` se probó mediante una extracción limpia en el mismo equipo de
desarrollo. Antes de distribuirlo, falta probar la instalación en otro PC
Windows limpio y verificar allí el runtime requerido.

## Preparar el paquete

En el PC de desarrollo, con PHP 8.2+, Composer, Node.js y npm instalados, abre
PowerShell en la carpeta del proyecto y ejecuta:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File windows\Preparar-ERP-Portatil.ps1
```

El resultado queda en `dist\ERP-ELEAM-portable-preview.zip`. El paquete no
incluye el `.env`, la base SQLite del desarrollador, dependencias de pruebas ni
credenciales locales. El script comprueba que el PHP que incluye pueda iniciar
y tenga las extensiones requeridas.

## Instalar y usar

1. Copia el ZIP al PC de destino y extráelo en una carpeta donde tu usuario
   pueda escribir, por ejemplo `Documentos\ERP ELEAM`. No lo instales en
   `Archivos de programa`.
2. Abre la carpeta extraída y ejecuta `windows\Iniciar-ERP.bat`.
3. En el primer inicio, ingresa correo, nombre, apellido y RUT del
   administrador. El instalador crea la base, aplica las migraciones y genera
   una contraseña aleatoria; guárdala cuando aparezca en la consola.
4. El navegador abre el panel en `http://127.0.0.1:8000/admin`. Para detener el
   servidor, ejecuta `windows\Detener-ERP.bat`.

La instalación nueva configura `APP_ENV=production` y `APP_DEBUG=false`, y no
crea usuarios de demostración.

Si Windows indica que falta Microsoft Visual C++ Runtime, instala el
Redistributable x64 2015–2022 desde Microsoft y vuelve a iniciar el ERP.

## Datos y límites de esta versión

- La base queda en `database\database.sqlite`; el `.env`, archivos adjuntos y
  registros quedan en la carpeta extraída.
- Para hacer una copia manual, detén el ERP y copia la carpeta completa a un
  medio seguro. Comprueba periódicamente que puedes recuperar esa copia.
- La versión portable todavía no tiene un actualizador automático. No
  sobrescribas una instalación con otro ZIP hasta tener una copia de seguridad
  y un procedimiento de actualización probado.
- El servidor solo escucha en `127.0.0.1`; esta versión es para uso en ese PC.
  No la expongas a la red local ni a internet. Para varios equipos, sigue la
  futura etapa de despliegue PostgreSQL por HTTPS.
- Esta prueba de instalación no certifica los cálculos laborales ni autoriza a
  usar remuneraciones en pagos oficiales sin revisión profesional.
