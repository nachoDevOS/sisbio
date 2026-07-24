<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiaExcepcionalController;
use App\Http\Controllers\DiaTurnoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\MarcacionController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ReporteMarcacionController;
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

    // Funcionarios del SIA (SQL Server remoto): solo lectura. Listado y ficha
    // (con sus marcaciones filtradas). El alta/edición/borrado siguen siendo
    // del sistema de escritorio.
    Route::resource('funcionarios', PersonaController::class)
        ->parameters(['funcionarios' => 'persona'])
        ->only(['index', 'show']);

    // Horarios (turnos) del SIA: «Administrador de horarios» del escritorio.
    Route::resource('horarios', DiaTurnoController::class)
        ->parameters(['horarios' => 'horario']);

    // Parámetros → Días excepcionales (feriados/tolerancias que no controlan
    // asistencia), base local MySQL. CRUD sin ficha (show).
    Route::resource('dias-excepcionales', DiaExcepcionalController::class)
        ->parameters(['dias-excepcionales' => 'diaExcepcional'])
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    // Reporte imprimible de marcaciones «sin procesar» (todo lo marcado por el
    // funcionario en un rango), con el formato del sistema de escritorio viejo.
    Route::get('funcionarios/{persona}/reporte-marcaciones', [PersonaController::class, 'reporteMarcaciones'])->name('funcionarios.reporte');

    // El listado es de solo lectura; la única escritura es importar el CSV
    // que ya exporta "Equipos > Marcaciones > Exportar".
    Route::get('marcaciones', [MarcacionController::class, 'index'])->name('marcaciones.index');
    Route::post('marcaciones/importar', [MarcacionController::class, 'importar'])->name('marcaciones.importar');

    // Reportes: selección de funcionario + generación (pantalla, imprimible o
    // CSV). «Sin procesar» = todas las marcaciones crudas del rango.
    Route::get('reportes/marcaciones/sin-procesar', [ReporteMarcacionController::class, 'sinProcesar'])->name('reportes.marcaciones.sin-procesar');
    Route::get('reportes/marcaciones/sin-procesar/generar', [ReporteMarcacionController::class, 'sinProcesarList'])->name('reportes.marcaciones.sin-procesar.generar');
    // Búsqueda JSON de funcionarios para el combo (select2) del reporte.
    Route::get('reportes/marcaciones/funcionarios', [ReporteMarcacionController::class, 'buscarFuncionarios'])->name('reportes.marcaciones.funcionarios');
});
