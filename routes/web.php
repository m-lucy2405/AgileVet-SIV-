<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Solo para usuarios que NO han iniciado sesión
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Solo para usuarios con sesión iniciada
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::view('/usuarios', 'usuarios.index')->name('usuarios.index');
    Route::view('/usuarios/crear', 'usuarios.create')->name('usuarios.create');
    Route::view('/usuarios/{id}/editar', 'usuarios.edit')->name('usuarios.edit');
    Route::view('/clientes/registro', 'clientes.registro')->name('clientes.registro');
});