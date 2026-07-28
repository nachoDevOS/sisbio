@props([
    'accion',
    'mensaje' => 'El registro se elimina de la base y no se puede recuperar.',
    'variante' => 'icono',
    'etiqueta' => 'Eliminar',
    'alHacerClic' => null,
])

@php
    // `alHacerClic` corre antes de abrir el modal: sirve para cerrar el menú
    // desplegable desde el que se eligió la baja (p. ej. `open = false`).
    $abrirModal = trim(($alHacerClic ? $alHacerClic.'; ' : '')
        .'$store.eliminar.abrir('.\Illuminate\Support\Js::from($accion).', '.\Illuminate\Support\Js::from($mensaje).')');
@endphp

{{-- Botón de baja. No envía nada por sí mismo: carga la URL del destroy y el
     texto de lo que se borra en el modal global de eliminación (layouts.app),
     que es el que pide el motivo y manda el DELETE. Así todas las bajas del
     sistema piden la misma confirmación y quedan auditadas igual.

     Variantes: `icono` (acciones de fila) y `menu` (opción dentro de un
     .dropdown-menu__peligro, que le pone el estilo). --}}
@if ($variante === 'menu')
    <button type="button" {{ $attributes }} x-on:click="{!! $abrirModal !!}">
        <x-heroicon-o-trash />{{ $etiqueta }}
    </button>
@else
    <button type="button" {{ $attributes->merge(['class' => 'btn-icon btn-icon--peligro']) }}
            title="{{ $etiqueta }}" aria-label="{{ $etiqueta }}"
            x-on:click="{!! $abrirModal !!}">
        <x-heroicon-o-trash />
    </button>
@endif
