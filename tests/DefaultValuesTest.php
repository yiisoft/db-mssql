<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/data-types-transact-sql?view=sql-server-ver16
 */
final class DefaultValuesTest extends TestCase
{
    use TestTrait;

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/int-bigint-smallint-and-tinyint-transact-sql?view=sql-server-ver16
     */
    public function testBigInt(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('bigint_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybigint' => '9223372036854775807',
                'Myint' => '2147483647',
                'Mysmallint' => '32767',
                'Mytinyint' => '255',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bigint_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/binary-and-varbinary-transact-sql?view=sql-server-ver16
     */
    public function testBinaryVarbinary(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('binary_varbinary_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mybinary1' => '0x62696E61727900000000',
                'Mybinary2' => 'b',
                'Myvarbinary1' => 'varbinary',
                'Myvarbinary2' => 'v',
            ],
            $command->setSql(
                <<<SQL
                SELECT id, CONVERT(VARCHAR(100), Mybinary1, 1) AS Mybinary1, Mybinary2, Myvarbinary1, Myvarbinary2 FROM binary_varbinary_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/bit-transact-sql?view=sql-server-ver16
     */
    public function testBit(): void
    {
        $db = $this->getConnection(true);

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

        $command->insert('bit_default', ['Mybit1' => true, 'Mybit2' => false, 'Mybit3' => '1'])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Mybit1' => '1',
                'Mybit2' => '0',
                'Mybit3' => '1',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM bit_default WHERE id = 2
                SQL
            )->queryOne()
        );
    }

    /**
     * https://learn.microsoft.com/en-us/sql/t-sql/data-types/char-and-varchar-transact-sql?view=sql-server-ver16
     */
    public function testCharVarchar(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('char_varchar_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mychar1' => 'char      ',
                'Mychar2' => 'c',
                'Myvarchar1' => 'varchar',
                'Myvarchar2' => 'v',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM char_varchar_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/ntext-text-and-image-transact-sql?view=sql-server-ver16
     */
    public function testNtextTextImage(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('ntext_text_image_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mytext' => 'text',
                'Myntext' => 'ntext',
                'Myimage' => 'image',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM ntext_text_image_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/date-transact-sql?view=sql-server-ver16
     */
    public function testDate(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('date_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydate1' => '2007-05-08',
                'Mydate2' => date('Y-m-d'),
                'Mydatetime' => '2007-05-08 12:35:29.123',
                'Mydatetime2' => '2007-05-08 12:35:29.1234567',
                'Mydatetimeoffset' => '2007-05-08 12:35:29.1234567 +12:15',
                'Mytime' => '12:35:29.1234567',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM date_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/float-and-real-transact-sql?view=sql-server-ver16
     */
    public function testFloatReal(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('float_real_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myfloat' => '123.45',
                'Myreal' => '38.502998',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM float_real_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/money-and-smallmoney-transact-sql?view=sql-server-ver16
     */
    public function testMoneySmallMoney(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('money_small_money_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mymoney' => '922337203685477.5807',
                'Mysmallmoney' => '123.4500',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM money_small_money_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/decimal-and-numeric-transact-sql?view=sql-server-ver16
     */
    public function testNumeric(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('numeric_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Mydecimal' => '123.00',
                'Mynumeric' => '12345.12000',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM numeric_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    /**
     * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/uniqueidentifier-transact-sql?view=sql-server-ver16
     */
    public function testUniqueidentifier(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert('uniqueidentifier_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myuniqueidentifier' => '12345678-1234-1234-1234-123456789012',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM uniqueidentifier_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }
}
