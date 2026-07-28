<div class="card">
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Turno</th>
                <th>Alcance</th>
                <th>Haberes</th>
                <th>Motivo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($licencias as $licencia)
                <tr>
                    <td><strong>{{ $licencia->fecha?->format('d/m/Y') }}</strong></td>
                    <td>{{ $licencia->resumen_turno }}</td>
                    <td>
                        @if ($licencia->tCompleto)
                            <span class="pill pill--info">Turno completo</span>
                        @else
                            {{ $licencia->lEntra?->format('H:i') ?? '—' }} – {{ $licencia->lSale?->format('H:i') ?? '—' }}
                        @endif
                    </td>
                    <td>
                        <span class="pill {{ $licencia->goceHaberes ? 'pill--ok' : 'pill--no' }}">
                            {{ $licencia->goceHaberes ? 'Con goce' : 'Sin goce' }}
                        </span>
                    </td>
                    <td>{{ $licencia->motivo ?: '—' }}</td>
                    <td class="acciones">
                        @can('delete', $licencia)
                            <x-boton-eliminar :accion="route('licencias.destroy', $licencia)"
                                              :mensaje="'Se elimina la licencia del '.$licencia->fecha?->format('d/m/Y').'.'" />
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">El funcionario no tiene licencias registradas.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="paginacion">{{ $licencias->links() }}</div>
