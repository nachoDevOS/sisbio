<?php

namespace App\Providers;

use App\Database\SqlServer2008Connection;
use Filament\Actions\CreateAction;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El servidor SIA corre SQL Server 2008 R2, que no soporta OFFSET/FETCH.
        // Toda conexión sqlsrv usa la variante con paginación por ROW_NUMBER().
        Connection::resolverFor('sqlsrv', function ($connection, $database, $prefix, $config) {
            return new SqlServer2008Connection($connection, $database, $prefix, $config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sin botón "crear y crear otro" en los formularios de creación por modal.
        CreateAction::configureUsing(function (CreateAction $action): void {
            $action->createAnother(false);
        });

        // Todas las tablas del panel con filas cebra para mejor lectura.
        Table::configureUsing(function (Table $table): void {
            $table->striped();
        });

        // Selector "por página" al inicio de la barra superior de cada tabla
        // (el selector inferior se oculta en el tema).
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_START,
            fn (): string => view('filament.tables.per-page-top')->render(),
        );
    }
}
