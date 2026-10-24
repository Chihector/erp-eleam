<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de Backup a Google Drive
    |--------------------------------------------------------------------------
    |
    | Configura aquí las opciones para el backup automático a Google Drive.
    | Las credenciales se obtienen de Google Cloud Console (Cuenta de Servicio).
    |
    */

    'google_drive' => [

        /*
         * ID de la carpeta en Google Drive donde se guardarán los backups.
         * Se obtiene de la URL: https://drive.google.com/drive/folders/ID_CARPETA
         * También se puede configurar via variable de entorno GOOGLE_DRIVE_BACKUP_FOLDER_ID
         */
        'folder_id' => env('GOOGLE_DRIVE_BACKUP_FOLDER_ID'),

        /*
         * Ruta al archivo de credenciales de la Cuenta de Servicio (JSON).
         * Coloca el archivo en la raíz del proyecto o en storage/app/credentials/
         */
        'credentials_path' => env('GOOGLE_DRIVE_CREDENTIALS_PATH', base_path('google-drive-credentials.json')),

        /*
         * Qué incluir en el backup diario (--all incluye todo)
         */
        'include' => [
            'database' => true,      // database/database.sqlite
            'storage' => true,       // storage/app (logs, uploads, etc.)
            'env' => true,           // .env sanitizado (sin secrets)
        ],

        /*
         * Retención: cuántos backups mantener en Drive (los más antiguos se borran)
         * 0 = sin límite
         */
        'retention_days' => env('GOOGLE_DRIVE_BACKUP_RETENTION', 30),

    ],

    /*
    |--------------------------------------------------------------------------
    | Respaldo de PostgreSQL
    |--------------------------------------------------------------------------
    |
    | El comando backup:google-drive usa pg_dump cuando DB_CONNECTION=pgsql.
    | Si el binario no está en PATH, define PG_DUMP_PATH (y opcionalmente
    | PG_RESTORE_PATH / PSQL_PATH) con la ruta absoluta del cliente.
    |
    */

    'pgsql' => [
        'pg_dump_path' => env('PG_DUMP_PATH'),
        'pg_restore_path' => env('PG_RESTORE_PATH'),
        'psql_path' => env('PSQL_PATH'),
        'timeout' => env('PG_DUMP_TIMEOUT', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificaciones
    |--------------------------------------------------------------------------
    */
    'notifications' => [

        /*
         * Días antes del vencimiento para notificar contratos
         */
        'contratos_dias_aviso' => [30, 15, 7, 3, 1],

        /*
         * Días de vacaciones pendientes para alertar (riesgo multa DT > 15)
         */
        'vacaciones_alerta_dias' => 10,

        /*
         * Días antes del vencimiento para alertar licencias médicas
         */
        'licencias_dias_aviso' => 7,

        /*
         * Frecuencia máxima de notificaciones repetidas
         */
        'frecuencia_repeticion' => [
            'vacaciones' => 7,      // días entre notificaciones del mismo trabajador
            'licencias_vencidas' => 3, // días entre alertas de licencias vencidas sin cerrar
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Canales de log
    |--------------------------------------------------------------------------
    */
    'logging' => [

        'channel' => 'backup', // canal definido en config/logging.php

    ],

];
