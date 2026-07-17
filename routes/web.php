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

    // Funcionarios del SIA (SQL Server remoto): listado, ficha, alta y
    // edición. Sin destroy: el borrado sigue siendo del sistema de escritorio.
    // Las marcaciones del funcionario se ven en el panel Filament (/admin).
    Route::resource('funcionarios', PersonaController::class)
        ->parameters(['funcionarios' => 'persona'])
        ->except(['destroy']);

    // Solo lectura: las marcaciones nunca se escriben desde aquí.
    Route::get('marcaciones', [MarcacionController::class, 'index'])->name('marcaciones.index');
});
