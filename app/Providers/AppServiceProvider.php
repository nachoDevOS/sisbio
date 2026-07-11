<?php

namespace App\Providers;

use App\Database\SqlServer2008Connection;
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
        //
    }
}
