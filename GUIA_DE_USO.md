# 📘 Guía de Uso del Sistema - ERP ELEAM

Esta guía explica paso a paso cómo utilizar cada uno de los módulos del **ERP ELEAM** para la administración del personal, control de turnos, asistencia, vacaciones y cálculo de liquidaciones de sueldo con formato PDF.

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
3. **Inicio de sesión híbrido (Correo O RUT):**
   Puedes ingresar tanto con tu correo electrónico como con tu RUT chileno (con o sin puntos/guion).
4. **Verificación de contraseña (botón del ojito):**
   A la derecha del campo de contraseña encontrarás el botón del ojito para comprobar visualmente que la clave esté bien escrita antes de pulsar **Entrar al Sistema**.
5. **Roles y permisos (control de acceso):**
   El sistema reconoce cuatro roles, asignados a cada usuario. Solo quien tenga un rol asignado puede entrar al panel:
   - **Administrador:** acceso total (puede crear, editar y borrar cualquier registro).
   - **Jefe de Turno:** gestiona **Turnos**, **Asistencias** y **Vacaciones**; consulta el resto de los módulos.
   - **Contador:** gestiona **Gastos**, **Ingresos** y **Liquidaciones**; consulta el resto de los módulos.
   - **Solo Lectura:** únicamente consulta la información, sin poder modificar nada.
   El sistema aplica estos permisos automáticamente según el rol; no requieres configurar nada más.

**Credenciales disponibles (con sus roles asignados):**
| Usuario | Correo | RUT | Rol | Contraseña |
| :--- | :--- | :--- | :--- | :--- |
| **Héctor Contreras** | `hector@eleam.cl` | `19486694-1` | Jefe de Turno | `password` |
| **Administrador General** | `admin@eleam.cl` | `11111111-1` | Administrador | `password` |

---

## 👥 2. Módulo de Trabajadores y Contratos

Para que un colaborador pueda tener turnos asignados o recibir su liquidación de sueldo, debe estar registrado con su respectivo contrato.

### A. Registrar un Trabajador
1. En el menú lateral, haz clic en **Trabajadores** > **Nuevo Trabajador**.
2. Completa los datos:
   - **RUT:** Debe tener formato con guión (ej: `12345678-9`). El sistema valida automáticamente el dígito verificador.
   - **Nombres y Apellidos:** Datos personales del trabajador.
   - **Cargo:** Cargo desempeñado (ej: Cuidador(a), Enfermero(a), Terapeuta, etc.).
   - **AFP y Sistema de Salud:** Previsión a la que pertenece (Fonasa, Isapre, AFP Habitat, Modelo, etc.).
   - **Fecha de Ingreso:** Fecha en la que comenzó a trabajar (no puede ser una fecha futura).
   - **Color de Calendario:** Elige un color identificador para visualizar rápidamente sus turnos en el calendario.
3. Haz clic en **Guardar**.

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
2. Selecciona el **Trabajador**.
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
4. Puedes adjuntar el comprobante o boleta/factura digital.

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

El sistema calcula las liquidaciones con la normativa chilena oficial (Gratificación legal, topes imponibles, descuentos de AFP, Fonasa/Salud y Seguro de Cesantía).

### A. Generar Liquidaciones Mensuales (1 solo clic)
1. En el menú, ve a **Liquidaciones**.
2. Haz clic en el botón superior azul **"Generar Liquidación Mensual"**.
3. Se abrirá una ventana donde puedes elegir:
   - **Mes:** Selecciona el mes a liquidar (ej: Octubre).
   - **Año:** Año correspondiente (ej: 2026).
   - **Días Trabajados:** 30 días para mes completo (o ajusta si hubo licencias/ausencias).
   - **Trabajador (Opcional):** Déjalo en blanco para **generar la liquidación de todos los trabajadores con contrato a la vez**, o elige a un trabajador puntual.
4. Haz clic en **Generar**.
5. El sistema procesará los cálculos y creará las liquidaciones al instante.

### B. Descargar la Liquidación en PDF
1. En la tabla de **Liquidaciones**, ubica la fila del trabajador.
2. Haz clic en el botón verde **"Descargar PDF"** (con icono de documento).
3. Se abrirá inmediatamente una nueva pestaña con el documento oficial en PDF con:
   - Datos del empleador y del trabajador.
   - Detalle de **Haberes Imponibles** (Sueldo base y Gratificación legal).
   - Detalle de **Haberes No Imponibles** (Movilización, Colación, Cargas familiares).
   - Desglose de **Descuentos Legales** (Fondo AFP, Salud 7%, Seguro Cesantía).
   - Monto final del **Sueldo Líquido a Pagar** en números y texto.
   - Registro de los turnos cumplidos en el período.
   - Líneas de firma formal del Empleador y del Trabajador con su RUT.
4. Puedes imprimirlo o descargarlo directamente a tu computadora para firma y archivo.

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

5. **Inicia sesión con cualquiera de las cuentas activas:**
   - **Héctor Contreras:** RUT `19486694-1` (o `hector@eleam.cl`) / Rol: *Jefe de Turno* / Clave: `password`
   - **Administrador General:** RUT `11111111-1` (o `admin@eleam.cl`) / Rol: *Administrador* / Clave: `password`

---

## ⚡ 9. Consejos de Rendimiento y Mantenimiento

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

