<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
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
    Route::view('/citas', 'citas.index')->name('citas.index');
    Route::view('/citas/recordatorios', 'citas.recordatorios')->name('citas.recordatorios');

    // La administración de cuentas y roles está reservada al Administrador.
    Route::middleware('role:Administrador')->group(function () {
        Route::resource('usuarios', UserController::class)
            ->parameters(['usuarios' => 'user'])
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
});
