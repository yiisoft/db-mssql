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
}
