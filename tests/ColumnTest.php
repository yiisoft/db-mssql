<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Column\BinaryColumn;
use Yiisoft\Db\Mssql\Connection;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Column\BooleanColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Column\DoubleColumn;
use Yiisoft\Db\Schema\Column\IntegerColumn;
use Yiisoft\Db\Schema\Column\StringColumn;
use Yiisoft\Db\Tests\AbstractColumnTest;

use function str_repeat;

/**
 * @group mssql
 */
final class ColumnTest extends AbstractColumnTest
{
    use TestTrait;

    private function insertTypeValues(Connection $db): void
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

    private function assertResultValues(array $result): void
    {
        $this->assertSame(1, $result['int_col']);
        $this->assertSame(str_repeat('x', 100), $result['char_col']);
        $this->assertNull($result['char_col3']);
        $this->assertSame(1.234, $result['float_col']);
        $this->assertSame("\x10\x11\x12", $result['blob_col']);
        $this->assertSame('2023-07-11 14:50:00.123', $result['datetime_col']);
        $this->assertFalse($result['bool_col']);
        $this->assertSame('[{"a":1,"b":null,"c":[1,3,5]}]', $result['json_col']);
    }

    public function testQueryTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $result = (new Query($db))->typecasting()->from('type')->one();

        $this->assertResultValues($result);

        $db->close();
    }

    public function testCommandPhpTypecasting(): void
    {
        $db = $this->getConnection(true);

        $this->insertTypeValues($db);

        $result = $db->createCommand('SELECT * FROM type')->phpTypecasting()->queryOne();

        $this->assertResultValues($result);

        $db->close();
    }

    public function testSelectPhpTypecasting(): void
    {
        $db = $this->getConnection();

        $result = $db->createCommand(
            "SELECT null AS [null], 1 AS [1], 2.5 AS [2.5], 'string' AS [string]"
        )->phpTypecasting()->queryOne();

        $this->assertSame(
            [
                'null' => null,
                1 => 1,
                '2.5' => 2.5,
                'string' => 'string',
            ],
            $result,
        );

        $db->close();
    }

    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->insertTypeValues($db);

        $query = (new Query($db))->from('type')->one();

        $intColPhpType = $tableSchema->getColumn('int_col')?->phpTypecast($query['int_col']);
        $charColPhpType = $tableSchema->getColumn('char_col')?->phpTypecast($query['char_col']);
        $charCol3PhpType = $tableSchema->getColumn('char_col3')?->phpTypecast($query['char_col3']);
        $floatColPhpType = $tableSchema->getColumn('float_col')?->phpTypecast($query['float_col']);
        $blobColPhpType = $tableSchema->getColumn('blob_col')?->phpTypecast($query['blob_col']);
        $datetimeColPhpType = $tableSchema->getColumn('datetime_col')?->phpTypecast($query['datetime_col']);
        $boolColPhpType = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);
        $jsonColPhpType = $tableSchema->getColumn('json_col')?->phpTypecast($query['json_col']);

        $this->assertSame(1, $intColPhpType);
        $this->assertSame(str_repeat('x', 100), $charColPhpType);
        $this->assertNull($charCol3PhpType);
        $this->assertSame(1.234, $floatColPhpType);
        $this->assertSame("\x10\x11\x12", $blobColPhpType);
        $this->assertSame('2023-07-11 14:50:00.123', $datetimeColPhpType);
        $this->assertFalse($boolColPhpType);
        $this->assertSame([['a' => 1, 'b' => null, 'c' => [1, 3, 5]]], $jsonColPhpType);

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

    /** @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\ColumnProvider::predefinedTypes */
    public function testPredefinedType(string $className, string $type, string $phpType)
    {
        parent::testPredefinedType($className, $type, $phpType);
    }

    /** @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\ColumnProvider::dbTypecastColumns */
    public function testDbTypecastColumns(ColumnInterface $column, array $values)
    {
        parent::testDbTypecastColumns($column, $values);
    }

    public function testBinaryColumn()
    {
        $binaryCol = new BinaryColumn();
        $binaryCol->dbType('varbinary');

        $this->assertEquals(
            new Expression('CONVERT(VARBINARY(MAX), 0x101112)'),
            $binaryCol->dbTypecast("\x10\x11\x12"),
        );
        $this->assertEquals(
            new Expression('CONVERT(VARBINARY(MAX), 0x101112)'),
            $binaryCol->dbTypecast(new Param("\x10\x11\x12", PDO::PARAM_LOB)),
        );
    }
}
