<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0B1120">
    <title>@yield('titulo', 'Bienvenido') | Agro-Veterinaria La Mascota</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-screen items-center justify-center overflow-hidden bg-fondo p-4 text-gray-800 antialiased">

    {{-- Cuadrícula tenue de fondo --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0
        bg-[linear-gradient(rgb(34_211_238/0.06)_1px,transparent_1px),linear-gradient(90deg,rgb(34_211_238/0.06)_1px,transparent_1px)]
        bg-[size:40px_40px]
        [mask-image:radial-gradient(ellipse_at_center,black_30%,transparent_75%)]"></div>

    {{-- Brillo detrás de la tarjeta --}}
    <div aria-hidden="true" class="pointer-events-none absolute left-1/2 top-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-primario-500/10 blur-3xl"></div>

    <div class="relative z-10 flex w-full justify-center">
        @yield('contenido')
    </div>

</body>
</html>