@extends('layouts.template-print-alt')

@section('page_title', 'Reporte de marcaciones')

@section('css')
    <style>
        /* Bordes colapsados: mismas líneas y en el mismo lugar, pero sin el
           borde inferior "fantasma" que el modelo de bordes separados dibuja
           al cortar la tabla al final de la página. */
        table[border] { border-collapse: collapse; }
        table[border] th,
        table[border] td { border: 1px solid #808080; }

        /* No parte una fila por la mitad y repite el encabezado en cada hoja. */
        @media print {
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            .leyenda, .firmas { page-break-inside: avoid; }
        }
    </style>
@endsection

@php
    use Illuminate\Support\Carbon;

    $nombreEmpleado = collect([$persona->Paterno, $persona->Materno, $persona->Nombres])
        ->map(fn ($parte) => trim((string) $parte))
        ->filter()
        ->implode(' ');
    $pin = trim((string) $persona->PinReloj) ?: '—';
    $desdeFmt = $desde ? Carbon::parse($desde)->format('j/n/Y') : '—';
    $hastaFmt = $hasta ? Carbon::parse($hasta)->format('j/n/Y') : '—';

    // QR con el resumen del reporte (para verificación/archivo). Se quita el
    // prólogo XML del SVG para poder incrustarlo dentro del HTML.
    $qrTexto = "REPORTE DE MARCACIONES - GAD BENI\n"
        ."Funcionario: {$nombreEmpleado}\n"
        .'CI: '.trim((string) $persona->IdPersona)." | PIN: {$pin}\n"
        ."Rango: {$desdeFmt} a {$hastaFmt}\n"
        .'Total: '.$marcaciones->count()."\n"
        .'Impreso: '.now()->format('d/m/Y H:i:s');
    $qrSvg = preg_replace('/^<\?xml.*?\?>\s*/s', '', \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(100)->margin(0)->generate($qrTexto));
@endphp

@section('content')
    <table width="100%">
        <tr>
            <td style="width: 20%"><img src="{{ asset('image/icon.png') }}" alt="GADBENI" width="140px"></td>
            <td style="text-align: center; width: 60%">
                <h3 style="margin-bottom: 0px; margin-top: 5px">
                    GOBIERNO AUTONOMO DEPARTAMENTAL DEL BENI
                </h3>
                <h4 style="margin-bottom: 0px; margin-top: 5px">
                    REPORTE DE MARCACIONES
                    <br>
                    TRINIDAD
                </h4>
                <small>Marcaciones sin procesar</small>
            </td>
            <td style="text-align: right; width: 20%">
                <div>{!! $qrSvg !!}</div>
                <small style="font-size: 11px; font-weight: 100">
                    Impreso por: {{ auth()->user()?->name }}
                    <br>
                    {{ now()->format('d/m/Y H:i:s') }}
                </small>
            </td>
        </tr>
    </table>

    <p style="font-size: 13px; margin: 10px 0 5px;">
        <b>Empleado:</b> {{ $nombreEmpleado }}, <b>PIN Reloj:</b> {{ $pin }}, desde el {{ $desdeFmt }} hasta el {{ $hastaFmt }}
    </p>

    <table style="width: 100%; font-size: 12px" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th style="width: 40px">N&deg;</th>
                <th style="text-align: center">Fecha</th>
                <th style="text-align: center">Hora</th>
                <th style="text-align: center">Tipo Marc.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($marcaciones as $marcacion)
                <tr>
                    <td style="text-align: center">{{ $loop->iteration }}</td>
                    <td style="text-align: center">{{ $marcacion->Fecha?->format('j/n/Y') }}</td>
                    <td style="text-align: center">{{ $marcacion->Hora?->format('H:i:s') }}</td>
                    <td style="text-align: center">{{ trim((string) $marcacion->Tipo) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center">No se encontraron marcaciones en el rango.</td>
                </tr>
            @endforelse
            <tr>
                <th colspan="3" style="text-align: right">Total registros:</th>
                <th style="text-align: center">{{ $marcaciones->count() }}</th>
            </tr>
        </tbody>
    </table>

    <div class="leyenda" style="font-size: 12px; margin-top: 10px;">
        <b>Leyenda "Tipo de marcación":</b>
        <br>&nbsp;&nbsp;&nbsp;&nbsp;R = descarga directa desde reloj
        <br>&nbsp;&nbsp;&nbsp;&nbsp;M = anotación manual
        <br>&nbsp;&nbsp;&nbsp;&nbsp;A = descarga desde archivo (USB)
    </div>

    <br><br><br>
    <table width="100%" class="firmas">
        <tr>
            <td style="text-align: center">
                ______________________
                <br>
                <b>Firma Responsable</b>
            </td>
            <td style="text-align: center">
                ______________________
                <br>
                <b>Firma RR. HH.</b>
            </td>
        </tr>
    </table>
@endsection
