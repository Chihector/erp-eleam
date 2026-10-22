<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Liquidación de Sueldo - {{ $liquidacion->trabajador->nombres }} {{ $liquidacion->trabajador->apellidos }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 5px; text-align: left; border: 1px solid #ccc; }
        th { background-color: #f4f4f4; }
        .section-title { font-weight: bold; background-color: #eaeaea; text-align: center; padding: 5px; border: 1px solid #ccc; margin-top: 10px;}
        .totals { font-weight: bold; }
        .right { text-align: right; }
        .firmas { margin-top: 50px; width: 100%; text-align: center; }
        .firmas td { border: none; padding-top: 50px; width: 50%; }
        .linea-firma { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LIQUIDACIÓN DE SUELDO</h1>
        <p>PERÍODO: {{ str_pad($liquidacion->mes, 2, '0', STR_PAD_LEFT) }} - {{ $liquidacion->anio }}</p>
    </div>

    <table>
        <tr>
            <th>Trabajador</th>
            <td>{{ $liquidacion->trabajador->nombres }} {{ $liquidacion->trabajador->apellidos }}</td>
            <th>RUT</th>
            <td>{{ $liquidacion->trabajador->rut }}</td>
        </tr>
        <tr>
            <th>Cargo</th>
            <td>{{ $liquidacion->trabajador->cargo }}</td>
            <th>Días Trabajados</th>
            <td>{{ $liquidacion->dias_trabajados }}</td>
        </tr>
        <tr>
            <th>AFP</th>
            <td>{{ $liquidacion->afp_nombre }} ({{ $liquidacion->afp_porcentaje }}%)</td>
            <th>Salud</th>
            <td>{{ $liquidacion->salud_nombre }} ({{ $liquidacion->salud_porcentaje }}%)</td>
        </tr>
    </table>

    <table>
        <tr>
            <th colspan="2" class="section-title">HABERES IMPONIBLES</th>
        </tr>
        <tr>
            <td>Sueldo Base Proporcional</td>
            <td class="right">${{ number_format($liquidacion->sueldo_base, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Gratificación Legal</td>
            <td class="right">${{ number_format($liquidacion->gratificacion, 0, ',', '.') }}</td>
        </tr>
        <tr class="totals">
            <td>TOTAL IMPONIBLE</td>
            <td class="right">${{ number_format($liquidacion->total_imponible, 0, ',', '.') }}</td>
        </tr>

        <tr>
            <th colspan="2" class="section-title">HABERES NO IMPONIBLES</th>
        </tr>
        <tr>
            <td>Movilización</td>
            <td class="right">${{ number_format($liquidacion->monto_movilizacion, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Colación</td>
            <td class="right">${{ number_format($liquidacion->monto_colacion, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Cargas Familiares</td>
            <td class="right">${{ number_format($liquidacion->cargas_familiares, 0, ',', '.') }}</td>
        </tr>
        <tr class="totals">
            <td>TOTAL NO IMPONIBLE</td>
            <td class="right">${{ number_format($liquidacion->total_no_imponible, 0, ',', '.') }}</td>
        </tr>
        <tr class="totals">
            <td style="background-color: #f4f4f4;">TOTAL HABERES</td>
            <td class="right" style="background-color: #f4f4f4;">${{ number_format($liquidacion->total_haberes, 0, ',', '.') }}</td>
        </tr>

        <tr>
            <th colspan="2" class="section-title">DESCUENTOS LEGALES</th>
        </tr>
        <tr>
            <td>Fondo de Pensiones (AFP)</td>
            <td class="right">${{ number_format($liquidacion->monto_afp, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Salud</td>
            <td class="right">${{ number_format($monto_salud ?? $liquidacion->monto_salud, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Seguro de Cesantía</td>
            <td class="right">${{ number_format($liquidacion->monto_cesantia, 0, ',', '.') }}</td>
        </tr>
        <tr class="totals">
            <td>TOTAL DESCUENTOS LEGALES</td>
            <td class="right">${{ number_format($liquidacion->total_descuentos_legales, 0, ',', '.') }}</td>
        </tr>
        @if(($liquidacion->monto_sis ?? 0) > 0)
        <tr>
            <td style="color: #666;">Aporte SIS del empleador (no se descuenta del trabajador)</td>
            <td class="right" style="color: #666;">${{ number_format($liquidacion->monto_sis, 0, ',', '.') }}</td>
        </tr>
        @endif

        <tr>
            <th colspan="2" class="section-title">OTROS DESCUENTOS</th>
        </tr>
        <tr>
            <td>Varios (Anticipos, etc.)</td>
            <td class="right">${{ number_format($liquidacion->otros_descuentos, 0, ',', '.') }}</td>
        </tr>
        <tr class="totals">
            <td style="background-color: #f4f4f4;">TOTAL DESCUENTOS</td>
            <td class="right" style="background-color: #f4f4f4;">${{ number_format($liquidacion->total_descuentos, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table style="width: 50%; margin-left: 50%; border: 2px solid #333; margin-bottom: 20px;">
        <tr class="totals" style="font-size: 14px;">
            <td style="background-color: #eaeaea; border-right: none;">LÍQUIDO A PAGAR</td>
            <td class="right" style="background-color: #eaeaea; border-left: none;">${{ number_format($liquidacion->sueldo_liquido, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if(isset($turnos) && $turnos->count() > 0)
    <table>
        <tr>
            <th colspan="4" class="section-title">DETALLE DE TURNOS DEL MES</th>
        </tr>
        <tr>
            <th style="width: 25%">Inicio del Turno</th>
            <th style="width: 25%">Fin del Turno</th>
            <th style="width: 25%">Tipo de Turno</th>
            <th style="width: 25%">Estado</th>
        </tr>
        @foreach($turnos as $turno)
        <tr>
            <td>{{ \Carbon\Carbon::parse($turno->fecha_inicio)->format('d-m-Y H:i') }}</td>
            <td>{{ \Carbon\Carbon::parse($turno->fecha_fin)->format('d-m-Y H:i') }}</td>
            <td>{{ $turno->tipo_turno }}</td>
            <td>{{ $turno->estado }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    <p style="text-align: justify; margin-top: 20px;">
        Certifico que he recibido de mi empleador la suma de <strong>${{ number_format($liquidacion->sueldo_liquido, 0, ',', '.') }}</strong>, a mi entera satisfacción, sin tener cargo ni cobro alguno que formular por los conceptos indicados en la presente liquidación.
    </p>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea-firma"></div>
                Firma Empleador
            </td>
            <td>
                <div class="linea-firma"></div>
                Firma Trabajador<br>
                RUT: {{ $liquidacion->trabajador->rut }}
            </td>
        </tr>
    </table>

</body>
</html>
