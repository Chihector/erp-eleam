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

### ✅ Etapa 9: Menú simplificado y despliegue con Docker + PostgreSQL
- [x] **Menú lateral organizado en 4 secciones colapsables** para reducir la carga visual:
  - **Operación:** Turnos, Asistencias, Vacaciones, Licencias Médicas.
  - **Personal:** Trabajadores, Contratos.
  - **Finanzas:** Ingresos, Gastos, Liquidaciones, Finiquitos.
  - **Configuración:** Parámetros Legales, Usuarios *(visibles solo para el Administrador)*.
- [x] Iconos distintivos por módulo y visibilidad automática según el rol del usuario.
- [x] **Despliegue profesional con Docker:** `Dockerfile` (nginx + PHP-FPM + OPcache), `docker-compose.yml` (aplicación + PostgreSQL 16) y guía completa en **[docs/DESPLIEGUE_DOCKER.md](docs/DESPLIEGUE_DOCKER.md)**.
- [x] **Migración de datos SQLite → PostgreSQL en un comando:** `php artisan eleam:migrate-sqlite` (copia tablas, convierte booleanos y sincroniza secuencias).
- [x] **Índices de rendimiento** en turnos, asistencias, liquidaciones, vacaciones, gastos e ingresos para acelerar las consultas más frecuentes.
- [x] **Despliegue como App Web sin Docker:** instalador automático para servidor Ubuntu (`deploy/instalar-servidor.sh`), script de actualización (`deploy/actualizar.sh`), respaldo automático (`deploy/respaldar.sh`) y guía en **[docs/DESPLIEGUE_SERVIDOR.md](docs/DESPLIEGUE_SERVIDOR.md)**.
- [x] **Comando para crear/restablecer administradores con clave segura:** `php artisan eleam:admin-crear`.

### ✅ Etapa 10: Licencias Médicas, Finiquitos y Libro de Remuneraciones DT
- [x] **Módulo de Licencias Médicas** (menú *Operación*):
  - Registro de folio, tipo (Común, Maternal, Accidente del Trabajo, Enfermedad Profesional), entidad emisora (Fonasa, Isapre, Mutual), fechas y estado (Pendiente, Validada, Rechazada).
  - Servicio `SubsidioLicenciaService` con cálculo automático del subsidio:
    - *Común:* 75% del sueldo diario, tope de 60 UF y 3 días de carencia.
    - *Maternal y Laboral:* 100% del sueldo, sin carencia.
  - Los días de reposo y el subsidio se recalculan automáticamente al guardar o editar.
- [x] **Módulo de Finiquitos** (menú *Finanzas*):
  - Botón *"Calcular Finiquito"* con la causal del término (Art. 159, 160, 161, renuncia).
  - Servicio `FiniquitoService` con normativa chilena:
    - Indemnización por años de servicio: 30 días por año, **tope 11 años** y sueldo tope **90 UF** (causal Art. 161).
    - Indemnización sustitutiva del aviso previo: 1 mes de sueldo.
    - Vacaciones pendientes valorizadas y gratificación proporcional.
  - **PDF formal del finiquito** con detalle de haberes, descuentos, líquido a pagar y líneas de firma.
  - Recalculo automático del líquido al ajustar descuentos manualmente.
- [x] **Libro de Remuneraciones Digital (DT) en CSV:**
  - Botón *"Libro de Remuneraciones DT"* en el módulo de Liquidaciones.
  - Exporta el período completo en CSV con BOM UTF-8 (compatible con Excel en español): identificación del trabajador, tipo de contrato, haberes, descuentos legales y líquido pagado.

### ✅ Etapa 11: Búsqueda inteligente en todo el sistema
- [x] **Búsqueda tolerante a tildes y mayúsculas:** escribir `hector` encuentra a **Héctor**, `munoz` a **Muñoz** y `gonzalez` a **González**, tanto en los selectores de trabajador como en el buscador de todas las tablas.
- [x] Aplica a los selectores de **Turnos, Asistencias, Contratos, Vacaciones, Licencias Médicas, Finiquitos y Liquidaciones** (incluido el botón *"Generar Liquidación Mensual"*).
- [x] **Búsqueda de RUT con o sin formato:** `19486694`, `19.486.694-1` y `19486694-1` encuentran al mismo trabajador.
- [x] Implementado con `App\Support\Buscador`, que normaliza el término buscado y los datos en PHP y en SQL (compatible con SQLite y PostgreSQL).

---

## 💻 Puesta en Marcha Rápida

### 1. Iniciar el servidor local (para PC y teléfonos)
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Ingresar al panel administrativo
- **Desde tu PC:** 👉 **[http://localhost:8000/admin](http://localhost:8000/admin)**
- **Desde tu teléfono móvil:** 👉 **`http://192.168.1.122:8000/admin`**

### 🐳 Modo profesional con Docker + PostgreSQL

Para instalar el sistema con base de datos PostgreSQL, respaldos fáciles y acceso
estable desde varios dispositivos a la vez:

```bash
copy .env.production.example .env.production
docker compose --env-file .env.production up -d --build
docker compose --env-file .env.production exec app php artisan eleam:migrate-sqlite
```

### 🌐 Publicar como App Web (sin Docker — recomendado)

Para tener el sistema en internet con tu propio dominio (ej: `erp.tu-eleam.cl`),
accesible desde cualquier PC o teléfono sin depender de una PC encendida:

```bash
# En un servidor Ubuntu limpio (VPS), un solo comando:
sudo bash deploy/instalar-servidor.sh erp.tu-eleam.cl \
     https://github.com/chicohector953/Erp-eleam.git tu-correo@dominio.cl
```

📖 **Guías completas paso a paso:**
- 🌐 **[docs/DESPLIEGUE_SERVIDOR.md](docs/DESPLIEGUE_SERVIDOR.md)** — App web propia (VPS, sin Docker, recomendado)
- 🐳 **[docs/DESPLIEGUE_DOCKER.md](docs/DESPLIEGUE_DOCKER.md)** — Docker + PostgreSQL

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

# Cálculo del subsidio de Licencias Médicas (75% común / 100% maternal y laboral)
php artisan test --filter=LicenciaMedicaTest

# Cálculo de Finiquitos (indemnizaciones, topes legales y PDF)
php artisan test --filter=FiniquitoTest

# Exportación del Libro de Remuneraciones DT (CSV)
php artisan test --filter=LibroRemuneracionesTest

# Búsqueda tolerante a tildes (selectores de trabajador y tablas)
php artisan test --filter=BusquedaSinAcentosTest
```

La suite completa se ejecuta con `php artisan test`. Última verificación: **64 pruebas aprobadas y 171 aserciones**.

---

## 📖 Documentación para el Usuario
Para conocer a fondo el flujo de trabajo y uso diario del sistema, consulta la **[Guía de Uso del Usuario](GUIA_DE_USO.md)**.
