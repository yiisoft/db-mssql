<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;

/**
 * Implements a database command that can be executed against a PDO (PHP Data Object) database connection for MSSQL
 * Server.
 */
final class Command extends AbstractPdoCommand
{
    public function showDatabases(): array
    {
        $sql = <<<SQL
        SELECT [name] FROM [sys].[databases] WHERE [name] NOT IN ('master', 'tempdb', 'model', 'msdb')
        SQL;

        return $this->setSql($sql)->queryColumn();
    }
}
