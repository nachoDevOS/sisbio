<?php

use App\Database\SqlServer2008Connection;

function conexionSia2008(): SqlServer2008Connection
{
    return new SqlServer2008Connection(fn () => null, 'SIA_DEV', '', ['driver' => 'sqlsrv']);
}

test('la paginación con offset se compila con row_number en lugar de offset/fetch', function () {
    $sql = conexionSia2008()->table('Asistencia')
        ->orderByDesc('Fecha')
        ->offset(25)
        ->limit(25)
        ->toSql();

    expect($sql)
        ->toContain('row_number() over (order by [Fecha] desc) as row_num')
        ->toContain('where row_num between 26 and 50')
        ->not->toContain('fetch next')
        ->not->toContain('offset 25 rows');
});

test('las consultas con limit y sin offset siguen usando top', function () {
    $sql = conexionSia2008()->table('Asistencia')
        ->orderByDesc('Fecha')
        ->limit(25)
        ->toSql();

    expect($sql)
        ->toContain('select top 25')
        ->not->toContain('row_num');
});

test('un offset sin orden explícito usa un orden neutro', function () {
    $sql = conexionSia2008()->table('Asistencia')
        ->offset(10)
        ->limit(5)
        ->toSql();

    expect($sql)
        ->toContain('row_number() over (order by (select 0)) as row_num')
        ->toContain('where row_num between 11 and 15');
});

test('un offset sin limit acota solo el inicio', function () {
    $sql = conexionSia2008()->table('Asistencia')
        ->orderBy('Fecha')
        ->offset(100)
        ->toSql();

    expect($sql)->toContain('where row_num >= 101');
});
