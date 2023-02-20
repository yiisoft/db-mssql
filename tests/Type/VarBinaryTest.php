<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

use function str_repeat;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/binary-and-varbinary-transact-sql?view=sql-server-ver16
 */
final class VarBinaryTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('varbinary.sql');

        $db = $this->getConnection(true);

        $tableSchema = $db->getSchema()->getTableSchema('varbinary_default');

        $this->assertSame('varbinary(10)', $tableSchema->getColumn('Myvarbinary1')->getDbType());
        $this->assertSame('resource', $tableSchema->getColumn('Myvarbinary1')->getPhpType());
        $this->assertSame('varbinary(100)', $tableSchema->getColumn('Myvarbinary2')->getDbType());
        $this->assertSame('resource', $tableSchema->getColumn('Myvarbinary2')->getPhpType());

        $command = $db->createCommand();
        $command->insert('varbinary_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarbinary1' => 'varbinary',
                'Myvarbinary2' => 'v',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM varbinary_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * When the value is greater than the maximum value, the value is truncated.
     */
    public function testMaxValue(): void
    {
        $this->setFixture('varbinary.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('varbinary', [
            'Myvarbinary1' => new Expression('CONVERT(varbinary(10), \'binary_default_value\')'),
            'Myvarbinary3' => new Expression('CONVERT(binary(100), \'' . str_repeat('v', 101) . '\')'),
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myvarbinary1' => 'binary_def',
                'Myvarbinary2' => null,
                'Myvarbinary3' => 'vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv',
                'Myvarbinary4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM varbinary WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testValue(): void
    {
        $this->setFixture('binary.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('binary', [
            'Mybinary1' => new Expression('CONVERT(binary(10), \'binary\')'),
            'Mybinary2' => new Expression('CONVERT(binary(10), null)'),
            'Mybinary3' => new Expression('CONVERT(binary(1), \'b\')'),
            'Mybinary4' => new Expression('CONVERT(binary(1), null)'),
        ])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybinary1' => '0x62696E61727900000000',
                'Mybinary2' => null,
                'Mybinary3' => 'b',
                'Mybinary4' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT id, CONVERT(VARCHAR(100), Mybinary1, 1) AS Mybinary1, Mybinary2, Mybinary3, Mybinary4 FROM binary WHERE id = 1
                SQL
            )->queryOne()
        );
    }
}
