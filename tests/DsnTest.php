<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mssql\Dsn;

/**
 * @group mssql
 * @group upsert
 */
final class DsnTest extends TestCase
{
    public function testAsString(): void
    {
        $this->assertSame(
            'sqlsrv:Server=127.0.0.1,1433;Database=yiitest',
            (new Dsn('sqlsrv', '127.0.0.1', 'yiitest', '1433'))->asString(),
        );
    }

    public function testGetDriver(): void
    {
        $this->assertSame(
            'sqlsrv',
            (new Dsn('sqlsrv', '127.0.0.1', 'yiitest', '1433'))->getDriver(),
        );
    }

    public function testToString(): void
    {
        $this->assertSame(
            'sqlsrv:Server=127.0.0.1,1433;Database=yiitest',
            (string) (new Dsn('sqlsrv', '127.0.0.1', 'yiitest', '1433')),
        );
    }
}
