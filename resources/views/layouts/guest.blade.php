<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Bienvenido') | Agro-Veterinaria La Mascota</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-fondo p-4 text-gray-800 antialiased">

    @yield('contenido')

</body>
</html>