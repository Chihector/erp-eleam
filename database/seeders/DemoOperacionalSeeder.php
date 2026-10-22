<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Contrato;
use App\Models\Finiquito;
use App\Models\Gasto;
use App\Models\Ingreso;
use App\Models\LicenciaMedica;
use App\Models\ParametroLegal;
use App\Models\Trabajador;
use App\Models\Turno;
use App\Models\Vacacion;
use App\Services\FiniquitoService;
use App\Services\RemuneracionService;
use Illuminate\Database\Seeder;

class DemoOperacionalSeeder extends Seeder
{
    public function run(): void
    {
        $ahora = now();
        $inicioMes = $ahora->copy()->startOfMonth();

        $parametro = ParametroLegal::firstOrCreate(
            ['mes' => $ahora->month, 'anio' => $ahora->year],
            [
                // Referencia usada solo si el período no existe. No sobrescribe
                // parámetros reales ya cargados o verificados.
                // UF de referencia para remuneraciones de octubre de 2026:
                // valor al 30/09/2026 (último día del mes anterior).
                'valor_uf' => 41057.20,
                'valor_utm' => 72151,
                'sueldo_minimo' => 553553,
                'tope_imponible_afp' => 84.3,
                'tope_imponible_ips' => 60.0,
                'tope_imponible_cesantia' => 126.6,
                'porcentaje_sis' => 1.49,
                'origen' => 'demo (valores de referencia sin verificar)',
                'actualizado_en' => now(),
            ],
        );

        $rutDemo = '12345678-5';
        $trabajadorExistente = Trabajador::withTrashed()->where('rut', $rutDemo)->first();

        if ($trabajadorExistente && (
            $trabajadorExistente->nombres !== 'DEMO'
            || $trabajadorExistente->apellidos !== 'NO ES PERSONA REAL'
        )) {
            throw new \RuntimeException(
                "El RUT de prueba {$rutDemo} ya está asociado a otro registro; no se modificó ningún dato.",
            );
        }

        $trabajador = Trabajador::firstOrCreate(
            ['rut' => $rutDemo],
            [
                'nombres' => 'DEMO',
                'apellidos' => 'NO ES PERSONA REAL',
                'cargo' => 'Cuidador/a (datos de prueba)',
                'afp' => 'Modelo',
                'afp_tasa' => 10.58,
                'sistema_salud' => 'Fonasa',
                'fecha_ingreso' => $ahora->copy()->subYears(2)->startOfMonth()->toDateString(),
                'color_calendario' => '#2563eb',
            ],
        );

        if ($trabajador->trashed()) {
            $trabajador->restore();
        }

        if ($trabajador->afp_tasa === null) {
            $trabajador->update(['afp_tasa' => 10.58]);
        }

        $fechaInicioContrato = $ahora->copy()->subYears(2)->startOfMonth()->toDateString();
        $contrato = Contrato::withTrashed()
            ->where('trabajador_id', $trabajador->id)
            ->whereDate('fecha_inicio', $fechaInicioContrato)
            ->orderByDesc('id')
            ->first();

        if (! $contrato) {
            $contrato = Contrato::create([
                'trabajador_id' => $trabajador->id,
                'fecha_inicio' => $fechaInicioContrato,
                'tipo_contrato' => 'Indefinido',
                'sueldo_base' => 750000,
                'horas_semanales' => 42,
                'jornada_excepcional' => false,
            ]);
        } elseif ($contrato->trashed()) {
            $contrato->restore();
        }
        $contrato->fill([
            'tipo_contrato' => 'Indefinido',
            'sueldo_base' => 750000,
            'horas_semanales' => 42,
            'jornada_excepcional' => false,
        ])->save();

        $turnoCompletado = Turno::firstOrCreate(
            [
                'trabajador_id' => $trabajador->id,
                'fecha_inicio' => $inicioMes->copy()->addDays(1)->setTime(8, 0)->toDateTimeString(),
            ],
            [
                'fecha_fin' => $inicioMes->copy()->addDays(1)->setTime(20, 0)->toDateTimeString(),
                'tipo_turno' => 'Día',
                'estado' => 'Completado',
                'observaciones' => 'Turno ficticio de demostración.',
            ],
        );

        Asistencia::firstOrCreate(
            ['turno_id' => $turnoCompletado->id],
            [
                'trabajador_id' => $trabajador->id,
                'fecha_hora_entrada' => $turnoCompletado->fecha_inicio,
                'fecha_hora_salida' => $turnoCompletado->fecha_fin,
                'observaciones' => 'Marca ficticia para validar asistencia.',
            ],
        );

        Turno::firstOrCreate(
            [
                'trabajador_id' => $trabajador->id,
                'fecha_inicio' => $ahora->copy()->addDay()->setTime(20, 0)->toDateTimeString(),
            ],
            [
                'fecha_fin' => $ahora->copy()->addDays(2)->setTime(8, 0)->toDateTimeString(),
                'tipo_turno' => 'Noche',
                'estado' => 'Programado',
                'observaciones' => 'Turno ficticio de demostración.',
            ],
        );

        $vacacion = Vacacion::firstOrCreate(
            ['trabajador_id' => $trabajador->id, 'observaciones' => 'Solicitud ficticia de demostración.'],
            [
                'fecha_inicio' => $inicioMes->copy()->addDays(13)->toDateString(),
                'fecha_fin' => $inicioMes->copy()->addDays(15)->toDateString(),
                'dias_habiles' => 3,
                'estado' => 'Aprobada',
            ],
        );
        $vacacion->fill([
            'fecha_inicio' => $inicioMes->copy()->addDays(13)->toDateString(),
            'fecha_fin' => $inicioMes->copy()->addDays(15)->toDateString(),
            'dias_habiles' => 3,
            'estado' => 'Aprobada',
        ])->save();

        $inicioLicencia = $inicioMes->copy()->addDays(4);
        LicenciaMedica::firstOrCreate(
            ['trabajador_id' => $trabajador->id, 'folio' => 'DEMO-'.$ahora->format('Ym')],
            [
                'tipo' => 'Común',
                'entidad_emisora' => 'Fonasa',
                'fecha_inicio' => $inicioLicencia->toDateString(),
                'fecha_fin' => $inicioLicencia->copy()->addDays(3)->toDateString(),
                'dias_reposo' => 4,
                'estado' => 'Pendiente',
                'observaciones' => 'Registro completamente ficticio para demostración.',
            ],
        );

        app(RemuneracionService::class)->calcularLiquidacion(
            $trabajador,
            $ahora->month,
            $ahora->year,
            30,
        );

        $causalDemo = 'Necesidades de la Empresa (Art. 161) - DEMO';
        $calculoFiniquito = app(FiniquitoService::class)->calcular($trabajador, $causalDemo, $ahora);

        $finiquito = Finiquito::withTrashed()
            ->where('trabajador_id', $trabajador->id)
            ->whereDate('fecha_termino', $ahora->toDateString())
            ->where('causal', $causalDemo)
            ->first();

        if (! $finiquito) {
            $finiquito = Finiquito::create(array_merge($calculoFiniquito, [
                'trabajador_id' => $trabajador->id,
                'fecha_termino' => $ahora->toDateString(),
                'causal' => $causalDemo,
                'estado' => 'Borrador',
                'observaciones' => 'Documento ficticio. No firmar ni usar para pagos.',
            ]));
        } elseif ($finiquito->trashed()) {
            $finiquito->restore();
        }
        $finiquito->fill(array_merge($calculoFiniquito, [
            'fecha_termino' => $ahora->toDateString(),
            'causal' => $causalDemo,
            'estado' => 'Borrador',
            'observaciones' => 'Documento ficticio. No firmar ni usar para pagos.',
        ]))->save();

        Ingreso::firstOrCreate(
            ['fecha' => $inicioMes->toDateString(), 'categoria' => 'Demo', 'descripcion' => 'Ingreso ficticio de demostración'],
            ['monto' => 850000],
        );

        Gasto::firstOrCreate(
            ['fecha' => $inicioMes->toDateString(), 'categoria' => 'Demo', 'descripcion' => 'Gasto ficticio de demostración'],
            ['monto' => 175000],
        );

        $this->command?->info('Escenario ficticio cargado (DEMO, no corresponde a una persona real).');
        $this->command?->line('Trabajador '.$trabajador->nombres.' '.$trabajador->apellidos.' | RUT de prueba '.$rutDemo);
        $this->command?->line('Contrato indefinido | Sueldo base $750.000 | Jornada 42 horas semanales.');
        $this->command?->line('Parámetros usados: UF $'.number_format((float) $parametro->valor_uf, 2, ',', '.')
            .' | UTM $'.number_format((float) $parametro->valor_utm, 0, ',', '.')
            .' | sueldo mínimo $'.number_format((float) $parametro->sueldo_minimo, 0, ',', '.')
            .' | topes AFP/IPS/Cesantía '.$parametro->tope_imponible_afp.'/'.$parametro->tope_imponible_ips.'/'.$parametro->tope_imponible_cesantia.' UF'
            .' | SIS '.$parametro->porcentaje_sis.'%'
            .' (valores de demostración del seeder, validar antes de uso real).');
    }
}
