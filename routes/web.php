<?php

use App\Http\Controllers\EquipoController;
use App\Http\Controllers\MarcacionController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// La raíz manda al panel: Filament redirige al login si no hay sesión.
Route::redirect('/', '/admin');

// CRUD clásico (MVC) protegido con la misma sesión del panel Filament.
// Conviven con los recursos de /admin: mismo modelo, otra interfaz.
Route::middleware('auth')->group(function (): void {
    // CRUD completo (base local).
    Route::resource('equipos', EquipoController::class);
    Route::resource('usuarios', UserController::class)
        ->parameters(['usuarios' => 'usuario'])
        ->except('show');

    // Solo lectura: el SIA (SQL Server remoto) nunca se escribe.
    Route::get('funcionarios', [PersonaController::class, 'index'])->name('funcionarios.index');
    Route::get('marcaciones', [MarcacionController::class, 'index'])->name('marcaciones.index');
});
