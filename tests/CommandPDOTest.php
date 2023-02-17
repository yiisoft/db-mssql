<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonCommandPDOTest;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandPDOTest extends CommonCommandPDOTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandPDOProvider::bindParam
     */
    public function testBindParam(
        string $field,
        string $name,
        mixed $value,
        int $dataType,
        int|null $length,
        mixed $driverOptions,
        array $expected,
    ): void {
        parent::testBindParam($field, $name, $value, $dataType, $length, $driverOptions, $expected);
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\CommandPDOProvider::bindParamsNonWhere
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        parent::testBindParamsNonWhere($sql);
    }
}
