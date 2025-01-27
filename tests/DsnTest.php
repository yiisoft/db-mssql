<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mssql\Dsn;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
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

    public function testAsStringWithDatabaseName(): void
    {
        $this->assertSame('sqlsrv:Server=127.0.0.1,1433', (new Dsn('sqlsrv', '127.0.0.1'))->asString());
    }

    public function testAsStringWithDatabaseNameWithEmptyString(): void
    {
        $this->assertSame('sqlsrv:Server=127.0.0.1,1433', (new Dsn('sqlsrv', '127.0.0.1', ''))->asString());
    }

    public function testAsStringWithDatabaseNameWithNull(): void
    {
        $this->assertSame('sqlsrv:Server=127.0.0.1,1433', (new Dsn('sqlsrv', '127.0.0.1', null))->asString());
    }

    public function testAsStringWithEmptyPort(): void
    {
        $this->assertSame(
            'sqlsrv:Server=127.0.0.1;Database=yiitest',
            (new Dsn('sqlsrv', '127.0.0.1', 'yiitest', ''))->asString(),
        );
    }
}
