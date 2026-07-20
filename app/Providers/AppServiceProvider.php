<?php

namespace App\Providers;

use App\Database\SqlServer2008Connection;
use App\Policies\RolePolicy;
use Filament\Actions\CreateAction;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        // El modelo Role de Spatie vive fuera de app/Models: la convención
        // de autodescubrimiento de policies de Laravel no lo encuentra sola.
        // Hasta ahora este registro lo hacía Filament Shield; se declara acá
        // para no depender del panel.
        Gate::policy(Role::class, RolePolicy::class);

        // super_admin puede todo, sin permisos individuales asignados. Antes
        // lo resolvía Shield (config('filament-shield.super_admin')).
        Gate::before(fn ($user): ?bool => $user->hasRole('super_admin') ? true : null);

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
