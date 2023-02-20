<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/bit-transact-sql?view=sql-server-ver16
 */
final class BitTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('Type/bit.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getSchema()->getTableSchema('bit_default');

        $this->assertSame('bit', $tableSchema->getColumn('Mybit1')->getDbType());
        $this->assertSame('integer', $tableSchema->getColumn('Mybit1')->getPhpType());
        $this->assertSame('bit', $tableSchema->getColumn('Mybit2')->getDbType());
        $this->assertSame('integer', $tableSchema->getColumn('Mybit2')->getPhpType());
        $this->assertSame('bit', $tableSchema->getColumn('Mybit3')->getDbType());
        $this->assertSame('integer', $tableSchema->getColumn('Mybit3')->getPhpType());

        $command = $db->createCommand();
        $command->insert('bit_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '0',
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testBoolean(): void
    {
        $this->setFixture('Type/bit.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => true, 'Mybit2' => false, 'Mybit3' => true])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '1',
                'Mybit2' => '0',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * Max value is `1`.
     */
    public function testMaxValue(): void
    {
        $this->setFixture('Type/bit.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => 1, 'Mybit2' => 1, 'Mybit3' => 1])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '1',
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('bit', ['Mybit1' => 0.5, 'Mybit2' => 3, 'Mybit3' => 4])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybit1' => '0',
                'Mybit2' => '1',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    /**
     * Min value is `0`.
     *
     * @https://learn.microsoft.com/en-us/sql/t-sql/data-types/bit-transact-sql?view=sql-server-ver16#remarks
     */
    public function testMinValue(): void
    {
        $this->setFixture('Type/bit.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('bit', ['Mybit1' => 0, 'Mybit2' => 0, 'Mybit3' => 0])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybit1' => '0',
                'Mybit2' => '0',
                'Mybit3' => '0',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 1
                SQL
            )->queryOne()
        );

        $command->insert('bit', ['Mybit1' => null, 'Mybit2' => 0.8, 'Mybit3' => -3])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybit1' => null,
                'Mybit2' => '0',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit WHERE id = 2
                SQL
            )->queryOne()
        );
    }
}
