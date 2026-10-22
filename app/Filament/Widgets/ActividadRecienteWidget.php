<?php

namespace App\Filament\Widgets;

use App\Models\ActividadReciente;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ActividadRecienteWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Actividad Reciente';

    protected static ?int $headingLevel = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->consultaActividad())
            ->columns([
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Liquidación' => 'success',
                        'Licencia Médica' => 'info',
                        'Finiquito' => 'warning',
                        'Vacación' => 'primary',
                        'Gasto' => 'danger',
                        'Ingreso' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Detalle')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto')
                    ->label('Monto')
                    ->money('CLP')
                    ->placeholder('—')
                    ->alignRight(),
            ])
            ->defaultSort('fecha', 'desc')
            ->paginated([10, 25, 50])
            ->poll('60s');
    }

    /**
     * Une las últimas actividades de todos los módulos en una sola consulta.
     *
     * Se usa concatenación con "||" (compatible con SQLite y PostgreSQL) y se
     * envuelve el UNION en una subconsulta para que Filament pueda paginar y
     * ordenar sin conflictos de columnas.
     */
    private function consultaActividad(): Builder
    {
        $desde = Carbon::now()->subDays(30);

        $liquidaciones = DB::table('liquidacions as l')
            ->selectRaw("'Liquidación-' || l.id as id")
            ->selectRaw("'Liquidación' as tipo")
            ->selectRaw("'Liquidación ' || l.mes || '/' || l.anio || ' - ' || t.nombres || ' ' || t.apellidos as descripcion")
            ->selectRaw('l.created_at as fecha')
            ->selectRaw('l.sueldo_liquido as monto')
            ->join('trabajadores as t', 't.id', '=', 'l.trabajador_id')
            ->where('l.created_at', '>=', $desde);

        $licencias = DB::table('licencias_medicas as lm')
            ->selectRaw("'Licencia-' || lm.id as id")
            ->selectRaw("'Licencia Médica' as tipo")
            ->selectRaw("'Licencia ' || lm.tipo || ' - ' || t.nombres || ' ' || t.apellidos as descripcion")
            ->selectRaw('lm.created_at as fecha')
            ->selectRaw('lm.monto_subsidio as monto')
            ->join('trabajadores as t', 't.id', '=', 'lm.trabajador_id')
            ->where('lm.created_at', '>=', $desde)
            ->whereNull('lm.deleted_at');

        $finiquitos = DB::table('finiquitos as f')
            ->selectRaw("'Finiquito-' || f.id as id")
            ->selectRaw("'Finiquito' as tipo")
            ->selectRaw("'Finiquito ' || f.causal || ' - ' || t.nombres || ' ' || t.apellidos as descripcion")
            ->selectRaw('f.created_at as fecha')
            ->selectRaw('f.liquido_pagar as monto')
            ->join('trabajadores as t', 't.id', '=', 'f.trabajador_id')
            ->where('f.created_at', '>=', $desde)
            ->whereNull('f.deleted_at');

        $vacaciones = DB::table('vacaciones as v')
            ->selectRaw("'Vacación-' || v.id as id")
            ->selectRaw("'Vacación' as tipo")
            ->selectRaw("'Vacación ' || v.estado || ' - ' || t.nombres || ' ' || t.apellidos as descripcion")
            ->selectRaw('v.created_at as fecha')
            ->selectRaw('NULL as monto')
            ->join('trabajadores as t', 't.id', '=', 'v.trabajador_id')
            ->where('v.created_at', '>=', $desde)
            ->whereNull('v.deleted_at');

        $gastos = DB::table('gastos as g')
            ->selectRaw("'Gasto-' || g.id as id")
            ->selectRaw("'Gasto' as tipo")
            ->selectRaw("'Gasto: ' || g.descripcion as descripcion")
            ->selectRaw('g.created_at as fecha')
            ->selectRaw('g.monto as monto')
            ->where('g.created_at', '>=', $desde);

        $ingresos = DB::table('ingresos as i')
            ->selectRaw("'Ingreso-' || i.id as id")
            ->selectRaw("'Ingreso' as tipo")
            ->selectRaw("'Ingreso: ' || i.descripcion as descripcion")
            ->selectRaw('i.created_at as fecha')
            ->selectRaw('i.monto as monto')
            ->where('i.created_at', '>=', $desde);

        $union = $liquidaciones
            ->unionAll($licencias)
            ->unionAll($finiquitos)
            ->unionAll($vacaciones)
            ->unionAll($gastos)
            ->unionAll($ingresos);

        // Se envuelve en fromSub para que Filament pagine/ordene el resultado.
        return ActividadReciente::query()
            ->fromSub($union, 'actividad')
            ->select('actividad.*');
    }
}
