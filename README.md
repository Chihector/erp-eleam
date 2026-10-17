# ERP ELEAM (Gestión Integral de Personal y Operaciones)

Sistema integral de gestión de personal, planificación de turnos, control de asistencia, cálculo de vacaciones, finanzas y liquidaciones de sueldo para **Establecimientos de Larga Estadía para Adultos Mayores (ELEAM)** en Chile, diseñado bajo estricto cumplimiento de la normativa laboral y previsional chilena.

---

## 🚀 Stack Tecnológico

- **Backend:** Laravel 12 (PHP 8.2+)
- **Panel Administrativo:** FilamentPHP 3 / 5
- **Base de Datos:** SQLite (optimizado con modo WAL y transacciones concurrentes)
- **Calendario Interactivo:** FullCalendar (`saade/filament-fullcalendar`)
- **Gestión de Permisos:** Spatie Laravel Permission
- **Auditoría de Registros:** Owen-It Laravel Auditing
- **Generación de Reportes:** Barryvdh DomPDF
- **Pruebas Automatizadas:** Pest & PHPUnit

---

## 📌 Progreso y Funcionalidades Implementadas

### ✅ Etapa 0: Configuración y Optimización Base
- [x] Configuración de Laravel 12 con SQLite en modo WAL (`DB_JOURNAL_MODE=WAL`, `DB_SYNCHRONOUS=NORMAL`, `DB_BUSY_TIMEOUT=5000`) para alto rendimiento en Windows.
- [x] Idioma regionalizado en Español (`es`) y zona horaria de Chile (`America/Santiago`).
- [x] Concurrencia de servidor local habilitada (`PHP_CLI_SERVER_WORKERS=4`).
- [x] Caché y optimización de componentes e iconos Filament precompilados.

### ✅ Etapa 1: Trabajadores, Contratos y Accesos
- [x] Modelo y gestión de `Trabajador` con datos personales, previsión (AFP y Salud) y selector de color para el calendario.
- [x] Validación personalizada `RutChile` que verifica matemáticamente el dígito verificador y exige guión.
- [x] Restricción de fecha de ingreso para evitar fechas futuras.
- [x] Modelo y gestión de `Contrato` con sueldo base en pesos chilenos, tipo de contrato (Indefinido, Plazo Fijo) y horas semanales.
- [x] Control de roles y permisos (Administrador, Jefe de Turno, Contador, Solo Lectura), aplicado mediante Policies con la siguiente matriz:
    - **Administrador:** acceso total en todos los módulos.
    - **Jefe de Turno:** gestiona turnos, asistencias y vacaciones; solo consulta el resto.
    - **Contador:** gestiona gastos, ingresos y liquidaciones; solo consulta el resto.
    - **Solo Lectura:** únicamente consulta (ver, sin modificar).

### ✅ Etapa 2: Módulo de Turnos y Calendario
- [x] Modelo `Turno` con tipo de jornada, estado (Programado, Completado, Ausente) y observaciones.
- [x] **Horarios automáticos inteligentes:**
  - **Día:** Auto-ajuste de horario de **08:00 a 20:00** (de 8 a 8).
  - **Noche:** Auto-ajuste de horario de **20:00 a 08:00** del día siguiente.
- [x] **Prevención de choques de turnos:** Validación en tiempo real para impedir solapamientos horarios del mismo trabajador.
- [x] **Dashboard con FullCalendar optimizado para móviles:**
  - Visualización priorizando el **primer nombre e inicial** al frente (`Hector M. (08:00-20:00)`) con salto de línea adaptativo para que en teléfonos nunca se corte el nombre del colaborador.
  - Vistas conmutables: **Mes**, **Agenda** (lista vertical diaria) y **Día**.
  - Bloques coloreados según el color distintivo de cada trabajador.

### ✅ Etapa 3: Módulo de Vacaciones
- [x] Registro y aprobación de solicitudes de días de vacaciones hábiles.
- [x] **Cálculo automático de Saldo Legal:** 1.25 días hábiles acumulados por cada mes trabajado desde la fecha de ingreso, descontando días aprobados.
- [x] Notificación visual del saldo disponible en tiempo real.

### ✅ Etapa 4: Finanzas - Egresos (Gastos)
- [x] Registro de gastos operativos categorizados con respaldo de comprobantes digitales.
- [x] Estadísticas en Dashboard con balance de gastos mensuales.

### ✅ Etapa 5: Finanzas - Ingresos y Reloj Control
- [x] Registro de ingresos del establecimiento.
- [x] Widget interactivo de caja y balance financiero neto en tiempo real.
- [x] Control de Asistencia (Reloj Control) con registro de marcas de entrada y salida vinculadas a turnos programados.

### ✅ Etapa 6: Remuneraciones y Liquidaciones de Sueldo (PDF)
- [x] Módulo `ParametroLegal` con valores preconfigurados de la legislación chilena (Sueldo mínimo de $500.000, UF, UTM, tope imponible AFP de 84.3 UF, tope Cesantía de 126.6 UF).
- [x] Servicio legal `RemuneracionService`:
  - Cálculo de Sueldo Base proporcional a los días trabajados.
  - Gratificación Legal automática (25% con tope de 4.75 ingresos mínimos anuales / 12).
  - Descuentos previsionales obligatorios de AFP y Salud (7%).
  - Seguro de Cesantía (0.6% trabajador para contratos indefinidos).
- [x] **Generador masivo e individual:** Botón *"Generar Liquidación Mensual"* en el panel para liquidar el sueldo de todo el personal activo en un solo clic.
- [x] **Exportación a PDF oficial:** Emisión y descarga de liquidaciones en PDF formato estándar chileno con detalle de haberes, descuentos, desglose de turnos y líneas de firma de empleador y trabajador.

### ✅ Etapa 7: Experiencia Móvil y Autenticación Flexible
- [x] **Inicio de sesión híbrido (Correo O RUT):** El usuario puede ingresar usando su correo electrónico o su RUT chileno (con o sin puntos/guión).
- [x] **Interfaz de inicio de sesión de alto contraste:** Botón principal *"Entrar al Sistema"* azul vibrante y botón del ojito estilizado para mostrar/ocultar contraseña táctilmente.
- [x] **Acceso en red local Wi-Fi:** Configurado para conectarse desde cualquier teléfono o tablet conectada a la misma red local.

### ✅ Etapa 8: Interfaz y validaciones en español
- [x] Español (`es`) como idioma predeterminado y de respaldo; Faker usa la configuración regional `es_ES`.
- [x] Traducciones en español para mensajes de autenticación, validaciones y paginación.
- [x] Portada de bienvenida en español, con enlaces directos al panel de administración y al inicio de sesión.
- [x] Pruebas de regresión para confirmar el idioma del inicio de sesión, la portada, el panel y los mensajes de validación.

---

## 💻 Puesta en Marcha Rápida

### 1. Iniciar el servidor local (para PC y teléfonos)
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Ingresar al panel administrativo
- **Desde tu PC:** 👉 **[http://localhost:8000/admin](http://localhost:8000/admin)**
- **Desde tu teléfono móvil:** 👉 **`http://192.168.1.122:8000/admin`**

**Credenciales disponibles (con sus roles asignados):**
| Usuario | Correo | RUT | Rol | Contraseña |
| :--- | :--- | :--- | :--- | :--- |
| **Héctor Contreras** | `hector@eleam.cl` | `19486694-1` | Jefe de Turno | `password` |
| **Administrador General** | `admin@eleam.cl` | `11111111-1` | Administrador | `password` |

> **Importante:** Estas credenciales son únicamente para desarrollo local. Cámbialas antes de exponer el sistema a usuarios o redes no confiables; no uses estas contraseñas en producción.

### Configuración regional
La configuración inicial usa español de Chile para la interfaz (`APP_LOCALE=es`), español como idioma de respaldo (`APP_FALLBACK_LOCALE=es`) y español de España para los datos de prueba (`APP_FAKER_LOCALE=es_ES`). Al crear la configuración local, copia `.env.example` y genera una clave propia con `php artisan key:generate`.

---

## 🧪 Pruebas Automatizadas (Tests)

El sistema incluye una completa suite de pruebas automatizadas:
```bash
php artisan test
```

Para probar módulos puntuales:
```bash
# Validaciones de RUT y Trabajadores
php artisan test --filter=TrabajadorTest

# Lógica y prevención de cruces de Turnos
php artisan test --filter=TurnoTest

# Cálculo de Liquidaciones según normativa chilena
php artisan test --filter=RemuneracionServiceTest

# Cálculo del saldo legal y solicitudes de vacaciones
php artisan test --filter=VacacionTest

# Permisos y acceso al panel por rol
php artisan test --filter=AutorizacionTest

# Interfaz y validaciones en español
php artisan test --filter=SpanishLocaleTest

# Cálculo de totales de finanzas (servicio FinanzasService)
php artisan test --filter=FinanzasServiceTest
```

La suite completa se ejecuta con `php artisan test`. Última verificación: **34 pruebas aprobadas y 76 aserciones**.

---

## 📖 Documentación para el Usuario
Para conocer a fondo el flujo de trabajo y uso diario del sistema, consulta la **[Guía de Uso del Usuario](GUIA_DE_USO.md)**.
