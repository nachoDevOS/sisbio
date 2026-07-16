{{-- Campos compartidos por alta y edición de usuario. $usuario null = alta. --}}
@php
    $usuario ??= null;
    $rolesActuales = old('roles', isset($usuario) ? $usuario->roles->pluck('id')->all() : []);
@endphp

<div class="campo">
    <label for="name">Nombre</label>
    <input type="text" id="name" name="name" value="{{ old('name', $usuario->name ?? '') }}" required>
    @error('name') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="email">Correo electrónico</label>
    <input type="text" id="email" name="email" value="{{ old('email', $usuario->email ?? '') }}" required>
    @error('email') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" {{ isset($usuario) ? '' : 'required' }}>
    <div class="ayuda">
        @isset($usuario) En edición, dejar vacío para no cambiarla. @else Mínimo 8 caracteres. @endisset
    </div>
    @error('password') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="roles">Roles</label>
    <select id="roles" name="roles[]" multiple size="{{ max(3, $roles->count()) }}"
            style="width: 100%; padding: .5rem; border: 1px solid #e5e7eb; border-radius: .5rem;">
        @foreach ($roles as $rol)
            <option value="{{ $rol->id }}" @selected(in_array($rol->id, $rolesActuales))>{{ $rol->name }}</option>
        @endforeach
    </select>
    <div class="ayuda">Ctrl/Cmd + clic para elegir varios.</div>
    @error('roles') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="avatar">Foto de perfil</label>
    <input type="file" id="avatar" name="avatar" accept="image/*">
    @isset($usuario)
        @if ($usuario->avatar_path)
            <div class="ayuda">Ya tiene una foto; sube otra para reemplazarla.</div>
        @endif
    @endisset
    @error('avatar') <div class="error">{{ $message }}</div> @enderror
</div>
