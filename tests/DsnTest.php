<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mssql\Dsn;

/**
 * @group mssql
 */
final class DsnTest extends TestCase
{
    public function testAsString(): void
    {
        $dsn = (new Dsn('sqlsrv', '127.0.0.1', 'yiitest', '1433'))->asString();

        $this->assertSame('sqlsrv:Server=127.0.0.1,1433;Database=yiitest', $dsn);
    }

    public function testGetDriver(): void
    {
        $driver = (new Dsn('sqlsrv', '127.0.0.1', 'yiitest', '1433'))->getDriver();

        $this->assertSame('sqlsrv', $driver);
    }

    public function testToString(): void
    {
        $dsn = (string) (new Dsn('sqlsrv', '127.0.0.1', 'yiitest', '1433'));

        $this->assertSame('sqlsrv:Server=127.0.0.1,1433;Database=yiitest', $dsn);
    }
}
