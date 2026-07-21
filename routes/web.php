<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\MarcacionController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Login propio (guard 'web' estándar), convive con el de Filament
// (admin/login) hasta que el panel se retire por completo.
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

// CRUD clásico (MVC) protegido con la misma sesión del panel Filament.
// Conviven con los recursos de /admin: mismo modelo, otra interfaz.
Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Escritorio: mismo resumen que el Dashboard de Filament (equipos,
    // asistencia SIA, gráfico de marcaciones).
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // CRUD completo (base local).
    Route::resource('equipos', EquipoController::class);
    // Habla en vivo con el microservicio Python: mismo criterio que las
    // acciones "Probar conexión"/"Ver marcaciones" del recurso Filament.
    Route::post('equipos/{equipo}/probar-conexion', [EquipoController::class, 'probarConexion'])->name('equipos.probar-conexion');
    Route::get('equipos/{equipo}/marcaciones', [EquipoController::class, 'marcaciones'])->name('equipos.marcaciones');
    Route::get('equipos/{equipo}/marcaciones/exportar', [EquipoController::class, 'exportarMarcaciones'])->name('equipos.marcaciones.exportar');
    Route::resource('usuarios', UserController::class)
        ->parameters(['usuarios' => 'usuario'])
        ->except('show');
    // Roles y su matriz de permisos, reemplazo de la pantalla de Shield.
    Route::resource('roles', RoleController::class)->except('show');

    // Funcionarios del SIA (SQL Server remoto): listado, ficha (con sus
    // marcaciones filtradas), alta y edición. Sin destroy: el borrado sigue
    // siendo del sistema de escritorio.
    Route::resource('funcionarios', PersonaController::class)
        ->parameters(['funcionarios' => 'persona'])
        ->except(['destroy']);

    // Solo lectura: las marcaciones nunca se escriben desde aquí.
    Route::get('marcaciones', [MarcacionController::class, 'index'])->name('marcaciones.index');
});
