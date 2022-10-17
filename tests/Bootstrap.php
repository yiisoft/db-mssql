<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Yiisoft\Db\Mssql\Tests\Support\MssqlHelper;

// add mssql fixture to database
$mssql = new Mssqlhelper();
$mssqlHelper = $mssql->createConnection();
$mssql->prepareDatabase($mssqlHelper);
$mssqlHelper->close();
