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
    public function testConstruct(): void
    {
        $dsn = new Dsn('sqlsrv', 'localhost', 'yiitest', '1434', ['Encrypt' => 'no']);

        $this->assertSame('sqlsrv', $dsn->driver);
        $this->assertSame('localhost', $dsn->host);
        $this->assertSame('yiitest', $dsn->databaseName);
        $this->assertSame('1434', $dsn->port);
        $this->assertSame(['Encrypt' => 'no'], $dsn->options);
        $this->assertSame('sqlsrv:Server=localhost,1434;Database=yiitest;Encrypt=no', (string) $dsn);
    }

    public function testConstructDefaults(): void
    {
        $dsn = new Dsn();

        $this->assertSame('sqlsrv', $dsn->driver);
        $this->assertSame('127.0.0.1', $dsn->host);
        $this->assertSame('', $dsn->databaseName);
        $this->assertSame('1433', $dsn->port);
        $this->assertSame([], $dsn->options);
        $this->assertSame('sqlsrv:Server=127.0.0.1,1433', (string) $dsn);
    }

    public function testConstructWithEmptyPort(): void
    {
        $dsn = new Dsn('sqlsrv', 'localhost', port: '');

        $this->assertSame('sqlsrv', $dsn->driver);
        $this->assertSame('localhost', $dsn->host);
        $this->assertSame('', $dsn->databaseName);
        $this->assertSame('', $dsn->port);
        $this->assertSame([], $dsn->options);
        $this->assertSame('sqlsrv:Server=localhost', (string) $dsn);
    }
}
