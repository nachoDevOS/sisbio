{{-- Campos compartidos por alta y edición. $persona puede no existir (alta). --}}
{{-- Cards por sección, replicando el formulario del SIA de escritorio. --}}
@php
    $persona ??= null;
    $editando = $persona !== null;
@endphp

<div class="form-grid">
    <div class="tarjeta">
        <h2>Datos personales</h2>
        <div class="grid-2">
            <div class="campo">
                <label for="IdPersona">Nro. carnet de identidad @unless ($editando)<span class="req">*</span>@endunless</label>
                <input type="text" id="IdPersona" name="IdPersona" maxlength="12"
                       value="{{ old('IdPersona', trim((string) ($persona->IdPersona ?? ''))) }}"
                       @if ($editando) disabled @else required @endif>
                @if ($editando)
                    <div class="ayuda">El carnet es la clave del registro y no se puede cambiar.</div>
                @endif
                @error('IdPersona') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="OrigenId">Expedido en</label>
                <input type="text" id="OrigenId" name="OrigenId" maxlength="3"
                       value="{{ old('OrigenId', trim((string) ($persona->OrigenId ?? ''))) }}">
                <div class="ayuda">Sigla del departamento: LP, CB, SC, OR, PT, TJ, CH, BE, PD</div>
                @error('OrigenId') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="Paterno">Apellido paterno <span class="req">*</span></label>
                <input type="text" id="Paterno" name="Paterno" maxlength="25"
                       value="{{ old('Paterno', trim((string) ($persona->Paterno ?? ''))) }}" required>
                @error('Paterno') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="Materno">Apellido materno</label>
                <input type="text" id="Materno" name="Materno" maxlength="25"
                       value="{{ old('Materno', trim((string) ($persona->Materno ?? ''))) }}">
                @error('Materno') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="Nombres">Nombres <span class="req">*</span></label>
                <input type="text" id="Nombres" name="Nombres" maxlength="35"
                       value="{{ old('Nombres', trim((string) ($persona->Nombres ?? ''))) }}" required>
                @error('Nombres') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="FechaNacimiento">Fecha de nacimiento <span class="req">*</span></label>
                <input type="date" id="FechaNacimiento" name="FechaNacimiento"
                       max="{{ now()->toDateString() }}"
                       value="{{ old('FechaNacimiento', $persona?->FechaNacimiento?->toDateString()) }}" required>
                @error('FechaNacimiento') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="LugarNacimiento">Lugar de nacimiento</label>
                <input type="text" id="LugarNacimiento" name="LugarNacimiento" maxlength="25"
                       value="{{ old('LugarNacimiento', trim((string) ($persona->LugarNacimiento ?? ''))) }}">
                @error('LugarNacimiento') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="Sexo">Sexo <span class="req">*</span></label>
                <select id="Sexo" name="Sexo" required>
                    <option value="">Seleccione una opción</option>
                    <option value="F" @selected(old('Sexo', $persona->Sexo ?? '') === 'F')>Femenino</option>
                    <option value="M" @selected(old('Sexo', $persona->Sexo ?? '') === 'M')>Masculino</option>
                </select>
                @error('Sexo') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="EstadoCivil">Estado civil <span class="req">*</span></label>
                <select id="EstadoCivil" name="EstadoCivil" required>
                    <option value="">Seleccione una opción</option>
                    @foreach (['S' => 'Soltero(a)', 'C' => 'Casado(a)', 'D' => 'Divorciado(a)', 'V' => 'Viudo(a)'] as $codigo => $etiqueta)
                        <option value="{{ $codigo }}" @selected(old('EstadoCivil', $persona->EstadoCivil ?? '') === $codigo)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('EstadoCivil') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Estudios</h2>
        <div class="campo">
            <label for="CodigoProfesion">Profesión <span class="req">*</span></label>
            <select id="CodigoProfesion" name="CodigoProfesion" required>
                <option value="">Seleccione una opción</option>
                @foreach ($profesiones as $codigo => $nombre)
                    <option value="{{ $codigo }}" @selected(old('CodigoProfesion', $persona->CodigoProfesion ?? '00') === $codigo)>{{ $nombre }}</option>
                @endforeach
            </select>
            @error('CodigoProfesion') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="campo">
            <label for="NivelEstudio">Nivel</label>
            <select id="NivelEstudio" name="NivelEstudio">
                <option value="">Seleccione una opción</option>
                @foreach ($niveles as $nivel)
                    <option value="{{ $nivel }}" @selected(old('NivelEstudio', trim((string) ($persona->NivelEstudio ?? ''))) === $nivel)>{{ $nivel }}</option>
                @endforeach
            </select>
            @error('NivelEstudio') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="tarjeta">
        <h2>Contactos</h2>
        <div class="grid-2">
            <div class="campo">
                <label for="Telefono">Teléfonos</label>
                <input type="text" id="Telefono" name="Telefono" maxlength="20"
                       value="{{ old('Telefono', trim((string) ($persona->Telefono ?? ''))) }}">
                @error('Telefono') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="Direccion">Dirección</label>
                <input type="text" id="Direccion" name="Direccion" maxlength="40"
                       value="{{ old('Direccion', trim((string) ($persona->Direccion ?? ''))) }}">
                @error('Direccion') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="CorreoE">E-mail</label>
                <input type="email" id="CorreoE" name="CorreoE" maxlength="40"
                       value="{{ old('CorreoE', trim((string) ($persona->CorreoE ?? ''))) }}">
                @error('CorreoE') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Control de asistencia</h2>
        {{-- Deshabilitada por ahora: el PIN y la marcación con contraseña se
             siguen gestionando desde el sistema de escritorio del SIA. --}}
        <fieldset disabled>
            <div class="campo">
                <label for="PinReloj">PIN reloj lector de huellas</label>
                <input type="text" id="PinReloj" name="PinReloj" maxlength="10"
                       value="{{ trim((string) ($persona->PinReloj ?? '')) }}">
            </div>

            <div class="campo check">
                <input type="checkbox" id="MarcaDirecta" name="MarcaDirecta" value="1"
                       @checked($persona->MarcaDirecta ?? false)>
                <label for="MarcaDirecta" style="margin: 0;">Puede marcar con contraseña</label>
            </div>
        </fieldset>
    </div>
</div>
