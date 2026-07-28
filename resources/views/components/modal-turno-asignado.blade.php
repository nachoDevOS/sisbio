@props([
    'ci',
    'origen' => '',
    'etiqueta' => 'Asignar turno',
])

@php
    $ciFijo = trim((string) $ci);
    $sufijo = 'ci'.preg_replace('/\W/', '', $ciFijo);
    // Varios formularios de la ficha comparten nombres de campo (`desde`,
    // `hasta`…): el marcador dice cuál rebotó, para reabrir solo ese modal.
    $abierto = old('_form') === 'turno-asignado' ? 'true' : 'false';
    $turnos = \App\Models\Turno::query()->ordenado()->get();
    $turnosPorDia = $turnos->groupBy(fn ($turno): int => (int) $turno->dia);
    $abreviar = fn (?int $dia): string => mb_strtoupper(mb_substr(\App\Models\Turno::DIAS[$dia] ?? '—', 0, 3));
@endphp

{{-- Asignarle un turno a un funcionario ya conocido, sin salir de su ficha. El
     formulario de `turnos-asignados/create` sigue existiendo para cuando se
     entra por el listado y hay que elegir a la persona. --}}
@can('create', \App\Models\AsignacionTurno::class)
    <div x-data="{ abierto: {{ $abierto }} }" {{ $attributes }}>
        <button type="button" class="btn" x-on:click="abierto = true">
            <x-heroicon-o-plus />{{ $etiqueta }}
        </button>

        <div class="modal-fondo" x-show="abierto" x-cloak
             x-on:click.self="abierto = false" x-on:keydown.escape.window="abierto = false">
            <div class="modal-caja modal-caja--ancha">
                <h2>Asignar turno a CI {{ $ciFijo }}</h2>

                <form method="POST" action="{{ route('turnos-asignados.store') }}">
                    @csrf
                    <input type="hidden" name="_form" value="turno-asignado">
                    <input type="hidden" name="ci" value="{{ $ciFijo }}">
                    <input type="hidden" name="origen" value="{{ $origen }}">
                    @error('ci') <div class="error">{{ $message }}</div> @enderror

                    <div class="campo">
                        <label for="turno-{{ $sufijo }}">Turno <span class="req">*</span></label>
                        <select id="turno-{{ $sufijo }}" name="turno_id" required>
                            <option value="">Seleccione un turno</option>
                            @foreach (\App\Models\Turno::DIAS as $numero => $nombreDia)
                                @if ($turnosPorDia->has($numero))
                                    <optgroup label="{{ $nombreDia }}">
                                        @foreach ($turnosPorDia[$numero] as $turno)
                                            <option value="{{ $turno->id }}" @selected((string) old('turno_id') === (string) $turno->id)>
                                                {{ $abreviar((int) $turno->dia) }} · {{ trim((string) $turno->nombreTurno) }}
                                                ({{ $turno->hEntrada?->format('H:i') }} – {{ $turno->hSalida?->format('H:i') }})
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                        @if ($turnos->isEmpty())
                            <div class="ayuda">
                                No hay turnos cargados todavía.
                                <a href="{{ route('horarios.create') }}">Creá uno primero</a>.
                            </div>
                        @endif
                        @error('turno_id') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="grid-2">
                        <div class="campo">
                            <label for="turno-desde-{{ $sufijo }}">Desde <span class="req">*</span></label>
                            <input type="date" id="turno-desde-{{ $sufijo }}" name="desde"
                                   value="{{ old('desde', now()->toDateString()) }}" required>
                            @error('desde') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div class="campo">
                            <label for="turno-hasta-{{ $sufijo }}">Hasta <span class="req">*</span></label>
                            <input type="date" id="turno-hasta-{{ $sufijo }}" name="hasta"
                                   value="{{ old('hasta', now()->endOfYear()->toDateString()) }}" required>
                            @error('hasta') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="campo">
                        <label for="turno-obs-{{ $sufijo }}">Observación</label>
                        <input type="text" id="turno-obs-{{ $sufijo }}" name="observacion" maxlength="1000"
                               value="{{ old('observacion') }}">
                        @error('observacion') <div class="error">{{ $message }}</div> @enderror
                    </div>

                    <div class="modal-acciones">
                        <button type="button" class="btn btn--gris" x-on:click="abierto = false">
                            <x-heroicon-o-x-mark />Cancelar
                        </button>
                        <button type="submit" class="btn"><x-heroicon-o-check />Asignar turno</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
