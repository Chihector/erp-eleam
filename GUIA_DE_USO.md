# 📘 Guía de Uso del Sistema - ERP ELEAM

Esta guía explica paso a paso cómo utilizar cada uno de los módulos del **ERP ELEAM** para la administración del personal, control de turnos, asistencia, vacaciones y cálculo de liquidaciones de sueldo con formato PDF.

La interfaz, la portada, los mensajes de validación y las funciones estándar del panel están configurados en español.

> **Importante sobre cálculos laborales:** las liquidaciones, licencias y
> finiquitos son cálculos de apoyo implementados con reglas simplificadas. Antes
> de pagar, firmar o presentar documentos, valida los parámetros del período y
> los resultados con el responsable laboral/contable del establecimiento.

---

## 🔑 1. Acceso al Sistema

1. **Iniciar el servidor local:**
   Abre una terminal en la carpeta del proyecto y ejecuta:
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
2. **Ingresar desde el navegador:**
   - **En este PC:** **[http://localhost:8000/admin](http://localhost:8000/admin)**
   - **En tu teléfono celular (misma red Wi-Fi):** **`http://192.168.1.122:8000/admin`**
   - La portada de `http://localhost:8000/` también está en español y ofrece un acceso directo al panel.
3. **Inicio de sesión híbrido (Correo O RUT):**
   Puedes ingresar tanto con tu correo electrónico como con tu RUT chileno (con o sin puntos/guion).
4. **Verificación de contraseña (botón del ojito):**
   A la derecha del campo de contraseña encontrarás el botón del ojito para comprobar visualmente que la clave esté bien escrita antes de pulsar **Entrar al Sistema**.
5. **Roles y permisos (control de acceso):**
   El sistema reconoce cuatro roles, asignados a cada usuario. Solo quien tenga un rol asignado puede entrar al panel:
   - **Administrador:** acceso total (puede crear, editar y borrar cualquier registro), incluidos **Parámetros Legales** y **Usuarios**.
   - **Jefe de Turno:** gestiona **Turnos**, **Asistencias** y **Vacaciones**; consulta el resto de los módulos, salvo Configuración.
   - **Contador:** gestiona **Gastos**, **Ingresos** y **Liquidaciones**; consulta el resto de los módulos, salvo Configuración.
   - **Solo Lectura:** únicamente consulta la información, sin poder modificar nada y sin acceso a Configuración.
   El sistema aplica estos permisos automáticamente según el rol; no requieres configurar nada más.

6. **Menú lateral simple y ordenado:**
   El menú de la izquierda está agrupado en **4 secciones colapsables** para que encuentres todo más rápido (haz clic en el título de la sección para plegarla o desplegarla):
   - **Operación:** Turnos, Asistencias, Vacaciones y Licencias Médicas (el día a día).
   - **Personal:** Trabajadores y Contratos.
   - **Finanzas:** Ingresos, Gastos, Liquidaciones y Finiquitos.
   - **Configuración:** Parámetros Legales y Usuarios *(solo visible para el Administrador)*.

**Creación de la cuenta de acceso:**

El proyecto ya no usa contraseñas fijas en los seeders. En local, `RoleSeeder`
crea las cuentas demo con contraseñas aleatorias y las imprime una sola vez en
la consola; fuera de local no crea usuarios demo. Para crear o restablecer un
administrador con una clave nueva:

```bash
php artisan eleam:admin-crear admin@eleam.cl
```

El comando muestra la contraseña una sola vez. Guárdala en un gestor de
contraseñas y no la publiques ni la escribas en documentación. Si necesitas una
cuenta de Jefe de Turno, créala desde **Usuarios** con el rol correspondiente.

> **Seguridad:** la cuenta de administrador es la única que puede ver
> **Parámetros Legales** y **Usuarios**. No compartas esas credenciales ni dejes
> cuentas de prueba con claves conocidas en una red compartida.

### 🔎 Búsqueda inteligente (sin tildes)
En cualquier selector o buscador del sistema puedes escribir con o sin tildes, sin importar mayúsculas; el sistema encuentra el resultado igual:
- `hector` encuentra a **Héctor**, `munoz` a **Muñoz** y `gonzalez` a **González**.
- El **RUT** se encuentra con o sin puntos y guion: `19486694`, `19.486.694-1` o `19486694-1`.
- Funciona en los selectores de trabajador (Turnos, Asistencias, Contratos, Vacaciones, Licencias Médicas, Finiquitos y Liquidaciones) y en el buscador de todas las tablas.

### Idioma de la aplicación
- La configuración inicial del proyecto establece `APP_LOCALE=es` y `APP_FALLBACK_LOCALE=es`.
- Si ya tienes un archivo `.env`, asegúrate de que esos dos valores estén en `es`. Laravel lee la configuración del `.env` local.
- Los mensajes de autenticación, validación y paginación tienen traducción al español.

---

## 👥 2. Módulo de Trabajadores y Contratos

Para que un colaborador pueda tener turnos asignados o recibir su liquidación de sueldo, debe estar registrado con su respectivo contrato.

### A. Registrar un Trabajador
1. En el menú lateral, haz clic en **Trabajadores** > **Nuevo Trabajador**.
2. Completa los datos:
   - **RUT:** Debe tener formato con guión (ej: `12345678-9`). El sistema valida automáticamente el dígito verificador.
   - **Nombres y Apellidos:** Datos personales del trabajador.
   - **Cargo:** Cargo desempeñado (ej: Cuidador(a), Enfermero(a), Terapeuta, etc.).
   - **AFP:** Nombre de la administradora (Capital, Cuprum, Habitat, Modelo, PlanVital, Provida, Uno).
   - **Tasa AFP (%):** Tasa propia del trabajador: 10 % obligatorio más la comisión vigente de su AFP. Si se deja vacía, el sistema usa la tasa de referencia configurada, pero lo recomendable es registrar la tasa real.
   - **Sistema de Salud:** Fonasa o Isapre.
   - **Plan Isapre (UF):** Obligatorio si el trabajador está en Isapre. Se cobra el plan contratado, con un mínimo legal equivalente al 7 % de la renta imponible.
   - **Fecha de Ingreso:** Fecha en la que comenzó a trabajar (no puede ser una fecha futura).
   - **Color de Calendario:** Elige un color identificador para visualizar rápidamente sus turnos en el calendario.
3. Haz clic en **Guardar**.

> Si falta la AFP, la tasa, el sistema de salud o el plan de Isapre, el sistema
> bloquea la liquidación de ese trabajador y muestra el dato faltante. Esto evita
> generar pagos con una previsión inventada o heredada de otra persona.

### B. Registrar un Contrato
1. Ve a **Contratos** > **Nuevo Contrato**.
2. Selecciona el **Trabajador**.
3. Ingresa el **Sueldo Base** en pesos chilenos (sin puntos ni decimales, ej: `550000`).
4. Selecciona el **Tipo de Contrato** (Indefinido, Plazo Fijo, Honorarios).
5. Ingresa las horas semanales y la fecha de inicio.
6. Haz clic en **Guardar**.

---

## 📅 3. Módulo de Turnos y Calendario de Escritorio

El sistema incluye un planificador interactivo que previene automáticamente errores humanos y cruces de horarios.

> ℹ️ **Nota de permisos:** Solo los roles **Jefe de Turno** (y **Administrador**) pueden programar o modificar turnos. Los demás roles únicamente consultan.

### A. Programar un Turno
1. En el menú, ve a **Turnos** > **Nuevo Turno**.
2. Selecciona el **Trabajador** (escribe su nombre con o sin tildes, o su RUT: ver *🔎 Búsqueda inteligente* en la sección 1).
3. Selecciona el **Tipo de Turno**:
   - **Día (08:00 a 20:00):** El sistema asigna automáticamente el horario de **8 a 8** (de 08:00 a 20:00 hrs del mismo día).
   - **Noche (20:00 a 08:00):** El sistema asigna automáticamente de **20:00 hrs a 08:00 hrs del día siguiente**.
   - **Cuarto Turno:** Jornada de rotación.
   - **Día Libre / Vacaciones / Licencia Médica.**
4. **Fecha de inicio:** Elige el día del turno. La hora se mantendrá coordinada según el tipo de turno seleccionado.
5. **Prevención de choques de turnos:** Si intentas asignar un turno en un horario donde el trabajador ya tiene otro turno programado, el sistema te avisará con una alerta y no permitirá guardar el cruce.
6. Haz clic en **Guardar**.

### B. Visualizar el Calendario en el Escritorio (Dashboard)
- En la página principal (**Dashboard**), encontrarás el calendario interactivo (**FullCalendar**).
- Cada turno se muestra en el día correspondiente priorizando el **primer nombre e inicial** al frente:
  👉 **`Hector M. (08:00-20:00)`**
- **Optimizado para celulares:** En pantallas de teléfonos, el texto realiza un salto de línea automático adaptativo dentro del cuadro del día para que el nombre del trabajador nunca quede cortado ni tapado.
- Cada bloque tiene el color específico asignado al trabajador en su ficha para identificarlo fácilmente a simple vista.
- **Vistas conmutables:** Puedes alternar entre **Mes**, **Agenda** (lista vertical tipo cronograma) o **Día**.

---

## ⏱️ 4. Control de Asistencia (Reloj Control)

Permite contrastar las marcas reales con los turnos programados.
1. Ve a **Asistencias** > **Nueva Asistencia**.
2. Selecciona el trabajador y turno correspondiente.
3. Registra la hora exacta de entrada y la hora de salida.
4. El sistema registra el total de horas cumplidas.

---

## 🏖️ 5. Módulo de Vacaciones

El sistema calcula de manera legal y automática los días disponibles según la ley laboral chilena (1.25 días hábiles ganados por mes trabajado desde su fecha de ingreso).

1. Ve a **Vacaciones** > **Nueva Vacación**.
2. Selecciona al trabajador: el sistema te informará en pantalla su **Saldo Disponible** en días hábiles.
3. Ingresa la fecha de inicio y término, y la cantidad de días hábiles solicitados.
4. Define el estado (*Pendiente*, *Aprobada* o *Rechazada*).
5. Solo las solicitudes en estado **Aprobada** descuentan el saldo acumulado; las pendientes y rechazadas no cuentan.

---

## 💰 6. Módulo de Finanzas (Caja, Ingresos y Gastos)

Permite llevar el control del flujo de efectivo del establecimiento.

> ℹ️ **Nota de permisos:** Solo los roles **Contador** (y **Administrador**) pueden registrar y gestionar ingresos y gastos. Los demás roles únicamente consultan.

### A. Registrar Gastos
1. Ve a **Gastos** > **Nuevo Gasto**.
2. Ingresa la descripción, categoría (Alimentación, Farmacia/Medicamentos, Insumos médicos, Servicios básicos, etc.).
3. Monto y fecha del egreso.
4. Puedes adjuntar el comprobante o boleta/factura digital (PDF, JPG o PNG de
   hasta 5 MB). Se guarda en almacenamiento privado y se descarga desde la fila
   del gasto con **"Ver comprobante"**.

### B. Registrar Ingresos
1. Ve a **Ingresos** > **Nuevo Ingreso**.
2. Registra mensualidades de residentes, donaciones o subsidios recibidos.

### C. Estadísticas en el Dashboard
- En el Dashboard verás widgets en tiempo real (con una pequeña demora por la caché de estadísticas, que se actualiza cada 60 segundos) con:
  - **Dinero Disponible (Caja real):** Total Ingresos - Total Gastos.
  - **Ingresos del Mes en curso.**
  - **Gastos del Mes en curso.**

---

## 📄 7. Módulo de Remuneraciones y Liquidaciones de Sueldo (PDF)

El sistema calcula las liquidaciones con reglas de apoyo (gratificación legal,
topes imponibles, AFP y salud por trabajador, seguro de cesantía, SIS
informativo del empleador e impuesto único de segunda categoría). La salud usa
el tope imponible de 60 UF para el mínimo legal de Fonasa/Isapre. **No
reemplaza la revisión laboral/contable.**

> **Límites actuales:** los días trabajados pueden calcularse como mes completo
> menos licencias médicas no rechazadas, según asistencias registradas o
> ingresarse manualmente. Las licencias **Pendientes** también descuentan días.
> Antes de pagar, revisa esos días, la tabla del IUSC, la salud Isapre y los
> resultados con el responsable del ELEAM.

### A. Actualizar y verificar los parámetros legales del período
1. Entra como **Administrador** y ve a **Configuración > Parámetros Legales**.
2. Pulsa **"Actualizar desde mindicador.cl"**.
3. Elige **Mes** y **Año**. Opcionalmente, indica el **sueldo mínimo** si deseas
   reemplazar el valor configurado para esa fecha de vigencia.
4. El sistema consulta la UF y la UTM, guarda el origen, la fecha y deja el
   período como **SIN VERIFICAR**.
5. Revisa la UF, UTM, sueldo mínimo, topes y porcentaje SIS contra la normativa
   vigente. Si todo coincide, pulsa **"Verificar"**: queda registrado el usuario
   y la fecha de revisión.
6. Si más adelante se actualiza un valor y cambia, la verificación se borra y
   debe hacerse de nuevo.

> **Importante:** mindicador.cl es una fuente externa de referencia para UF y
> UTM. Los topes previsionales, el SIS y el sueldo mínimo no se validan
> automáticamente contra la ley; deben confirmarse con fuentes oficiales.

### B. Generar Liquidaciones Mensuales (1 solo clic)
1. En el menú, ve a **Liquidaciones**.
2. Haz clic en el botón superior azul **"Generar Liquidación Mensual"**.
3. Se abrirá una ventana donde puedes elegir:
   - **Mes:** Selecciona el mes a liquidar (ej: Octubre).
   - **Año:** Año correspondiente (ej: 2026).
   - **Días Trabajados:** elige **mes completo menos licencias** (predeterminado), **según asistencias registradas** o **ingreso manual**. En modo manual, usa 30 para un mes completo.
   - **Trabajador (Opcional):** Déjalo en blanco para **generar la liquidación de todos los trabajadores con contrato a la vez**, o elige a un trabajador puntual.
4. Haz clic en **Generar**.
5. El sistema procesará los cálculos y creará las liquidaciones al instante. Si
   a un trabajador le falta AFP, tasa, salud o plan de Isapre, se informa el
   error de ese caso y se continúa con los demás.

### C. Descargar la Liquidación en PDF
1. En la tabla de **Liquidaciones**, ubica la fila del trabajador.
2. Haz clic en el botón verde **"Descargar PDF"** (con icono de documento).
3. Se abrirá inmediatamente una nueva pestaña con el documento en PDF con:
   - Datos del empleador y del trabajador.
   - Detalle de **Haberes Imponibles** (Sueldo base y Gratificación legal).
   - Detalle de **Haberes No Imponibles** (Movilización, Colación, Cargas familiares).
   - Desglose de **Descuentos Legales**: AFP con la tasa del trabajador, Salud
     (Fonasa 7 % o el plan Isapre), Seguro de Cesantía e Impuesto único de
     segunda categoría.
   - **SIS del empleador** como línea informativa: no se descuenta del líquido.
   - Monto final del **Sueldo Líquido a Pagar** en números y texto.
   - Registro de los turnos cumplidos en el período.
   - Líneas de firma formal del Empleador y del Trabajador con su RUT.
4. Puedes imprimirlo o descargarlo directamente a tu computadora para firma y archivo.

### D. Cerrar y reabrir una liquidación
El cierre convierte la liquidación en un documento inmutable: no se puede editar
ni recalcular. Solo un **Administrador** puede reabrirla, dejando usuario, fecha
y motivo registrados.

1. Verifica que los parámetros legales del período estén marcados como
   verificados.
2. En la fila de la liquidación, pulsa **"Cerrar"** y confirma. Si los
   parámetros cambiaron después del cálculo, el sistema rechazará el cierre y
   pedirá recalcular.
3. Para corregir un error, un administrador pulsa **"Reabrir"**, escribe el
   motivo (mínimo 10 caracteres) y confirma.
4. Con la liquidación reabierta, corrige los datos y vuelve a generarla o
   editarla. El cierre anterior y el motivo de la reapertura quedan en la
   auditoría.

### E. Descargar el Libro de Remuneraciones Digital (Dirección del Trabajo)
1. En el menú, ve a **Liquidaciones**.
2. Haz clic en el botón superior **"Libro de Remuneraciones DT"** (icono de descarga).
3. Elige el **Mes** y **Año** del período que quieres exportar.
4. Pulsa **Descargar CSV**.
5. Se descargará un archivo `libro-remuneraciones-MM-AAAA.csv` que puedes abrir directamente en Excel con una fila por trabajador:
   - Identificación (RUT, nombres, apellidos, cargo, tipo de contrato, fecha de ingreso).
   - Haberes (Sueldo base, Gratificación, Movilización, Colación, Cargas familiares y totales).
   - Descuentos legales (AFP, Salud, Seguro de Cesantía) y otros descuentos.
   - **Sueldo Líquido** pagado en el período.

> ℹ️ Este libro es una **versión simplificada de respaldo y revisión** de las remuneraciones del mes, exportable a Excel. Sirve para auditoría interna y como base de la información que se reporta a la Dirección del Trabajo.

---

## 📱 8. Cómo Usar y Probar el Sistema desde tu Teléfono Celular

El ERP ELEAM está optimizado para funcionar de forma fluida y cómoda en teléfonos móviles (smartphones) y tablets.

### A. Mejoras Móviles Incluidas
- **Pantalla de Inicio de Sesión táctil:** Botón principal *"Entrar al Sistema"* azul vibrante de 48px de alto y botón del ojito con contraste para ver/ocultar la contraseña con facilidad desde el celular.
- **Ingreso con Correo O RUT:** Puedes iniciar sesión escribiendo tu RUT (ej: `19486694-1`) o tu correo electrónico.
- **Calendario con nombres legibles:** En la vista mensual, los eventos colocan al inicio el **primer nombre e inicial** del trabajador (`Hector M. (08:00-20:00)`) con salto de línea adaptativo para que nunca se oculte quién toma el turno.
- **Vista de "Agenda" alternativa:** En celulares puedes pulsar el botón **"Agenda"** para ver un listado vertical limpio y detallado de los turnos de la semana.
- **Tablas compactas y responsivas:** Las tablas muestran los datos esenciales (Trabajador con RUT, Horario o Sueldo Líquido y Botón PDF) sin desbordarse hacia los lados.
- **Formularios adaptados al dedo:** Todos los campos se organizan en 1 columna vertical fácil de rellenar desde el móvil.
- **Descarga y visualización directa de PDF:** El archivo PDF de las liquidaciones se puede abrir o descargar directamente en el visor de tu teléfono para enviarlo por WhatsApp o correo.

### B. Cómo conectarte desde tu Celular en la misma red Wi-Fi
Para que tu teléfono pueda ver la aplicación que corre en este computador:

1. **Asegúrate de que ambos equipos estén en la misma red Wi-Fi:**
   Tu PC y tu celular deben estar conectados al mismo router o red Wi-Fi.

2. **Inicia el servidor en tu PC con el comando:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   > ℹ️ **Nota clave:** Usar `--host=0.0.0.0` es imprescindible, ya que por defecto Laravel solo responde a peticiones locales de este computador. Al poner `0.0.0.0`, permite que tu teléfono se conecte a través de la red local.

3. **Abre el navegador en tu teléfono (Chrome, Safari, Firefox, etc.):**
   Ingresa a la siguiente dirección IP de este PC:
   👉 **`http://192.168.1.122:8000/admin`**

4. **Si Windows Defender te pregunta:**
   Si aparece una ventana de Firewall de Windows pidiendo confirmación para PHP en redes privadas, pulsa **"Permitir acceso"**.

5. **Inicia sesión con una cuenta activa:** por correo o RUT. En local, las cuentas demo se crean con contraseñas aleatorias que el seeder muestra una sola vez. Para el administrador, restablece la clave con `php artisan eleam:admin-crear admin@eleam.cl` siguiendo la [guía local](docs/PRUEBA_LOCAL.md). No expongas cuentas con claves conocidas a la red.

---

## 🩺 9. Módulo de Licencias Médicas (con Cálculo de Subsidio)

Permite registrar las licencias médicas del personal y **calcula automáticamente el subsidio** que paga Fonasa, Isapre o la Mutual, según la normativa chilena.

### A. Registrar una Licencia Médica
1. En el menú, ve a **Licencias Médicas** (dentro del grupo *Operación*).
2. Haz clic en **"Nueva Licencia Médica"**.
3. Completa:
   - **Trabajador:** Búscalo por RUT o nombre.
   - **N° de Folio:** El número que trae el documento (opcional).
   - **Tipo de Licencia:**
     - *Común*: 75% del sueldo, con tope de 60 UF y **3 días de carencia** (los 3 primeros días no se pagan).
     - *Maternal*: 100% del sueldo, sin carencia.
     - *Accidente del Trabajo / Enfermedad Profesional*: 100% del sueldo, sin carencia (Mutual).
   - **Entidad Emisora:** Fonasa, Isapre o Mutual de Seguridad.
   - **Fechas de inicio y término del reposo.**
4. Al guardar, el sistema calcula **solo**:
   - Los **días de reposo** (corridos, ambos días inclusive).
   - Los **días pagados** (descontando la carencia cuando corresponde).
   - El **monto del subsidio** en pesos.

### B. Revisar y Actualizar
- En la tabla ves de un vistazo: tipo, fechas, días, subsidio y estado (**Pendiente / Validada / Rechazada**).
- Puedes filtrar por estado o tipo de licencia.
- Si editas las fechas o el tipo, **el subsidio se recalcula automáticamente** al guardar.
- Marca la licencia como **Validada** cuando Fonasa/Isapre la apruebe, o **Rechazada** si fue objetada.

> 💡 **Consejo:** el cálculo usa el sueldo base del contrato del trabajador y los parámetros legales (UF, sueldo mínimo) del mes de inicio de la licencia.

---

## 📕 10. Módulo de Finiquitos (con PDF)

Permite calcular el finiquito de un trabajador al terminar su contrato, **con todas las indemnizaciones legales**, y descargarlo en PDF para la firma.

### A. Calcular un Finiquito
1. En el menú, ve a **Finiquitos** (dentro del grupo *Finanzas*).
2. Haz clic en el botón superior **"Calcular Finiquito"**.
3. Completa:
   - **Trabajador.**
   - **Causal del Término:** por ejemplo *Necesidades de la Empresa (Art. 161)* o *Renuncia Voluntaria*.
   - **Fecha de Término.**
4. El sistema calcula automáticamente:
   - **Indemnización por Años de Servicio** (solo causal Art. 161): 30 días de sueldo por año, con **tope de 11 años** y sueldo tope de **90 UF**.
   - **Indemnización sustitutiva del Aviso Previo:** 1 mes de sueldo (solo Art. 161).
   - **Vacaciones Pendientes:** saldo legal no gozado valorizado por día.
   - **Gratificación Proporcional** a los meses trabajados del año.
   - El **Líquido a Pagar** final.

### B. Descargar el Finiquito en PDF
1. En la tabla de **Finiquitos**, pulsa **"Descargar PDF"** en la fila del trabajador.
2. Se abrirá el documento formal con:
   - Datos del trabajador, causal y fecha de término.
   - Antigüedad (años y meses) y sueldo de cálculo.
   - Detalle de haberes (indemnizaciones, vacaciones, gratificación).
   - Descuentos y **Líquido a Pagar**.
   - Texto legal y **líneas de firma** del empleador y del trabajador.
3. Imprímelo para la firma ante notario o ministro de fe.

### C. Ajustes Manuales
- Puedes editar cualquier finiquito generado (botón *Editar*) para corregir montos, agregar **otros descuentos** (anticipos, préstamos) o dejar observaciones.
- Al guardar, el **Líquido a Pagar se recalcula solo** (Total Haberes − Descuentos).
- Cambia el estado a **Firmado** una vez que el trabajador lo firme, para dejar constancia.

---

## ⚡ 11. Consejos de Rendimiento y Mantenimiento

Si en algún momento notas que el sistema tarda en cargar o deseas reiniciar la información:

1. **Reiniciar el servidor local:**
   Si modificaste configuraciones en el archivo `.env`, en la terminal presiona `Ctrl + C` y vuelve a iniciar con `php artisan serve --host=0.0.0.0 --port=8000`.
2. **Reoptimizar la memoria caché de Filament:**
   Ejecuta en la terminal:
   ```bash
   php artisan filament:optimize
   ```
3. **Reiniciar la base de datos limpia con datos iniciales:**
   Si deseas borrar los datos de prueba y comenzar desde cero:
   ```bash
   php artisan migrate:fresh --seed
   ```
4. **Ejecutar las pruebas del sistema:**
   Para verificar que todas las reglas de negocio y cálculos matemáticos funcionen al 100%:
   ```bash
   php artisan test
   ```

---

## 🔔 12. Dashboard, Notificaciones y Respaldos

### A. Dashboard operativo
Al entrar a **`/admin`** verás un panel con:
- **Estadísticas generales:** trabajadores, contratos por vencer, licencias activas, finiquitos pendientes y vacaciones.
- **Gastos vs. ingresos:** gráfico de los últimos 12 meses.
- **Actividad reciente:** últimos movimientos de liquidaciones, licencias, finiquitos, vacaciones, gastos e ingresos.
- **Próximos turnos:** turnos programados para los próximos 14 días.

Las tarjetas y tablas se actualizan automáticamente. Si un dato no aparece, revisa que el módulo correspondiente tenga registros.

### B. Notificaciones diarias
El sistema puede enviar avisos por correo y dejarlos en la base de datos:
- Contratos que vencen en **30, 15, 7, 3 o 1 día**.
- Trabajadores con más de **10 días** de vacaciones pendientes (con alerta especial sobre 15 días).
- Licencias médicas que vencen en **7 días** o que siguen abiertas después de su fecha de término.

Para ejecutarlo manualmente:
```bash
php artisan notificaciones:diarias
php artisan notificaciones:diarias --dry-run
php artisan notificaciones:diarias --solo=contratos
```
El envío real requiere configurar el correo en `.env` (`MAIL_MAILER`, `MAIL_HOST`, etc.). En local, `MAIL_MAILER=log` escribe los correos en el log en vez de enviarlos. Las notificaciones se envían en forma inmediata; no requieren un worker de colas.

### C. Reportes PDF de notificaciones
Genera un reporte con contratos, vacaciones, licencias y notificaciones enviadas:
```bash
php artisan reporte:notificaciones --periodo=semanal
php artisan reporte:notificaciones --periodo=mensual --guardar
php artisan reporte:notificaciones --periodo=semanal --email
```
El PDF se guarda en `storage/app/reportes/`. La opción `--email` envía el reporte a los usuarios con rol **administrador**; requiere correo configurado.

### D. Respaldo a Google Drive
El respaldo incluye la base SQLite, `storage/` y una copia del `.env` **sin secretos**. La base se copia con `VACUUM INTO`, así que incluye las transacciones que todavía están en el WAL. Para configurarlo:

> ⚠️ **Solo SQLite:** si el sistema usa PostgreSQL, este comando no respalda la
> base de datos y falla de forma explícita al pedir `--all` o `--database`.
> En ese caso usa `deploy/respaldar.sh` o `pg_dump` y copia el respaldo a un
> destino externo.
1. Crea una cuenta de servicio en Google Cloud y descarga su archivo JSON.
2. Guarda el JSON fuera de Git; por ejemplo, en la raíz como `google-drive-credentials.json` (ya está en `.gitignore`).
3. Crea una carpeta en Google Drive y copia su ID.
4. Configura en `.env`:
   ```dotenv
   GOOGLE_DRIVE_CREDENTIALS_PATH=google-drive-credentials.json
   GOOGLE_DRIVE_BACKUP_FOLDER_ID=ID_DE_LA_CARPETA
   GOOGLE_DRIVE_BACKUP_RETENTION=30
   ```
5. Prueba sin subir nada:
   ```bash
   php artisan backup:google-drive --all --dry-run
   ```
6. Ejecuta el respaldo real:
   ```bash
   php artisan backup:google-drive --all
   ```

Si las credenciales o la carpeta no están configuradas, la tarea programada **se omite**; no se ejecuta un respaldo a medias.

> **Seguridad:** nunca subas el archivo JSON de credenciales ni el `.env` real al repositorio. El respaldo sube una versión sanitizada del `.env`, pero aun así revisa los permisos de la carpeta de Drive.

### E. Tareas programadas y mantenimiento
El scheduler ejecuta:
- **08:00:** notificaciones diarias.
- **02:00:** respaldo a Google Drive (si está configurado).
- **Domingo 03:00:** limpieza de logs antiguos.
- **Día 1, 04:00:** optimización de la base de datos.
- **Lunes 07:00:** reporte semanal.
- **Día 1, 06:00:** reporte mensual.

En local, déjalo corriendo en una terminal aparte:
```bash
php artisan schedule:work
```
En producción, el instalador de servidor crea `/etc/cron.d/eleam` con el scheduler y el respaldo diario. Si instalaste de otra forma, configura el cron de Laravel (`php artisan schedule:run` cada minuto). También puedes ejecutar las mantenciones a mano:
```bash
php artisan log:clear
php artisan db:optimize
```
