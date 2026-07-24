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
                <label for="ci">Nro. carnet de identidad @unless ($editando)<span class="req">*</span>@endunless</label>
                <input type="text" id="ci" name="ci" maxlength="12"
                       value="{{ old('ci', trim((string) ($persona->ci ?? ''))) }}"
                       @if ($editando) disabled @else required @endif>
                @if ($editando)
                    <div class="ayuda">El carnet es la clave del registro y no se puede cambiar.</div>
                @endif
                @error('ci') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="origenId">Expedido en</label>
                <input type="text" id="origenId" name="origenId" maxlength="3"
                       value="{{ old('origenId', trim((string) ($persona->origenId ?? ''))) }}">
                <div class="ayuda">Sigla del departamento: LP, CB, SC, OR, PT, TJ, CH, BE, PD</div>
                @error('origenId') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="paterno">Apellido paterno <span class="req">*</span></label>
                <input type="text" id="paterno" name="paterno" maxlength="25"
                       value="{{ old('paterno', trim((string) ($persona->paterno ?? ''))) }}" required>
                @error('paterno') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="materno">Apellido materno</label>
                <input type="text" id="materno" name="materno" maxlength="25"
                       value="{{ old('materno', trim((string) ($persona->materno ?? ''))) }}">
                @error('materno') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="nombres">Nombres <span class="req">*</span></label>
                <input type="text" id="nombres" name="nombres" maxlength="35"
                       value="{{ old('nombres', trim((string) ($persona->nombres ?? ''))) }}" required>
                @error('nombres') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="fechaNacimiento">Fecha de nacimiento <span class="req">*</span></label>
                <input type="date" id="fechaNacimiento" name="fechaNacimiento"
                       max="{{ now()->toDateString() }}"
                       value="{{ old('fechaNacimiento', $persona?->fechaNacimiento?->toDateString()) }}" required>
                @error('fechaNacimiento') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="lugarNacimiento">Lugar de nacimiento</label>
                <input type="text" id="lugarNacimiento" name="lugarNacimiento" maxlength="25"
                       value="{{ old('lugarNacimiento', trim((string) ($persona->lugarNacimiento ?? ''))) }}">
                @error('lugarNacimiento') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="sexo">Sexo <span class="req">*</span></label>
                <select id="sexo" name="sexo" required>
                    <option value="">Seleccione una opción</option>
                    <option value="F" @selected(old('sexo', $persona->sexo ?? '') === 'F')>Femenino</option>
                    <option value="M" @selected(old('sexo', $persona->sexo ?? '') === 'M')>Masculino</option>
                </select>
                @error('sexo') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="estadoCivil">Estado civil <span class="req">*</span></label>
                <select id="estadoCivil" name="estadoCivil" required>
                    <option value="">Seleccione una opción</option>
                    @foreach (['S' => 'Soltero(a)', 'C' => 'Casado(a)', 'D' => 'Divorciado(a)', 'V' => 'Viudo(a)'] as $codigo => $etiqueta)
                        <option value="{{ $codigo }}" @selected(old('estadoCivil', $persona->estadoCivil ?? '') === $codigo)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('estadoCivil') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Estudios</h2>
        <div class="campo">
            <label for="codigoProfesion">Profesión <span class="req">*</span></label>
            <select id="codigoProfesion" name="codigoProfesion" required>
                <option value="">Seleccione una opción</option>
                @foreach ($profesiones as $codigo => $nombre)
                    <option value="{{ $codigo }}" @selected(old('codigoProfesion', $persona->codigoProfesion ?? '00') === $codigo)>{{ $nombre }}</option>
                @endforeach
            </select>
            @error('codigoProfesion') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="campo">
            <label for="nivelEstudio">Nivel</label>
            <select id="nivelEstudio" name="nivelEstudio">
                <option value="">Seleccione una opción</option>
                @foreach ($niveles as $nivel)
                    <option value="{{ $nivel }}" @selected(old('nivelEstudio', trim((string) ($persona->nivelEstudio ?? ''))) === $nivel)>{{ $nivel }}</option>
                @endforeach
            </select>
            @error('nivelEstudio') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="tarjeta">
        <h2>Contactos</h2>
        <div class="grid-2">
            <div class="campo">
                <label for="telefono">Teléfonos</label>
                <input type="text" id="telefono" name="telefono" maxlength="20"
                       value="{{ old('telefono', trim((string) ($persona->telefono ?? ''))) }}">
                @error('telefono') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" maxlength="40"
                       value="{{ old('direccion', trim((string) ($persona->direccion ?? ''))) }}">
                @error('direccion') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="correo">E-mail</label>
                <input type="email" id="correo" name="correo" maxlength="40"
                       value="{{ old('correo', trim((string) ($persona->correo ?? ''))) }}">
                @error('correo') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Control de asistencia</h2>
        {{-- Deshabilitada por ahora: el PIN y la marcación con contraseña se
             siguen gestionando desde el sistema de escritorio del SIA. --}}
        <fieldset disabled>
            <div class="campo">
                <label for="pinReloj">PIN reloj lector de huellas</label>
                <input type="text" id="pinReloj" name="pinReloj" maxlength="10"
                       value="{{ trim((string) ($persona->pinReloj ?? '')) }}">
            </div>

            <div class="campo check">
                <input type="checkbox" id="marcaDirecta" name="marcaDirecta" value="1"
                       @checked($persona->marcaDirecta ?? false)>
                <label for="marcaDirecta" style="margin: 0;">Puede marcar con contraseña</label>
            </div>
        </fieldset>
    </div>
</div>
