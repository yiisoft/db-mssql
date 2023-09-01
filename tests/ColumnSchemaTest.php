<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\BinaryColumnSchema;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;

use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\IntegerColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;
use function fopen;
use function str_repeat;

/**
 * @group mssql
 */
final class ColumnSchemaTest extends TestCase
{
    use TestTrait;

    public function testPhpTypeCast(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $command->insert(
            'type',
            [
                'int_col' => 1,
                'char_col' => str_repeat('x', 100),
                'char_col3' => null,
                'float_col' => 1.234,
                'blob_col' => "\x10\x11\x12",
                'datetime_col' => '2023-07-11 14:50:00.123',
                'bool_col' => false,
            ]
        );
        $command->execute();
        $query = (new Query($db))->from('type')->one();

        $this->assertNotNull($tableSchema);

        $intColPhpType = $tableSchema->getColumn('int_col')?->phpTypecast($query['int_col']);
        $charColPhpType = $tableSchema->getColumn('char_col')?->phpTypecast($query['char_col']);
        $charCol3PhpType = $tableSchema->getColumn('char_col3')?->phpTypecast($query['char_col3']);
        $floatColPhpType = $tableSchema->getColumn('float_col')?->phpTypecast($query['float_col']);
        $blobColPhpType = $tableSchema->getColumn('blob_col')?->phpTypecast($query['blob_col']);
        $datetimeColPhpType = $tableSchema->getColumn('datetime_col')?->phpTypecast($query['datetime_col']);
        $boolColPhpType = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);

        $this->assertSame(1, $intColPhpType);
        $this->assertSame(str_repeat('x', 100), $charColPhpType);
        $this->assertNull($charCol3PhpType);
        $this->assertSame(1.234, $floatColPhpType);
        $this->assertSame("\x10\x11\x12", $blobColPhpType);
        $this->assertSame('2023-07-11 14:50:00.123', $datetimeColPhpType);
        $this->assertEquals(false, $boolColPhpType);

        $db->close();
    }

    public function testColumnSchemaInstance()
    {
        $db = $this->getConnection(true);
        $schema = $db->getSchema();
        $tableSchema = $schema->getTableSchema('type');

        $this->assertInstanceOf(IntegerColumnSchema::class, $tableSchema->getColumn('int_col'));
        $this->assertInstanceOf(StringColumnSchema::class, $tableSchema->getColumn('char_col'));
        $this->assertInstanceOf(DoubleColumnSchema::class, $tableSchema->getColumn('float_col'));
        $this->assertInstanceOf(BinaryColumnSchema::class, $tableSchema->getColumn('blob_col'));
    }

    public function testBinaryColumnSchema()
    {
        $binaryCol = new BinaryColumnSchema('binary_col');

        $this->assertSame('binary_col', $binaryCol->getName());
        $this->assertSame(SchemaInterface::TYPE_BINARY, $binaryCol->getType());
        $this->assertSame(SchemaInterface::PHP_TYPE_RESOURCE, $binaryCol->getPhpType());

        $this->assertNull($binaryCol->dbTypecast(null));
        $this->assertSame('1', $binaryCol->dbTypecast(1));
        $this->assertSame('1', $binaryCol->dbTypecast(true));
        $this->assertSame('0', $binaryCol->dbTypecast(false));
        $this->assertSame($resource = fopen('php://memory', 'rb'), $binaryCol->dbTypecast($resource));
        $this->assertEquals(new Param("\x10\x11\x12", PDO::PARAM_LOB), $binaryCol->dbTypecast("\x10\x11\x12"));
        $this->assertSame($expression = new Expression('expression'), $binaryCol->dbTypecast($expression));

        $this->assertNull($binaryCol->phpTypecast(null));
        $this->assertSame("\x10\x11\x12", $binaryCol->phpTypecast("\x10\x11\x12"));
        $this->assertSame($resource = fopen('php://memory', 'rb'), $binaryCol->phpTypecast($resource));

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
