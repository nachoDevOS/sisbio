<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Corre TODA la migración de datos del SIA (SQL Server) a la base local, en el
 * orden correcto, con un solo comando:
 *
 *     php artisan db:seed --class=MigrarSiaSeeder
 *
 * Cada paso es idempotente (upsert): reejecutar el seeder no duplica. El orden
 * importa porque `asignacion_turnos` resuelve su FK `turno_id` contra `turnos`,
 * así que los horarios se migran antes.
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
        foreach (self::COMANDOS as $comando) {
            $this->command->getOutput()->writeln("<info>→ {$comando}</info>");
            $this->command->call($comando);
        }

        $this->command->getOutput()->writeln('<info>Migración del SIA completa.</info>');
    }
}
