{{-- Selector "por página" arriba de la tabla, a la izquierda (estilo AdminLTE). --}}
{{-- Se inyecta vía TablesRenderHook::TOOLBAR_START; el de abajo se oculta por CSS. --}}
@php
    $livewire = \Livewire\Livewire::current();
    $tabla = ($livewire instanceof \Filament\Tables\Contracts\HasTable) ? $livewire->getTable() : null;
    $opciones = ($tabla && $tabla->isPaginated()) ? $tabla->getPaginationPageOptions() : [];
@endphp

@if (count($opciones) > 1)
    <label class="siscor-per-page-top">
        <x-filament::input.wrapper
            :prefix="__('filament::components/pagination.fields.records_per_page.label')"
        >
            <x-filament::input.select wire:model.live="tableRecordsPerPage">
                @foreach ($opciones as $opcion)
                    <option value="{{ $opcion }}">
                        {{ $opcion === 'all' ? __('filament::components/pagination.fields.records_per_page.options.all') : $opcion }}
                    </option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </label>
@endif
