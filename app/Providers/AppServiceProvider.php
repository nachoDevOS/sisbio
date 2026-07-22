<?php

namespace App\Providers;

use App\Database\SqlServer2008Connection;
use App\Policies\RolePolicy;
use Illuminate\Database\Connection;
use Illuminate\Pagination\Paginator;
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
        // de autodescubrimiento de policies de Laravel no lo encuentra sola,
        // por eso se registra su policy a mano.
        Gate::policy(Role::class, RolePolicy::class);

        // super_admin puede todo, sin permisos individuales asignados.
        Gate::before(fn ($user): ?bool => $user->hasRole('super_admin') ? true : null);

        // La vista de paginación por defecto de Laravel usa clases Tailwind
        // que este layout no compila; se reemplaza por una vista propia
        // (resources/views/vendor/pagination/custom.blade.php) acorde al
        // estilo del sitio, aplicada a todas las tablas paginadas.
        Paginator::defaultView('vendor.pagination.custom');
    }
}
