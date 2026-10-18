<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finiquito - {{ $finiquito->trabajador->nombres }} {{ $finiquito->trabajador->apellidos }}</title>
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
        <h1>FINIQUITO DE CONTRATO DE TRABAJO</h1>
        <p>CAUSAL: {{ $finiquito->causal }}</p>
        <p>FECHA DE TÉRMINO: {{ $finiquito->fecha_termino->format('d/m/Y') }}</p>
    </div>

    <table>
        <tr>
            <th>Trabajador</th>
            <td>{{ $finiquito->trabajador->nombres }} {{ $finiquito->trabajador->apellidos }}</td>
            <th>RUT</th>
            <td>{{ $finiquito->trabajador->rut }}</td>
        </tr>
        <tr>
            <th>Cargo</th>
            <td>{{ $finiquito->trabajador->cargo }}</td>
            <th>Fecha de Ingreso</th>
            <td>{{ $finiquito->trabajador->fecha_ingreso?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <th>Antigüedad</th>
            <td>{{ $finiquito->anios_servicio }} años{{ $finiquito->meses_servicio > 0 ? " y {$finiquito->meses_servicio} meses" : '' }}</td>
            <th>Sueldo de Cálculo</th>
            <td>${{ number_format($finiquito->sueldo_base_calculo, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <th colspan="2" class="section-title">HABERES DEL FINIQUITO</th>
        </tr>
        <tr>
            <td>Indemnización por Años de Servicio</td>
            <td class="right">${{ number_format($finiquito->indemnizacion_anios, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Indemnización Sustitutiva del Aviso Previo</td>
            <td class="right">${{ number_format($finiquito->indemnizacion_aviso_previo, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Vacaciones Pendientes</td>
            <td class="right">${{ number_format($finiquito->vacaciones_pendientes, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Gratificación Proporcional</td>
            <td class="right">${{ number_format($finiquito->gratificacion_pendiente, 0, ',', '.') }}</td>
        </tr>
        <tr class="totals">
            <td style="background-color: #f4f4f4;">TOTAL HABERES</td>
            <td class="right" style="background-color: #f4f4f4;">${{ number_format($finiquito->total_haberes, 0, ',', '.') }}</td>
        </tr>

        <tr>
            <th colspan="2" class="section-title">DESCUENTOS</th>
        </tr>
        <tr>
            <td>Otros Descuentos (anticipos, préstamos, etc.)</td>
            <td class="right">${{ number_format($finiquito->otros_descuentos, 0, ',', '.') }}</td>
        </tr>
        <tr class="totals">
            <td style="background-color: #f4f4f4;">TOTAL DESCUENTOS</td>
            <td class="right" style="background-color: #f4f4f4;">${{ number_format($finiquito->otros_descuentos, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table style="width: 50%; margin-left: 50%; border: 2px solid #333; margin-bottom: 20px;">
        <tr class="totals" style="font-size: 14px;">
            <td style="background-color: #eaeaea; border-right: none;">LÍQUIDO A PAGAR</td>
            <td class="right" style="background-color: #eaeaea; border-left: none;">${{ number_format($finiquito->liquido_pagar, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($finiquito->observaciones)
        <p style="text-align: justify;"><strong>Observaciones:</strong> {{ $finiquito->observaciones }}</p>
    @endif

    <p style="text-align: justify; margin-top: 20px;">
        En ____________________, a {{ now()->format('d/m/Y') }}, el trabajador declara recibir de su
        empleador la suma de <strong>${{ number_format($finiquito->liquido_pagar, 0, ',', '.') }}</strong>,
        quedando así terminada la relación laboral y sin tener cargo ni cobro alguno que formular por
        los conceptos indicados en el presente finiquito.
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
                RUT: {{ $finiquito->trabajador->rut }}
            </td>
        </tr>
    </table>

</body>
</html>
