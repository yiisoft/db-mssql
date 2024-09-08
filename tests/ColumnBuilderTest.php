<?php

declare(strict_types=1);

use Yiisoft\Db\Mssql\Column\ColumnFactory;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnBuilderTest;

/**
 * @group mssql
 */
class ColumnBuilderTest extends AbstractColumnBuilderTest
{
    use TestTrait;

    public function testColumnFactory(): void
    {
        $db = $this->getConnection();
        $columnBuilderClass = $db->getColumnBuilderClass();

        $this->assertInstanceOf(ColumnFactory::class, $columnBuilderClass::columnFactory());
    }
}
