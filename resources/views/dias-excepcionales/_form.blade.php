{{-- Campos compartidos por alta y edición. $diaExcepcional puede no existir (alta). --}}
@php
    $diaExcepcional ??= null;
@endphp

<div class="campo">
    <label for="fecha">Fecha <span class="req">*</span></label>
    <input type="date" id="fecha" name="fecha"
           value="{{ old('fecha', $diaExcepcional?->fecha?->format('Y-m-d') ?? '') }}" required>
    @error('fecha') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="motivoInasistencia">Motivo de inasistencia general <span class="req">*</span></label>
    <input type="text" id="motivoInasistencia" name="motivoInasistencia" maxlength="255"
           value="{{ old('motivoInasistencia', $diaExcepcional->motivoInasistencia ?? '') }}" required>
    <div class="ayuda">Ej. «FERIADO POR CARNAVAL», «ANIVERSARIO DEL BENI».</div>
    @error('motivoInasistencia') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="observacion">Observación</label>
    <input type="text" id="observacion" name="observacion" maxlength="255"
           value="{{ old('observacion', $diaExcepcional->observacion ?? '') }}">
    @error('observacion') <div class="error">{{ $message }}</div> @enderror
</div>
