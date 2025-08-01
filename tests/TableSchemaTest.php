<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mssql\TableSchema;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class TableSchemaTest extends TestCase
{
    use TestTrait;

    public function testConstructorWithTableSchemaCatalogServer(): void
    {
        $tableSchema = new TableSchema('test', 'dbo', 'catalog', 'server');

        $this->assertSame('test', $tableSchema->getName());
        $this->assertSame('server.catalog.dbo.test', $tableSchema->getFullName());
        $this->assertSame('dbo', $tableSchema->getSchemaName());
        $this->assertSame('catalog', $tableSchema->getCatalogName());
        $this->assertSame('server', $tableSchema->getServerName());
    }

    public function testConstructorWithTableSchema(): void
    {
        $tableSchema = new TableSchema('test', 'schema');

        $this->assertSame('test', $tableSchema->getName());
        $this->assertSame('schema.test', $tableSchema->getFullName());
        $this->assertSame('schema', $tableSchema->getSchemaName());
        $this->assertSame('', $tableSchema->getCatalogName());
        $this->assertSame('', $tableSchema->getServerName());
    }

    public function testConstructorWithTableSchemaCatalog(): void
    {
        $tableSchema = new TableSchema('test', 'schema', 'catalog');

        $this->assertSame('test', $tableSchema->getName());
        $this->assertSame('catalog.schema.test', $tableSchema->getFullName());
        $this->assertSame('schema', $tableSchema->getSchemaName());
        $this->assertSame('catalog', $tableSchema->getCatalogName());
        $this->assertSame('', $tableSchema->getServerName());
    }

    public function testGetCatalogName(): void
    {
        $tableSchema = new TableSchema();

        $this->assertSame('', $tableSchema->getCatalogName());

        $tableSchema->catalogName('test');

        $this->assertSame('test', $tableSchema->getCatalogName());
    }

    public function testGetServerName(): void
    {
        $tableSchema = new TableSchema();

        $this->assertEmpty($tableSchema->getServerName());

        $tableSchema->serverName('test');

        $this->assertSame('test', $tableSchema->getServerName());
    }
}
