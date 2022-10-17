<?php

declare(strict_types=1);

use Yiisoft\Db\Mssql\Tests\Support\MssqlHelper;

require dirname(__DIR__) . '/vendor/autoload.php';

// add mssql fixture to database
$mssql = new Mssqlhelper();
$mssqlHelper = $mssql->createConnection();
$mssql->prepareDatabase($mssqlHelper);
$mssqlHelper->close();
