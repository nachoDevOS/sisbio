<?php

namespace App\Database;

use Illuminate\Database\SqlServerConnection;

/**
 * Conexión sqlsrv que usa el grammar compatible con SQL Server 2008 R2 (SIA).
 */
class SqlServer2008Connection extends SqlServerConnection
{
    /**
     * Get the default query grammar instance.
     *
     * @return SqlServer2008Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new SqlServer2008Grammar($this);
    }
}
