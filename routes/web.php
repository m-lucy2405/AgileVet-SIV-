<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\DesparasitacionController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacunacionController;
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

    // Expedientes clínicos (consulta para cualquier usuario con sesión)
    Route::get('/expedientes', [ExpedienteController::class, 'index'])->name('expedientes.index');
    Route::get('/expedientes/{mascota}', [ExpedienteController::class, 'show'])->name('expedientes.show');

    // Consultas (solo veterinarios; el controlador lo verifica)
    Route::get('/expedientes/{mascota}/consultas/crear', [ConsultaController::class, 'create'])
        ->name('consultas.create');
    Route::post('/expedientes/{mascota}/consultas', [ConsultaController::class, 'store'])
        ->name('consultas.store');

    // Vacunas y desparasitaciones (solo veterinarios; el controlador lo verifica)
    Route::get('/expedientes/{mascota}/vacunas/crear', [VacunacionController::class, 'create'])
        ->name('vacunaciones.create');
    Route::post('/expedientes/{mascota}/vacunas', [VacunacionController::class, 'store'])
        ->name('vacunaciones.store');
    Route::get('/expedientes/{mascota}/desparasitaciones/crear', [DesparasitacionController::class, 'create'])
        ->name('desparasitaciones.create');
    Route::post('/expedientes/{mascota}/desparasitaciones', [DesparasitacionController::class, 'store'])
        ->name('desparasitaciones.store');

    // La administración de cuentas y roles está reservada al Administrador.
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'user'])
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
});