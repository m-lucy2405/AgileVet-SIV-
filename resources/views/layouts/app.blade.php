<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Inicio') | Agro-Veterinaria La Mascota</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-fondo text-gray-800 antialiased">

    @include('partials.sidebar')

    
    <div id="overlay" class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden"></div>

    <div class="flex min-h-screen flex-col lg:pl-64">
        @include('partials.navbar')

        <main class="flex-1 p-4 sm:p-6">
            @yield('contenido')
        </main>

        <footer class="border-t border-gray-200 px-6 py-4 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Agro-Veterinaria La Mascota
        </footer>
    </div>

    @stack('scripts')
</body>
</html>