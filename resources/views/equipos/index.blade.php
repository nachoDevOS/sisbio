@extends('layouts.app')

@section('titulo', 'Equipos biométricos')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-computer-desktop /></span>
            <h1>Equipos biométricos</h1>
        </div>
        <a href="{{ route('equipos.create') }}" class="btn"><x-heroicon-o-plus />Nuevo equipo</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>IP</th>
                    <th>Puerto</th>
                    <th>Ubicación</th>
                    <th>Algoritmo</th>
                    <th>En línea</th>
                    <th>Activo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equipos as $equipo)
                    <tr>
                        <td><a href="{{ route('equipos.show', $equipo) }}"><strong>{{ $equipo->nombre }}</strong></a></td>
                        <td>{{ $equipo->ip }}</td>
                        <td>{{ $equipo->puerto }}</td>
                        <td>{{ $equipo->ubicacion ?? '—' }}</td>
                        <td>{{ $equipo->algoritmo ?? 'Sin detectar' }}</td>
                        <td>
                            <span class="pill {{ $equipo->en_linea ? 'pill--ok' : 'pill--no' }}">
                                {{ $equipo->en_linea ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <span class="pill {{ $equipo->activo ? 'pill--ok' : 'pill--no' }}">
                                {{ $equipo->activo ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <div class="acciones">
                                <form action="{{ route('equipos.probar-conexion', $equipo) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Probar conexión" aria-label="Probar conexión"><x-heroicon-o-signal /></button>
                                </form>
                                <a href="{{ route('equipos.edit', $equipo) }}" class="btn-icon" title="Editar" aria-label="Editar"><x-heroicon-o-pencil-square /></a>
                                <div class="dropdown"
                                     x-data="{
                                         open: false,
                                         modal: false,
                                         enviando: false,
                                         exportando: false,
                                         errorCsv: '',
                                         desde: '{{ now()->startOfMonth()->toDateString() }}',
                                         hasta: '{{ now()->toDateString() }}',
                                         async descargarCsv() {
                                             if (this.exportando) { return; }
                                             this.exportando = true;
                                             this.errorCsv = '';
                                             try {
                                                 const url = `{{ route('equipos.marcaciones.exportar', $equipo) }}?desde=${this.desde}&hasta=${this.hasta}`;
                                                 const resp = await fetch(url, { headers: { 'Accept': 'text/csv' } });
                                                 const tipo = resp.headers.get('Content-Type') || '';
                                                 if (! resp.ok || ! tipo.includes('csv')) {
                                                     this.errorCsv = 'No se pudo leer el equipo. Revisá que esté en línea e intentá de nuevo.';
                                                     return;
                                                 }
                                                 const blob = await resp.blob();
                                                 const enlace = document.createElement('a');
                                                 enlace.href = URL.createObjectURL(blob);
                                                 enlace.download = `marcaciones-{{ str($equipo->nombre)->slug() }}-${this.hasta || new Date().toISOString().slice(0, 10)}.csv`;
                                                 document.body.appendChild(enlace);
                                                 enlace.click();
                                                 enlace.remove();
                                                 URL.revokeObjectURL(enlace.href);
                                                 this.modal = false;
                                             } catch (e) {
                                                 this.errorCsv = 'No se pudo descargar. Revisá la conexión y probá otra vez.';
                                             } finally {
                                                 this.exportando = false;
                                             }
                                         },
                                     }"
                                     x-on:click.outside="open = false">
                                    <button type="button" class="dropdown-toggle" x-on:click="open = !open" aria-haspopup="true" :aria-expanded="open">
                                        Mas <x-heroicon-o-chevron-down />
                                    </button>
                                    <div class="dropdown-menu" x-show="open" x-cloak x-transition.opacity.duration.100ms>
                                        <button type="button" x-on:click="modal = true; open = false"><x-heroicon-o-arrow-down-tray />Exportar marcaciones</button>
                                        <form action="{{ route('equipos.destroy', $equipo) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar el equipo «{{ $equipo->nombre }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="peligro"><x-heroicon-o-trash />Eliminar</button>
                                        </form>
                                    </div>

                                    {{-- Modal de rango: se elige una vez y sirve para las dos acciones
                                         (descargar CSV o enviar directo a la BD del SIA), sin pasar por
                                         la vista en vivo que renderiza la tabla y es más lenta. --}}
                                    <div class="modal-fondo" x-show="modal" x-cloak
                                         x-on:click.self="modal = false" x-on:keydown.escape.window="modal = false">
                                        <div class="modal-caja">
                                            <h2>Marcaciones de «{{ $equipo->nombre }}»</h2>
                                            <div class="grid-2">
                                                <div class="campo">
                                                    <label for="desde-{{ $equipo->id }}">Desde</label>
                                                    <input type="date" id="desde-{{ $equipo->id }}" x-model="desde">
                                                </div>
                                                <div class="campo">
                                                    <label for="hasta-{{ $equipo->id }}">Hasta</label>
                                                    <input type="date" id="hasta-{{ $equipo->id }}" x-model="hasta">
                                                </div>
                                            </div>
                                            <div class="modal-acciones">
                                                <button type="button" class="btn btn--gris" x-on:click="modal = false" :disabled="exportando || enviando">Cancelar</button>
                                                <button type="button" class="btn btn--gris" x-on:click="descargarCsv()" :disabled="exportando">
                                                    <span class="btn__contenido" x-show="! exportando"><x-heroicon-o-arrow-down-tray />Descargar CSV</span>
                                                    <span class="btn__contenido" x-show="exportando" x-cloak><span class="spinner-anillo"></span>Exportando…</span>
                                                </button>
                                                <form method="POST" action="{{ route('equipos.marcaciones.sincronizar', $equipo) }}"
                                                      x-on:submit="if (! confirm('¿Enviar las marcaciones del rango a la base del SIA?')) { $event.preventDefault(); return; } enviando = true">
                                                    @csrf
                                                    <input type="hidden" name="desde" :value="desde">
                                                    <input type="hidden" name="hasta" :value="hasta">
                                                    <button type="submit" class="btn" :disabled="enviando">
                                                        <span class="btn__contenido" x-show="! enviando"><x-heroicon-o-arrow-up-tray />Enviar a la BD</span>
                                                        <span class="btn__contenido" x-show="enviando" x-cloak><span class="spinner-anillo"></span>Enviando…</span>
                                                    </button>
                                                </form>
                                            </div>
                                            <p x-show="errorCsv" x-cloak x-text="errorCsv"
                                               style="margin: .75rem 0 0; color: #dc2626; font-size: .8125rem;"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="vacio">Aún no hay equipos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">
        {{ $equipos->links() }}
    </div>
@endsection
