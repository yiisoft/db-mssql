<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Function;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class StringTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(): void
    {
        $this->setFixture('Function/string.sql');

        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('string', [])->execute();
        $tableSchema = $db->getTableSchema('string');

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/ascii-transact-sql?view=sql-server-ver16 */
        $this->assertSame("ascii('a')", $tableSchema?->getColumn('Myascii')->getDefaultValue());

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/char-transact-sql?view=sql-server-ver16 */
        $this->assertSame('char((97))', $tableSchema?->getColumn('Mychar')->getDefaultValue());

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/charindex-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "charindex('B','aBc')",
            $tableSchema?->getColumn('Mycharindex')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/concat-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "concat('a','b','c')",
            $tableSchema?->getColumn('Myconcat')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/concat-ws-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "concat_ws('a','b','C')",
            $tableSchema?->getColumn('Myconcatws')->getDefaultValue(),
        );

        /* edge case */
        $this->assertSame(
            "substring(stuff(concat('a','b','c'),(3),(1),concat_ws('f','g','h')),(5),(1))",
            $tableSchema?->getColumn('Mycomplex')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/data-length-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "datalength('abc')",
            $tableSchema?->getColumn('Mydatalength')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/left-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "left('abc',(1))",
            $tableSchema?->getColumn('Myleft')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/len-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "len('abc')",
            $tableSchema?->getColumn('Mylen')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/lower-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "lower('ABC')",
            $tableSchema?->getColumn('Mylower')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/ltrim-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "ltrim(' abc')",
            $tableSchema?->getColumn('Myltrim')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/nchar-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            'nchar((50))',
            $tableSchema?->getColumn('Mynchar')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/patindex-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "patindex('a','abc')",
            $tableSchema?->getColumn('Mypatindex')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/replace-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "replace('abc','a','d')",
            $tableSchema?->getColumn('Myreplace')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/right-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "right('abc',(1))",
            $tableSchema?->getColumn('Myright')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/rtrim-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "rtrim('abc ')",
            $tableSchema?->getColumn('Myrtrim')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/str-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            'str((1.234),(5),(2))',
            $tableSchema?->getColumn('Mystr')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/stuff-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "stuff('abc',(1),(1),'d')",
            $tableSchema?->getColumn('Mystuff')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/substring-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "substring('abc',(1),(1))",
            $tableSchema?->getColumn('Mysubstring')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/upper-transact-sql?view=sql-server-ver16 */
        $this->assertSame(
            "upper('abc')",
            $tableSchema?->getColumn('Myupper')->getDefaultValue(),
        );

        $this->assertSame(
            [
                'id' => '1',
                'Myascii' => '97',
                'Mychar' => 'a',
                'Mycharindex' => '2',
                'Myconcat' => 'abc',
                'Myconcatws' => 'baC',
                'Mycomplex' => 'h',
                'Mydatalength' => '3',
                'Myleft' => 'a',
                'Mylen' => '3',
                'Mylower' => 'abc',
                'Myltrim' => 'abc',
                'Mynchar' => '2',
                'Mypatindex' => '0',
                'Myreplace' => 'dbc',
                'Myright' => 'c',
                'Myrtrim' => 'abc',
                'Mystr' => ' 1.23',
                'Mystuff' => 'dbc',
                'Mysubstring' => 'a',
                'Myupper' => 'ABC',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [string] WHERE [id] = 1
                SQL
            )->queryOne(),
        );
    }
}
