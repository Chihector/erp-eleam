<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Notificaciones - ERP ELEAM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; line-height: 1.4; color: #1f2937; }
        .page { padding: 20mm 15mm; }
        .header { text-align: center; border-bottom: 3px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; color: #1e40af; margin-bottom: 5px; }
        .header .subtitle { font-size: 14px; color: #64748b; }
        .header .periodo { font-size: 12px; color: #94a3b8; margin-top: 5px; }

        .section { margin-bottom: 25px; page-break-inside: avoid; }
        .section-title { font-size: 14px; font-weight: bold; color: #1e40af; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .section-count { font-size: 12px; font-weight: normal; color: #64748b; background: #eff6ff; padding: 2px 8px; border-radius: 12px; }

        table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 5px; }
        th { background: #2563eb; color: white; padding: 8px 5px; text-align: left; font-weight: 600; }
        td { padding: 6px 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr:nth-child(even) td { background: #f8fafc; }
        tr:hover td { background: #eff6ff; }

        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        .badge-alta { background: #fef2f2; color: #dc2626; }
        .badge-media { background: #fffbeb; color: #d97706; }
        .badge-baja { background: #f0fdf4; color: #16a34a; }
        .badge-riesgo { background: #fef2f2; color: #dc2626; }
        .badge-ok { background: #f0fdf4; color: #16a34a; }
        .badge-info { background: #eff6ff; color: #2563eb; }
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-danger { background: #fef2f2; color: #dc2626; }
        .badge-success { background: #f0fdf4; color: #16a34a; }

        .empty { text-align: center; color: #94a3b8; font-style: italic; padding: 20px; }
        .footer { text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 30px; }
        .summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px; }
        .summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; text-align: center; }
        .summary-card .number { font-size: 28px; font-weight: bold; color: #2563eb; }
        .summary-card .label { font-size: 11px; color: #64748b; margin-top: 4px; }
        .summary-card.warning .number { color: #dc2626; }
        .summary-card.success .number { color: #16a34a; }
        .summary-card.info .number { color: #2563eb; }

        @page { margin: 0; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>📊 Reporte de Notificaciones</h1>
            <div class="subtitle">ERP ELEAM — {{ ucfirst($periodo) }}</div>
            <div class="periodo">Período: {{ $inicioPeriodo }} – {{ $finPeriodo }} | Generado: {{ $fechaGeneracion }}</div>
        </div>

        <!-- Resumen ejecutivo -->
        <div class="summary-grid">
            <div class="summary-card {{ $datos['totales']['contratos'] > 0 ? 'warning' : 'success' }}">
                <div class="number">{{ $datos['totales']['contratos'] }}</div>
                <div class="label">Contratos por vencer (30 días)</div>
            </div>
            <div class="summary-card {{ $datos['totales']['vacaciones'] > 0 ? 'warning' : 'success' }}">
                <div class="number">{{ $datos['totales']['vacaciones'] }}</div>
                <div class="label">Vacaciones pendientes (>10 días)</div>
            </div>
            <div class="summary-card info">
                <div class="number">{{ $datos['totales']['licencias_por_vencer'] }}</div>
                <div class="label">Licencias por vencer (7 días)</div>
            </div>
            <div class="summary-card {{ $datos['totales']['licencias_vencidas'] > 0 ? 'warning' : 'success' }}">
                <div class="number">{{ $datos['totales']['licencias_vencidas'] }}</div>
                <div class="label">Licencias vencidas sin cerrar</div>
            </div>
        </div>

        <!-- Contratos por vencer -->
        <div class="section">
            <div class="section-title">
                Contratos por vencer (próximos 30 días)
                <span class="section-count">{{ $datos['totales']['contratos'] }} registros</span>
            </div>
            @if(empty($datos['contratos']))
                <div class="empty">No hay contratos por vencer en los próximos 30 días</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:25%">Trabajador</th>
                            <th style="width:15%">RUT</th>
                            <th style="width:15%">Cargo</th>
                            <th style="width:12%">Tipo</th>
                            <th style="width:10%">Vence</th>
                            <th style="width:8%">Días</th>
                            <th style="width:15%">Urgencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['contratos'] as $c)
                        <tr>
                            <td>{{ $c['trabajador'] }}</td>
                            <td>{{ $c['rut'] }}</td>
                            <td>{{ $c['cargo'] }}</td>
                            <td>{{ $c['tipo_contrato'] }}</td>
                            <td>{{ $c['fecha_termino'] }}</td>
                            <td style="text-align:center">{{ $c['dias_restantes'] }}</td>
                            <td><span class="badge badge-{{ strtolower($c['urgencia']) }}">{{ $c['urgencia'] }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Vacaciones pendientes -->
        <div class="section">
            <div class="section-title">
                Trabajadores con vacaciones pendientes (>10 días)
                <span class="section-count">{{ $datos['totales']['vacaciones'] }} trabajadores</span>
            </div>
            @if(empty($datos['vacaciones']))
                <div class="empty">Todos los trabajadores tienen menos de 10 días pendientes</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:25%">Trabajador</th>
                            <th style="width:15%">RUT</th>
                            <th style="width:15%">Cargo</th>
                            <th style="width:12%">Días pendientes</th>
                            <th style="width:15%">Ingreso</th>
                            <th style="width:18%">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['vacaciones'] as $v)
                        <tr>
                            <td>{{ $v['trabajador'] }}</td>
                            <td>{{ $v['rut'] }}</td>
                            <td>{{ $v['cargo'] }}</td>
                            <td style="text-align:center;font-weight:bold">{{ $v['dias_pendientes'] }}</td>
                            <td>{{ $v['fecha_ingreso'] }}</td>
                            <td>
                                @if($v['riesgo_multa'])
                                    <span class="badge badge-riesgo">⚠️ Riesgo multa DT</span>
                                @else
                                    <span class="badge badge-warning">Pendiente</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Licencias por vencer -->
        <div class="section">
            <div class="section-title">
                Licencias médicas por vencer (próximos 7 días)
                <span class="section-count">{{ $datos['totales']['licencias_por_vencer'] }} licencias</span>
            </div>
            @if(empty($datos['licencias_por_vencer']))
                <div class="empty">No hay licencias por vencer esta semana</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:22%">Trabajador</th>
                            <th style="width:13%">RUT</th>
                            <th style="width:15%">Tipo</th>
                            <th style="width:15%">Entidad</th>
                            <th style="width:10%">Fin</th>
                            <th style="width:8%">Días</th>
                            <th style="width:8%">Reposo</th>
                            <th style="width:9%">Restan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['licencias_por_vencer'] as $l)
                        <tr>
                            <td>{{ $l['trabajador'] }}</td>
                            <td>{{ $l['rut'] }}</td>
                            <td>{{ $l['tipo'] }}</td>
                            <td>{{ $l['entidad'] }}</td>
                            <td>{{ $l['fecha_fin'] }}</td>
                            <td style="text-align:center">{{ $l['dias_reposo'] }}</td>
                            <td style="text-align:center">{{ $l['dias_reposo'] }}</td>
                            <td style="text-align:center"><span class="badge badge-warning">{{ $l['dias_restantes'] }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Licencias vencidas sin cerrar -->
        <div class="section">
            <div class="section-title">
                Licencias médicas vencidas sin cerrar
                <span class="section-count">{{ $datos['totales']['licencias_vencidas'] }} licencias</span>
            </div>
            @if(empty($datos['licencias_vencidas']))
                <div class="empty">No hay licencias vencidas pendientes de cierre</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:22%">Trabajador</th>
                            <th style="width:13%">RUT</th>
                            <th style="width:15%">Tipo</th>
                            <th style="width:12%">Venció</th>
                            <th style="width:8%">Reposo</th>
                            <th style="width:10%">Atraso</th>
                            <th style="width:20%">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['licencias_vencidas'] as $l)
                        <tr>
                            <td>{{ $l['trabajador'] }}</td>
                            <td>{{ $l['rut'] }}</td>
                            <td>{{ $l['tipo'] }}</td>
                            <td>{{ $l['fecha_fin'] }}</td>
                            <td style="text-align:center">{{ $l['dias_reposo'] }}</td>
                            <td style="text-align:center"><span class="badge badge-danger">{{ $l['dias_atraso'] }} días</span></td>
                            <td>Requiere cierre manual en panel</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <!-- Notificaciones enviadas -->
        <div class="section">
            <div class="section-title">
                Notificaciones enviadas en el período
                <span class="section-count">{{ $datos['totales']['notificaciones_enviadas'] }} total</span>
            </div>
            @if(empty($datos['notificaciones_enviadas']))
                <div class="empty">No se enviaron notificaciones en este período</div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:50%">Tipo</th>
                            <th style="width:50%">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['notificaciones_enviadas'] as $tipo => $cantidad)
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $tipo)) }}</td>
                            <td style="text-align:center;font-weight:bold">{{ $cantidad }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="footer">
            Generado automáticamente por ERP ELEAM • {{ $fechaGeneracion }} • Reporte {{ ucfirst($periodo) }}
        </div>
    </div>
</body>
</html>