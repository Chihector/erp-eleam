<?php

use App\Models\Finiquito;
use App\Models\Liquidacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/liquidaciones/{liquidacion}/pdf', function (Liquidacion $liquidacion) {
    $turnos = $liquidacion->trabajador->turnos()
        ->whereMonth('fecha_inicio', $liquidacion->mes)
        ->whereYear('fecha_inicio', $liquidacion->anio)
        ->orderBy('fecha_inicio')
        ->get();

    $pdf = Pdf::loadView('pdf.liquidacion', [
        'liquidacion' => $liquidacion,
        'turnos' => $turnos,
    ]);

    $nombreArchivo = "liquidacion-{$liquidacion->trabajador->rut}-".str_pad($liquidacion->mes, 2, '0', STR_PAD_LEFT)."-{$liquidacion->anio}.pdf";

    if (request()->has('download')) {
        return $pdf->download($nombreArchivo);
    }

    return $pdf->stream($nombreArchivo);
})->name('liquidaciones.pdf')->middleware(['web', 'auth'])->can('view', 'liquidacion');

Route::get('/admin/finiquitos/{finiquito}/pdf', function (Finiquito $finiquito) {
    $pdf = Pdf::loadView('pdf.finiquito', [
        'finiquito' => $finiquito,
    ]);

    $nombreArchivo = "finiquito-{$finiquito->trabajador->rut}-".$finiquito->fecha_termino->format('d-m-Y').'.pdf';

    if (request()->has('download')) {
        return $pdf->download($nombreArchivo);
    }

    return $pdf->stream($nombreArchivo);
})->name('finiquitos.pdf')->middleware(['web', 'auth'])->can('view', 'finiquito');
