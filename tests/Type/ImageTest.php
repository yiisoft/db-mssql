<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *
 * @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/ntext-text-and-image-transact-sql?view=sql-server-ver16
 */
final class ImageTest extends TestCase
{
    use TestTrait;

    public function testDefaultValue(): void
    {
        $this->setFixture('image.sql');

        $db = $this->getConnection(true);

        $tableSchema = $db->getSchema()->getTableSchema('image_default');

        $this->assertSame('image', $tableSchema->getColumn('Myimage')->getDbType());
        $this->assertSame('resource', $tableSchema->getColumn('Myimage')->getPhpType());

        $command = $db->createCommand();
        $command->insert('image_default', [])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myimage' => 'image',

            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM image_default WHERE id = 1
                SQL
            )->queryOne()
        );
    }

    public function testValue(): void
    {
        $this->setFixture('image.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();

        $command->insert(
            'image',
            ['Myimage1' => new Expression('CONVERT(image, 0x30313233343536373839)'), 'Myimage2' => null]
        )->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myimage1' => '0123456789',
                'Myimage2' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM image WHERE id = 1
                SQL
            )->queryOne()
        );
    }
}
