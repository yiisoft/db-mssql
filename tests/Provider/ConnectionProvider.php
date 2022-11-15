<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

final class ConnectionProvider
{
    public function execute(): array
    {
        return [
            [
                "SQLSTATE[42000]: [Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Could not find stored procedure 'bad'",
            ],
        ];
    }
}
