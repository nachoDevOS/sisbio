<?php

namespace App\Database;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;

/**
 * Grammar de consultas compatible con SQL Server 2008 R2.
 *
 * La paginación moderna de Laravel compila `OFFSET n ROWS FETCH NEXT m ROWS ONLY`,
 * sintaxis que existe recién desde SQL Server 2012. El servidor SIA corre
 * 2008 R2 (10.50), así que toda consulta con offset se reescribe con
 * ROW_NUMBER() OVER (...), igual que hacía Laravel <= 5.7.
 *
 * Limitaciones conocidas (no afectan al panel): no soporta offset combinado
 * con agregados ni con uniones.
 */
class SqlServer2008Grammar extends SqlServerGrammar
{
    /**
     * Compile a select query into SQL.
     *
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        if (! $query->offset) {
            return parent::compileSelect($query);
        }

        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        $components = $this->compileComponents($query);

        // ROW_NUMBER() exige un ORDER BY; sin uno explícito se usa un orden neutro.
        $orders = ($components['orders'] ?? '') !== '' ? $components['orders'] : 'order by (select 0)';

        // El orden y la paginación pasan a la subconsulta con ROW_NUMBER();
        // se descartan los componentes offset/fetch que genera el padre.
        unset($components['orders'], $components['limit'], $components['offset']);

        $components['columns'] .= ', row_number() over ('.$orders.') as row_num';

        return $this->compileRowConstraint($this->concatenate($components), $query);
    }

    /**
     * Envuelve la consulta en una expresión de tabla acotada por row_num.
     */
    protected function compileRowConstraint(string $sql, Builder $query): string
    {
        $start = (int) $query->offset + 1;

        $constraint = $query->limit
            ? 'between '.$start.' and '.($start + (int) $query->limit - 1)
            : '>= '.$start;

        return "select * from ({$sql}) as temp_table where row_num {$constraint} order by row_num";
    }
}
