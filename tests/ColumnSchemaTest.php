<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;

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
                'smalldatetime_col' => '2023-08-05 12:05:00',
                'datetime_col' => '2023-07-11 14:50:12.123',
                'datetime2_col' => new DateTimeImmutable('2023-07-11 14:50:23.12 +02:00'),
                'datetimeoffset_col' => new DateTimeImmutable('2023-07-11 14:50:23.1234567 -2:30'),
                'date_col' => new DateTimeImmutable('2023-07-11'),
                'time_col' => new DateTimeImmutable('14:50:23.123456'),
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
        $smalldatetimeColPhpType = $tableSchema->getColumn('smalldatetime_col')?->phpTypecast($query['smalldatetime_col']);
        $datetimeColPhpType = $tableSchema->getColumn('datetime_col')?->phpTypecast($query['datetime_col']);
        $datetime2ColPhpType = $tableSchema->getColumn('datetime2_col')?->phpTypecast($query['datetime2_col']);
        $datetimeoffsetColPhpType = $tableSchema->getColumn('datetimeoffset_col')?->phpTypecast($query['datetimeoffset_col']);
        $dateColPhpType = $tableSchema->getColumn('date_col')?->phpTypecast($query['date_col']);
        $timeColPhpType = $tableSchema->getColumn('time_col')?->phpTypecast($query['time_col']);
        $datetime2DefaultPhpType = $tableSchema->getColumn('datetime2_default')?->phpTypecast($query['datetime2_default']);
        $boolColPhpType = $tableSchema->getColumn('bool_col')?->phpTypecast($query['bool_col']);

        $this->assertSame(1, $intColPhpType);
        $this->assertSame(str_repeat('x', 100), $charColPhpType);
        $this->assertNull($charCol3PhpType);
        $this->assertSame(1.234, $floatColPhpType);
        $this->assertSame("\x10\x11\x12", $blobColPhpType);
        $this->assertEquals(new DateTimeImmutable('2023-08-05 12:05:00'), $smalldatetimeColPhpType);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:12.123'), $datetimeColPhpType);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23.12 +02:00'), $datetime2ColPhpType);
        $this->assertEquals(new DateTimeImmutable('2023-07-11 14:50:23.1234567 -2:30'), $datetimeoffsetColPhpType);
        $this->assertEquals(new DateTimeImmutable('2023-07-11'), $dateColPhpType);
        $this->assertEquals(new DateTimeImmutable('14:50:23.123456'), $timeColPhpType);
        $this->assertInstanceOf(DateTimeImmutable::class, $datetime2DefaultPhpType);
        $this->assertEquals(false, $boolColPhpType);

        $db->close();
    }
}
