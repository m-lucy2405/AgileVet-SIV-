@extends('layouts.guest')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">

            {{-- logo oficial de la veterinaria --}}
            <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl border-2 border-dashed border-primario-300 bg-primario-50 text-xs font-medium text-primario-700">
                Logo
            </div>

            <h1 class="text-center text-xl font-semibold text-gray-800">Agro-Veterinaria La Mascota</h1>
            <p class="mt-1 text-center text-sm text-gray-500">Ingresa tus credenciales para continuar</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="etiqueta">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                        required autofocus autocomplete="username" class="campo">
                    @error('email')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="etiqueta">Contraseña</label>
                    <input id="password" name="password" type="password"
                        required autocomplete="current-password" class="campo">
                    @error('password')
                        <p class="error-campo">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 accent-primario-600">
                    Recordarme
                </label>

                <button type="submit" class="btn-primario w-full py-2.5">Iniciar sesión</button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Agro-Veterinaria La Mascota
        </p>
    </div>
@endsection