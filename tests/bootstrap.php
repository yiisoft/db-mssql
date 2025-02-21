<?php

declare(strict_types=1);

if (getenv('ENVIRONMENT', true) === 'local') {
    putenv('YII_MSSQL_DATABASE=tempdb');
    putenv('YII_MSSQL_HOST=mssql');
}
