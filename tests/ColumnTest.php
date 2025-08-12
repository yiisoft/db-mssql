<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Mssql\Column\BinaryColumn;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\Tests\Provider\ColumnProvider;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Schema\Data\StringableStream;
use Yiisoft\Db\Tests\Common\CommonColumnTest;

use function iterator_to_array;
use function str_repeat;

/**
 * @group mssql
 */
final class ColumnTest extends CommonColumnTest
{
    use TestTrait;

    protected const COLUMN_BUILDER = ColumnBuilder::class;

    protected function insertTypeValues(ConnectionInterface $db): void
    {
        $db->createCommand()->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'datetime_col' => '2023-07-11 14:50:00.123',
                'bool_col' => false,
                'json_col' => [['a' => 1, 'b' => null, 'c' => [1, 3, 5]]],
            ]
        )->execute();
    }

    protected function assertTypecastedValues(array $result, bool $allTypecasted = false): void
    {
        $this->assertSame(1, $result['int_col']);
        $this->assertSame(str_repeat('x', 100), $result['char_col']);
        $this->assertNull($result['char_col3']);
        $this->assertSame(1.234, $result['float_col']);
        $this->assertSame("\x10\x11\x12", $result['blob_col']);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:00.123', new DateTimeZone('UTC')), $result['datetime_col']);
        $this->assertFalse($result['bool_col']);

        if ($allTypecasted) {
            $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $result['json_col']);
        } else {
            $this->assertSame('[{"a":1,"b":null,"c":[1,3,5]}]', $result['json_col']);
        }
    }

    public function testSelectWithPhpTypecasting(): void
    {
        $db = $this->getConnection();

        $sql = "SELECT null AS [null], 1 AS [1], 2.5 AS [2.5], 'string' AS [string]";

        $expected = [
            'null' => null,
            1 => 1,
            '2.5' => 2.5,
            'string' => 'string',
        ];

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->queryOne();

        $this->assertSame($expected, $result);

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->queryAll();

        $this->assertSame([$expected], $result);

        $result = $db->createCommand($sql)
            ->withPhpTypecasting()
            ->query();

        $this->assertSame([$expected], iterator_to_array($result));

        $result = $db->createCommand('SELECT 2.5')
            ->withPhpTypecasting()
            ->queryScalar();

        $this->assertSame(2.5, $result);

        $result = $db->createCommand('SELECT 2.5 UNION SELECT 3.3')
            ->withPhpTypecasting()
            ->queryColumn();

        $this->assertSame([2.5, 3.3], $result);

        $db->close();
    }

    public function testColumnInstance()
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(IntegerColumn::class, $tableSchema->getColumn('int_col'));
        $this->assertInstanceOf(StringColumn::class, $tableSchema->getColumn('char_col'));
        $this->assertInstanceOf(DoubleColumn::class, $tableSchema->getColumn('float_col'));
        $this->assertInstanceOf(BinaryColumn::class, $tableSchema->getColumn('blob_col'));
        $this->assertInstanceOf(BooleanColumn::class, $tableSchema->getColumn('bool_col'));
    }

    #[DataProviderExternal(ColumnProvider::class, 'predefinedTypes')]
    public function testPredefinedType(string $className, string $type, string $phpType)
    {
        parent::testPredefinedType($className, $type);
    }

    #[DataProviderExternal(ColumnProvider::class, 'dbTypecastColumns')]
    public function testDbTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testDbTypecastColumns($column, $values);
    }

    #[DataProviderExternal(ColumnProvider::class, 'phpTypecastColumns')]
    public function testPhpTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testPhpTypecastColumns($column, $values);
    }

    public function testBinaryColumn()
    {
        $binaryCol = new BinaryColumn();
        $binaryCol->dbType('varbinary');

        $expected = new Expression('CONVERT(VARBINARY(MAX), 0x101112)');

        $this->assertEquals(
            $expected,
            $binaryCol->dbTypecast("\x10\x11\x12"),
        );
        $this->assertEquals(
            $expected,
            $binaryCol->dbTypecast(new Param("\x10\x11\x12", PDO::PARAM_LOB)),
        );
        $this->assertEquals(
            $expected,
            $binaryCol->dbTypecast(new StringableStream("\x10\x11\x12")),
        );
    }
}
