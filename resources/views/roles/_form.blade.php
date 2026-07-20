{{-- Campos compartidos por alta y edición de rol. $role null = alta. --}}
@php
    $role ??= null;
    $permisosActuales = old('permisos', $permisosActuales ?? []);
@endphp

<div class="campo">
    <label for="name">Nombre del rol</label>
    <input type="text" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" required>
    @error('name') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label>Permisos</label>
    @error('permisos') <div class="error">{{ $message }}</div> @enderror

    <table>
        <thead>
            <tr>
                <th>Recurso</th>
                @foreach (\App\Policies\RolePolicy::ABILIDADES as $etiquetaHabilidad)
                    <th>{{ $etiquetaHabilidad }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (\App\Policies\RolePolicy::MODELOS as $modelo => $etiquetaModelo)
                <tr>
                    <td><strong>{{ $etiquetaModelo }}</strong></td>
                    @foreach (\App\Policies\RolePolicy::ABILIDADES as $habilidad => $etiquetaHabilidad)
                        @php $nombrePermiso = "{$habilidad}:{$modelo}"; @endphp
                        <td>
                            <input type="checkbox" name="permisos[]" value="{{ $nombrePermiso }}"
                                @checked(in_array($nombrePermiso, $permisosActuales))>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
