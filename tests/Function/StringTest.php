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
    public function testCreateTableDefaultValue(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $command = $db->createCommand();

        if ($schema->getTableSchema('string') !== null) {
            $command->dropTable('string')->execute();
        }

        $command->createTable(
            'string',
            [
                'id' => $schema->createColumnSchemaBuilder('int')->notNull(),
                'Myascii' => 'VARCHAR(10) NOT NULL DEFAULT ASCII(\'a\')',
                'Mychar' => 'VARCHAR(10) NOT NULL DEFAULT CHAR(97)',
                'Mycharindex' => 'VARCHAR(10) NOT NULL DEFAULT CHARINDEX(\'B\',\'aBc\')',
                'Myconcat' => 'VARCHAR(10) NOT NULL DEFAULT CONCAT(\'a\',\'b\',\'c\')',
                'Myconcatws' => 'VARCHAR(10) NOT NULL DEFAULT CONCAT_WS(\'a\',\'b\',\'C\')',
                'Mycomplex' => 'VARCHAR(10) NOT NULL DEFAULT SUBSTRING(STUFF(CONCAT(\'a\',\'b\',\'c\'),3,1,CONCAT_WS(\'f\',\'g\',\'h\')),5,1)',
                'Mydatalength' => 'VARCHAR(10) NOT NULL DEFAULT DATALENGTH(\'ABC\')',
                'Myleft' => 'VARCHAR(10) NOT NULL DEFAULT LEFT(\'ABC\',1)',
                'Mylen' => 'VARCHAR(10) NOT NULL DEFAULT LEN(\'ABC\')',
                'Mylower' => 'VARCHAR(10) NOT NULL DEFAULT LOWER(\'ABC\')',
                'Myltrim' => 'VARCHAR(10) NOT NULL DEFAULT LTRIM(\'ABC\')',
                'Mynchar' => 'VARCHAR(10) NOT NULL DEFAULT NCHAR(97)',
                'Mypatindex' => 'VARCHAR(10) NOT NULL DEFAULT PATINDEX(\'B\',\'aBc\')',
                'Myreplace' => 'VARCHAR(10) NOT NULL DEFAULT REPLACE(\'ABC\',\'B\',\'D\')',
                'Myright' => 'VARCHAR(10) NOT NULL DEFAULT RIGHT(\'ABC\',1)',
                'Myrtrim' => 'VARCHAR(10) NOT NULL DEFAULT RTRIM(\'ABC\')',
                'Mystr' => 'VARCHAR(10) NOT NULL DEFAULT STR(1.234, 5, 2)',
                'Mystuff' => 'VARCHAR(10) NOT NULL DEFAULT STUFF(\'ABC\',2,1,\'D\')',
                'Mysubstring' => 'VARCHAR(10) NOT NULL DEFAULT SUBSTRING(\'ABC\',2,1)',
                'Myupper' => 'VARCHAR(10) NOT NULL DEFAULT UPPER(\'ABC\')',
            ],
        )->execute();

        $tableSchema = $db->getTableSchema('string');

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/ascii-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myascii')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myascii')->getPhpType());
        $this->assertSame('ascii(\'a\')', $tableSchema?->getColumn('Myascii')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/char-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mychar')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mychar')->getPhpType());
        $this->assertSame('char((97))', $tableSchema?->getColumn('Mychar')->getDefaultValue());

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/charindex-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mycharindex')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mycharindex')->getPhpType());
        $this->assertSame(
            'charindex(\'B\',\'aBc\')',
            $tableSchema?->getColumn('Mycharindex')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/concat-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myconcat')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myconcat')->getPhpType());
        $this->assertSame(
            'concat(\'a\',\'b\',\'c\')',
            $tableSchema?->getColumn('Myconcat')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/concat-ws-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myconcatws')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myconcatws')->getPhpType());
        $this->assertSame(
            'concat_ws(\'a\',\'b\',\'C\')',
            $tableSchema?->getColumn('Myconcatws')->getDefaultValue(),
        );

        /** edge case */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mycomplex')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mycomplex')->getPhpType());
        $this->assertSame(
            'substring(stuff(concat(\'a\',\'b\',\'c\'),(3),(1),concat_ws(\'f\',\'g\',\'h\')),(5),(1))',
            $tableSchema?->getColumn('Mycomplex')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/data-length-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mydatalength')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mydatalength')->getPhpType());
        $this->assertSame(
            'datalength(\'ABC\')',
            $tableSchema?->getColumn('Mydatalength')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/left-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myleft')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myleft')->getPhpType());
        $this->assertSame('left(\'ABC\',(1))', $tableSchema?->getColumn('Myleft')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/len-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mylen')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mylen')->getPhpType());
        $this->assertSame('len(\'ABC\')', $tableSchema?->getColumn('Mylen')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/lower-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mylower')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mylower')->getPhpType());
        $this->assertSame('lower(\'ABC\')', $tableSchema?->getColumn('Mylower')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/ltrim-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myltrim')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myltrim')->getPhpType());
        $this->assertSame('ltrim(\'ABC\')', $tableSchema?->getColumn('Myltrim')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/nchar-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mynchar')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mynchar')->getPhpType());
        $this->assertSame('nchar((97))', $tableSchema?->getColumn('Mynchar')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/patindex-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mypatindex')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mypatindex')->getPhpType());
        $this->assertSame(
            'patindex(\'B\',\'aBc\')',
            $tableSchema?->getColumn('Mypatindex')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/replace-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myreplace')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myreplace')->getPhpType());
        $this->assertSame(
            'replace(\'ABC\',\'B\',\'D\')',
            $tableSchema?->getColumn('Myreplace')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/right-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myright')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myright')->getPhpType());
        $this->assertSame('right(\'ABC\',(1))', $tableSchema?->getColumn('Myright')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/rtrim-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myrtrim')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myrtrim')->getPhpType());
        $this->assertSame('rtrim(\'ABC\')', $tableSchema?->getColumn('Myrtrim')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/str-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mystr')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mystr')->getPhpType());
        $this->assertSame('str((1.234),(5),(2))', $tableSchema?->getColumn('Mystr')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/substring-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mysubstring')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mysubstring')->getPhpType());
        $this->assertSame('substring(\'ABC\',(2),(1))', $tableSchema?->getColumn('Mysubstring')->getDefaultValue());

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/upper-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Myupper')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myupper')->getPhpType());
        $this->assertSame('upper(\'ABC\')', $tableSchema?->getColumn('Myupper')->getDefaultValue());
    }

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
        $this->assertSame('integer', $tableSchema?->getColumn('Myascii')->getPhpType());
        $this->assertSame('int', $tableSchema?->getColumn('Myascii')->getDbType());
        $this->assertSame("ascii('a')", $tableSchema?->getColumn('Myascii')->getDefaultValue());

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/char-transact-sql?view=sql-server-ver16 */
        $this->assertSame('char(1)', $tableSchema?->getColumn('Mychar')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mychar')->getPhpType());
        $this->assertSame('char((97))', $tableSchema?->getColumn('Mychar')->getDefaultValue());

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/charindex-transact-sql?view=sql-server-ver16 */
        $this->assertSame('integer', $tableSchema?->getColumn('Mycharindex')->getPhpType());
        $this->assertSame('int', $tableSchema?->getColumn('Mycharindex')->getDbType());
        $this->assertSame(
            "charindex('B','aBc')",
            $tableSchema?->getColumn('Mycharindex')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/concat-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Myconcat')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myconcat')->getPhpType());
        $this->assertSame(
            "concat('a','b','c')",
            $tableSchema?->getColumn('Myconcat')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/concat-ws-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Myconcatws')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myconcatws')->getPhpType());
        $this->assertSame(
            "concat_ws('a','b','C')",
            $tableSchema?->getColumn('Myconcatws')->getDefaultValue(),
        );

        /* edge case */
        $this->assertSame('varchar(10)', $tableSchema?->getColumn('Mycomplex')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mycomplex')->getPhpType());
        $this->assertSame(
            "substring(stuff(concat('a','b','c'),(3),(1),concat_ws('f','g','h')),(5),(1))",
            $tableSchema?->getColumn('Mycomplex')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/data-length-transact-sql?view=sql-server-ver16 */
        $this->assertSame('integer', $tableSchema?->getColumn('Mydatalength')->getPhpType());
        $this->assertSame('int', $tableSchema?->getColumn('Mydatalength')->getDbType());
        $this->assertSame(
            "datalength('abc')",
            $tableSchema?->getColumn('Mydatalength')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/left-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(1)', $tableSchema?->getColumn('Myleft')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myleft')->getPhpType());
        $this->assertSame(
            "left('abc',(1))",
            $tableSchema?->getColumn('Myleft')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/len-transact-sql?view=sql-server-ver16 */
        $this->assertSame('integer', $tableSchema?->getColumn('Mylen')->getPhpType());
        $this->assertSame('int', $tableSchema?->getColumn('Mylen')->getDbType());
        $this->assertSame(
            "len('abc')",
            $tableSchema?->getColumn('Mylen')->getDefaultValue(),
        );

        /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/lower-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Mylower')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mylower')->getPhpType());
        $this->assertSame(
            "lower('ABC')",
            $tableSchema?->getColumn('Mylower')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/ltrim-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Myltrim')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myltrim')->getPhpType());
        $this->assertSame(
            "ltrim(' abc')",
            $tableSchema?->getColumn('Myltrim')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/nchar-transact-sql?view=sql-server-ver16 */
        $this->assertSame('nchar(1)', $tableSchema?->getColumn('Mynchar')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mynchar')->getPhpType());
        $this->assertSame(
            'nchar((50))',
            $tableSchema?->getColumn('Mynchar')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/patindex-transact-sql?view=sql-server-ver16 */
        $this->assertSame('integer', $tableSchema?->getColumn('Mypatindex')->getPhpType());
        $this->assertSame('int', $tableSchema?->getColumn('Mypatindex')->getDbType());
        $this->assertSame(
            "patindex('a','abc')",
            $tableSchema?->getColumn('Mypatindex')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/replace-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Myreplace')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myreplace')->getPhpType());
        $this->assertSame(
            "replace('abc','a','d')",
            $tableSchema?->getColumn('Myreplace')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/right-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(1)', $tableSchema?->getColumn('Myright')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myright')->getPhpType());
        $this->assertSame(
            "right('abc',(1))",
            $tableSchema?->getColumn('Myright')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/rtrim-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Myrtrim')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myrtrim')->getPhpType());
        $this->assertSame(
            "rtrim('abc ')",
            $tableSchema?->getColumn('Myrtrim')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/str-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(5)', $tableSchema?->getColumn('Mystr')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mystr')->getPhpType());
        $this->assertSame(
            'str((1.234),(5),(2))',
            $tableSchema?->getColumn('Mystr')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/stuff-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Mystuff')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mystuff')->getPhpType());
        $this->assertSame(
            "stuff('abc',(1),(1),'d')",
            $tableSchema?->getColumn('Mystuff')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/substring-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Mysubstring')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Mysubstring')->getPhpType());
        $this->assertSame(
            "substring('abc',(1),(1))",
            $tableSchema?->getColumn('Mysubstring')->getDefaultValue(),
        );

        /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/upper-transact-sql?view=sql-server-ver16 */
        $this->assertSame('varchar(3)', $tableSchema?->getColumn('Myupper')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myupper')->getPhpType());
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
