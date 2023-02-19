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
