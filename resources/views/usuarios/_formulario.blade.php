@php
    $esEdicion = $usuario !== null;
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="nombre" class="etiqueta">Nombre completo</label>
        <input id="nombre" name="nombre" type="text" required class="campo"
            value="{{ old('nombre', $usuario?->nombre ?? '') }}" maxlength="100">
        @error('nombre')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="email" class="etiqueta">Correo electrónico</label>
        <input id="email" name="email" type="email" required class="campo"
            value="{{ old('email', $usuario?->email ?? '') }}" maxlength="150">
        @error('email')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="etiqueta">Contraseña</label>
        <input id="password" name="password" type="password" autocomplete="new-password"
            class="campo" @required(! $esEdicion)>
        @if ($esEdicion)
            <p class="mt-1 text-xs text-gray-500">Déjala en blanco para mantener la actual.</p>
        @endif
        @error('password')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="etiqueta">Confirmar contraseña</label>
        <input id="password_confirmation" name="password_confirmation" type="password"
            autocomplete="new-password" class="campo" @required(! $esEdicion)>
    </div>

    <div>
        <label for="rol_id" class="etiqueta">Rol</label>
        <select id="rol_id" name="rol_id" required class="campo">
            <option value="">Selecciona un rol</option>
            @foreach ($roles as $rol)
                <option value="{{ $rol->id }}" @selected(old('rol_id', $usuario?->rol_id ?? '') == $rol->id)>
                    {{ $rol->nombre }}
                </option>
            @endforeach
        </select>
        @error('rol_id')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="estado" class="etiqueta">Estado</label>
        <select id="estado" name="estado" class="campo">
            <option value="1" @selected(old('estado', $usuario?->estado ?? true) == 1)>Activo</option>
            <option value="0" @selected(old('estado', $usuario?->estado ?? true) == 0)>Inactivo</option>
        </select>
        @error('estado')
            <p class="error-campo">{{ $message }}</p>
        @enderror
    </div>
</div>