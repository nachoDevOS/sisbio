{{-- Campos compartidos por alta y edición. $horario puede no existir (alta).
     Réplica del formulario «Añadir/editar horario» del SIA de escritorio. --}}
@php
    $horario ??= null;
    $hm = fn ($valor) => $valor?->format('H:i');
@endphp

<div class="form-grid" style="grid-template-columns: 1fr 1fr;"
     x-data="{
         dia: '{{ old('Dia', $horario->Dia ?? '') }}',
         hEntrada: '{{ old('HEntrada', $hm($horario->HEntrada ?? null)) }}',
         hSalida: '{{ old('HSalida', $hm($horario->HSalida ?? null)) }}',
         nombre: '',
         abreviaturas: { 1: 'DOM', 2: 'LUN', 3: 'MAR', 4: 'MIE', 5: 'JUE', 6: 'VIE', 7: 'SAB' },
     }"
     x-effect="nombre = (abreviaturas[dia] && hEntrada && hSalida) ? (abreviaturas[dia] + ': ' + hEntrada + ' - ' + hSalida) : ''">
    <div class="tarjeta" style="grid-column: 1 / -1;">
        <h2>Descripción del horario</h2>
        <div class="grid-2">
            <div class="campo">
                <label for="Dia">Día <span class="req">*</span></label>
                <select id="Dia" name="Dia" x-model="dia" required>
                    <option value="">Seleccione una opción</option>
                    @foreach (\App\Models\Sia\DiaTurno::DIAS as $numero => $nombre)
                        <option value="{{ $numero }}" @selected((string) old('Dia', $horario->Dia ?? '') === (string) $numero)>{{ $nombre }}</option>
                    @endforeach
                </select>
                @error('Dia') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="campo">
                <label for="NombreTurno">Nombre del horario <span class="req">*</span></label>
                <input type="text" id="NombreTurno" name="NombreTurno" maxlength="25"
                       :value="nombre" readonly required>
                <div class="ayuda">Se arma solo con el día y las horas de entrada/salida.</div>
                @error('NombreTurno') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Entrada</h2>
        <div class="grid-2">
            <div class="campo">
                <label for="HEntrada">Hora de entrada <span class="req">*</span></label>
                <input type="time" id="HEntrada" name="HEntrada" x-model="hEntrada" value="{{ old('HEntrada', $hm($horario->HEntrada ?? null)) }}" required>
                @error('HEntrada') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="campo">
                <label for="HTolerancia">Hora de tolerancia de entrada <span class="req">*</span></label>
                <input type="time" id="HTolerancia" name="HTolerancia" value="{{ old('HTolerancia', $hm($horario->HTolerancia ?? null)) }}" required>
                @error('HTolerancia') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="campo">
                <label for="EMinima">Mínima hora de entrada <span class="req">*</span></label>
                <input type="time" id="EMinima" name="EMinima" value="{{ old('EMinima', $hm($horario->EMinima ?? null)) }}" required>
                @error('EMinima') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="campo">
                <label for="EMaxima">Máxima hora de entrada <span class="req">*</span></label>
                <input type="time" id="EMaxima" name="EMaxima" value="{{ old('EMaxima', $hm($horario->EMaxima ?? null)) }}" required>
                @error('EMaxima') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Salida</h2>
        <div class="grid-2">
            <div class="campo">
                <label for="HSalida">Hora de salida <span class="req">*</span></label>
                <input type="time" id="HSalida" name="HSalida" x-model="hSalida" value="{{ old('HSalida', $hm($horario->HSalida ?? null)) }}" required>
                @error('HSalida') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="campo">
                <label for="STolerancia">Hora de tolerancia de salida <span class="req">*</span></label>
                <input type="time" id="STolerancia" name="STolerancia" value="{{ old('STolerancia', $hm($horario->STolerancia ?? null)) }}" required>
                @error('STolerancia') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="campo">
                <label for="SMinima">Mínima hora de salida <span class="req">*</span></label>
                <input type="time" id="SMinima" name="SMinima" value="{{ old('SMinima', $hm($horario->SMinima ?? null)) }}" required>
                @error('SMinima') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="campo">
                <label for="SMaxima">Máxima hora de salida <span class="req">*</span></label>
                <input type="time" id="SMaxima" name="SMaxima" value="{{ old('SMaxima', $hm($horario->SMaxima ?? null)) }}" required>
                @error('SMaxima') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="campo check">
            <input type="checkbox" id="SiguienteDia" name="SiguienteDia" value="1"
                   @checked(old('SiguienteDia', $horario->SiguienteDia ?? false))>
            <label for="SiguienteDia" style="margin: 0;">La salida corresponde al día siguiente</label>
        </div>
    </div>

    <div class="tarjeta" style="grid-column: 1 / -1;">
        <h2>Horas trabajadas</h2>
        <div class="campo" style="max-width: 14rem; margin-bottom: 0;">
            <label for="HTrabajadas">Horas trabajadas <span class="req">*</span></label>
            <input type="number" id="HTrabajadas" name="HTrabajadas" step="0.01" min="0" max="24"
                   value="{{ old('HTrabajadas', $horario->HTrabajadas ?? '0.00') }}" required>
            @error('HTrabajadas') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
