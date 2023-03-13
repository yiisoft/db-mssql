<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonColumnSchemaBuilderTest;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ColumnSchemaBuilderTest extends CommonColumnSchemaBuilderTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\ColumnSchemaBuilderProvider::createColumnTypes()
     */
    public function testCreateColumnTypes(string $expected, string $type, ?int $length, array $calls): void
    {
        parent::testCreateColumnTypes($expected, $type, $length, $calls);
    }
}
