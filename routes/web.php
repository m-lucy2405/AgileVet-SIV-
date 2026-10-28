
<?php
use App\Http\Controllers\MascotaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\DesparasitacionController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacunacionController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Solo para usuarios que NO han iniciado sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.post');
});

// Solo para usuarios con sesión iniciada
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/clientes/registro', 'clientes.registro')->name('clientes.registro');
    Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
    
    Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/{cliente}', [ClienteController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{cliente}/editar', [ClienteController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{cliente}', [ClienteController::class, 'update'])->name('clientes.update');
    Route::get('/mascotas/{mascota}/editar', [MascotaController::class, 'edit'])->name('mascotas.edit');
    Route::put('/mascotas/{mascota}', [MascotaController::class, 'update'])->name('mascotas.update');

    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/calendario/eventos', [CitaController::class, 'calendarEvents'])
        ->name('citas.calendario.eventos');
    Route::get('/citas/recordatorios/datos', [CitaController::class, 'dailyReminders'])
        ->name('citas.recordatorios.datos');
    Route::post('/citas', [CitaController::class, 'store'])->name('citas.store');
    Route::put('/citas/{cita}', [CitaController::class, 'update'])->name('citas.update');
    Route::patch('/citas/{cita}/estado', [CitaController::class, 'changeStatus'])
        ->name('citas.estado');
    Route::delete('/citas/{cita}', [CitaController::class, 'destroy'])->name('citas.destroy');
    Route::get('/citas/recordatorios', [CitaController::class, 'remindersPage'])
        ->name('citas.recordatorios');

    Route::middleware('role:Administrador,Recepcionista')->group(function () {
        Route::get('/ventas', [VentaController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/crear', [VentaController::class, 'create'])->name('ventas.create');
        Route::post('/ventas', [VentaController::class, 'store'])->name('ventas.store');
        Route::get('/ventas/{venta}/comprobante', [VentaController::class, 'comprobante'])
            ->whereNumber('venta')
            ->name('ventas.comprobante');
    });

    // Expedientes clínicos (consulta para cualquier usuario con sesión)
    Route::get('/expedientes', [ExpedienteController::class, 'index'])
        ->name('expedientes.index');

    Route::get('/expedientes/{mascota}', [ExpedienteController::class, 'show'])
        ->name('expedientes.show');

    Route::get('/expedientes/{mascota}/consultas', [ExpedienteController::class, 'consultas'])
        ->name('expedientes.consultas');

    // Editar los datos generales del expediente (solo veterinarios; el controlador lo verifica)
    Route::get('/expedientes/{mascota}/editar', [ExpedienteController::class, 'edit'])
        ->name('expedientes.edit');

    Route::put('/expedientes/{mascota}', [ExpedienteController::class, 'update'])
        ->name('expedientes.update');

    // Consultas (solo veterinarios; el controlador lo verifica)
    Route::get('/expedientes/{mascota}/consultas/crear', [ConsultaController::class, 'create'])
        ->name('consultas.create');

    Route::post('/expedientes/{mascota}/consultas', [ConsultaController::class, 'store'])
        ->name('consultas.store');

    // Corregir una consulta (solo quien la registró y el mismo día; el controlador lo verifica)
    Route::get('/expedientes/{mascota}/consultas/{consulta}/editar', [ConsultaController::class, 'edit'])
        ->name('consultas.edit');

    Route::put('/expedientes/{mascota}/consultas/{consulta}', [ConsultaController::class, 'update'])
        ->name('consultas.update');

    // Vacunas y desparasitaciones (solo veterinarios; el controlador lo verifica)
    Route::get('/expedientes/{mascota}/vacunas/crear', [VacunacionController::class, 'create'])
        ->name('vacunaciones.create');

    Route::post('/expedientes/{mascota}/vacunas', [VacunacionController::class, 'store'])
        ->name('vacunaciones.store');

    Route::get('/expedientes/{mascota}/desparasitaciones/crear', [DesparasitacionController::class, 'create'])
        ->name('desparasitaciones.create');

    Route::post('/expedientes/{mascota}/desparasitaciones', [DesparasitacionController::class, 'store'])
        ->name('desparasitaciones.store');

    // Inventario: catálogo visible para todos los usuarios con sesión
    Route::get('/inventario', [ProductoController::class, 'index'])
        ->name('productos.index');

    // Kárdex del producto: disponible para cualquier usuario autenticado
    Route::get('/inventario/productos/{producto}', [ProductoController::class, 'show'])
        ->whereNumber('producto')
        ->name('productos.show');

    // Reportes (el controlador verifica qué rol puede ver cada uno)
    Route::get('/reportes', [ReporteController::class, 'index'])
        ->name('reportes.index');

    Route::get('/reportes/citas', [ReporteController::class, 'citas'])
        ->name('reportes.citas');

    Route::get('/reportes/consultas', [ReporteController::class, 'consultas'])
        ->name('reportes.consultas');

    Route::get('/reportes/diagnosticos', [ReporteController::class, 'diagnosticos'])
        ->name('reportes.diagnosticos');

    Route::get('/reportes/dosis', [ReporteController::class, 'dosis'])
        ->name('reportes.dosis');

    // La administración de cuentas, roles, auditoría e inventario está reservada al Administrador.
    Route::middleware('role:Administrador')->group(function () {
        // Administración de usuarios
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'user'])
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        // Auditoría del sistema (solo lectura)
        Route::get('/auditoria', [AuditoriaController::class, 'index'])
            ->name('auditoria.index');

        Route::get('/auditoria/{auditoria}', [AuditoriaController::class, 'show'])
            ->name('auditoria.show');

        // Inventario: crear, editar, activar y desactivar productos
        Route::get('/inventario/productos/crear', [ProductoController::class, 'create'])
            ->name('productos.create');

        Route::post('/inventario/productos', [ProductoController::class, 'store'])
            ->name('productos.store');

        Route::get('/inventario/productos/{producto}/editar', [ProductoController::class, 'edit'])
            ->name('productos.edit');

        Route::put('/inventario/productos/{producto}', [ProductoController::class, 'update'])
            ->name('productos.update');

        Route::patch('/inventario/productos/{producto}/estado', [ProductoController::class, 'cambiarEstado'])
            ->name('productos.estado');

        // Inventario: entradas, salidas y ajustes
        Route::get('/inventario/productos/{producto}/movimientos/crear', [MovimientoInventarioController::class, 'create'])
            ->name('movimientos.create');

        Route::post('/inventario/productos/{producto}/movimientos', [MovimientoInventarioController::class, 'store'])
            ->name('movimientos.store');
    });
});
