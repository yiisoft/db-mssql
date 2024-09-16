<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnBuilderTest;

/**
 * @group mssql
 */
class ColumnBuilderTest extends AbstractColumnBuilderTest
{
    use TestTrait;

    public function getColumnBuilderClass(): string
    {
        return ColumnBuilder::class;
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\ColumnBuilderProvider::buildingMethods
     */
    public function testBuildingMethods(
        string $buildingMethod,
        array $args,
        string $expectedInstanceOf,
        string $expectedType,
        array $expectedMethodResults = [],
    ): void {
        parent::testBuildingMethods($buildingMethod, $args, $expectedInstanceOf, $expectedType, $expectedMethodResults);
    }
}
