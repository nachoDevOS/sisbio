<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Corre TODA la migración de datos del SIA (SQL Server) a la base local, con un
 * solo comando:
 *
 *     php artisan db:seed --class=MigrarSiaSeeder
 *
 * PRIMERO limpia la base con `migrate:fresh --seed` (recrea el esquema desde
 * cero y siembra usuario de prueba + roles), y LUEGO corre los comandos de copia
 * en el orden correcto. El orden importa porque `asignacion_turnos` y `licencias`
 * resuelven su FK `turno_id` contra `turnos`, así que los horarios se migran antes.
 *
 * OJO: `migrate:fresh` BORRA todas las tablas (equipos, usuarios, roles y las del
 * SIA ya copiadas). Es una migración limpia completa cada vez que se corre.
 *
 * Requiere `pdo_sqlsrv` instalado, MySQL arriba y las credenciales `DB_*_SIA`
 * en el `.env`. Ver docs/MIGRACION-SIA-MYSQL.md.
 */
class MigrarSiaSeeder extends Seeder
{
    /**
     * Comandos de copia, en orden de dependencia.
     *
     * @var list<string>
     */
    private const COMANDOS = [
        'sia:migrar-profesiones',
        'sia:migrar-personas',
        'sia:migrar-horarios',        // antes de asignacion-turnos (FK turno_id).
        'sia:migrar-marcaciones',
        'sia:migrar-licencias',
        'sia:migrar-asignacion-turnos',
        'sia:migrar-dias-excepcionales',
    ];

    public function run(): void
    {
        // Base limpia desde cero antes de copiar (esquema + usuario/roles base).
        // En testing la BD ya viene fresca por RefreshDatabase; correr
        // migrate:fresh ahí rompería la transacción de las pruebas.
        if (! app()->environment('testing')) {
            $this->command->getOutput()->writeln('<comment>Limpiando la base: migrate:fresh --seed…</comment>');
            $this->command->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        }

        foreach (self::COMANDOS as $comando) {
            $this->command->getOutput()->writeln("<info>→ {$comando}</info>");
            $this->command->call($comando);
        }

        $this->command->getOutput()->writeln('<info>Migración del SIA completa.</info>');
    }
}
