<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::view('/login', 'auth.login')->name('login');
Route::view('/usuarios', 'usuarios.index')->name('usuarios.index');
Route::view('/usuarios/crear', 'usuarios.create')->name('usuarios.create');
Route::view('/usuarios/{id}/editar', 'usuarios.edit')->name('usuarios.edit');
Route::view('/clientes/registro', 'clientes.registro')->name('clientes.registro');