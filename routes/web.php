<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\MarcacionController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Login propio del sitio (guard 'web' estándar).
Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

// CRUD clásico (MVC) protegido con la sesión del sitio.
Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Escritorio: resumen general (equipos, asistencia SIA, gráfico de
    // marcaciones).
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // CRUD completo (base local).
    Route::resource('equipos', EquipoController::class);
    // Habla en vivo con el microservicio Python (probar conexión, exportar y
    // sincronizar marcaciones del equipo).
    Route::post('equipos/{equipo}/probar-conexion', [EquipoController::class, 'probarConexion'])->name('equipos.probar-conexion');
    Route::get('equipos/{equipo}/marcaciones/exportar', [EquipoController::class, 'exportarMarcaciones'])->name('equipos.marcaciones.exportar');
    Route::post('equipos/{equipo}/marcaciones/sincronizar', [EquipoController::class, 'sincronizarMarcaciones'])->name('equipos.marcaciones.sincronizar');
    Route::resource('usuarios', UserController::class)
        ->parameters(['usuarios' => 'usuario'])
        ->except('show');
    // Roles y su matriz de permisos.
    Route::resource('roles', RoleController::class)->except('show');

    // Funcionarios del SIA (SQL Server remoto): listado, ficha (con sus
    // marcaciones filtradas), alta y edición. Sin destroy: el borrado sigue
    // siendo del sistema de escritorio.
    Route::resource('funcionarios', PersonaController::class)
        ->parameters(['funcionarios' => 'persona'])
        ->except(['destroy']);

    // El listado es de solo lectura; la única escritura es importar el CSV
    // que ya exporta "Equipos > Marcaciones > Exportar".
    Route::get('marcaciones', [MarcacionController::class, 'index'])->name('marcaciones.index');
    Route::post('marcaciones/importar', [MarcacionController::class, 'importar'])->name('marcaciones.importar');
});
