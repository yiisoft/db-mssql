<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnSchemaBuilderTest;

/**
 * @group mssql
 */
final class ColumnSchemaBuilderTest extends AbstractColumnSchemaBuilderTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider::types
     */
    public function testCustomTypes(string $expected, string $type, int|null $length, mixed $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }
}
