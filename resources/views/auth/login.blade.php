@extends('layouts.guest')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-primario-500/20 bg-white p-8 shadow-sm">

            {{-- Logo: reemplazar "LM" por la imagen oficial cuando esté disponible --}}
            <div class="mx-auto mb-6 w-20 rounded-2xl bg-gradient-to-br from-primario-500 to-acento-500 p-[1.5px] shadow-[0_0_30px_rgb(34_211_238/0.25)]">
                <div class="flex h-20 items-center justify-center rounded-2xl bg-[#0d1528] text-2xl font-bold tracking-wider text-primario-300">
                    LM
                </div>
            </div>

            <h1 class="bg-gradient-to-r from-gray-900 to-primario-200 bg-clip-text text-center text-xl font-semibold text-transparent">
                Agro-Veterinaria La Mascota
            </h1>
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
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 accent-primario-500">
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