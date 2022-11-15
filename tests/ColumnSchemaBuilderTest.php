<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonColumnSchemaBuilderTest;

/**
 * @group mssql
 */
final class ColumnSchemaBuilderTest extends CommonColumnSchemaBuilderTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\ColumnSchemaBuilderProvider::types();
     */
    public function testCustomTypes(string $expected, string $type, int|null $length, array $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }
}
