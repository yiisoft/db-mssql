<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Function;

use PHPUnit\Framework\TestCase;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
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
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\StringProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        string $defaultValue
    ): void {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('string');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('string')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\StringProvider::columns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValue(
        string $column,
        string $dbType,
        string $phpType,
        string $defaultValue
    ): void {
        $this->setFixture('Function/string.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('string');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('string')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('string') !== null) {
            $command->dropTable('string')->execute();
        }

        $command->createTable(
            'string',
            [
                'id' => 'INT NOT NULL IDENTITY',
                'Myascii' => 'INT NOT NULL DEFAULT ASCII(\'a\')',
                'Mychar' => 'CHAR(1) NOT NULL DEFAULT CHAR(97)',
                'Mycharindex' => 'INT NOT NULL DEFAULT charindex(\'B\', \'aBc\')',
                'Myconcat' => 'VARCHAR(3) NOT NULL DEFAULT CONCAT(\'a\',\'b\',\'c\')',
                'Myconcatws' => 'VARCHAR(3) NOT NULL DEFAULT CONCAT_WS(\'a\',\'b\',\'C\')',
                'Mycomplex' => 'VARCHAR(10) NOT NULL DEFAULT SUBSTRING(STUFF(concat(\'a\', \'b\', \'c\'), 3, 1, concat_ws(\'f\', \'g\', \'h\')), 5, 1)',
                'Mydatalength' => 'INT NOT NULL DEFAULT DATALENGTH(\'abc\')',
                'Myleft' => 'VARCHAR(1) NOT NULL DEFAULT LEFT(\'abc\',1)',
                'Mylen' => 'INT NOT NULL DEFAULT LEN(\'abc\')',
                'Mylower' => 'VARCHAR(3) NOT NULL DEFAULT LOWER(\'ABC\')',
                'Myltrim' => 'VARCHAR(3) NOT NULL DEFAULT LTRIM(\' abc\')',
                'Mynchar' => 'NCHAR(1) NOT NULL DEFAULT NCHAR(50)',
                'Mypatindex' => 'INT NOT NULL DEFAULT PATINDEX(\'a\',\'abc\')',
                'Myreplace' => 'VARCHAR(3) NOT NULL DEFAULT replace(\'abc\',\'a\',\'d\')',
                'Myright' => 'VARCHAR(1) NOT NULL DEFAULT right(\'abc\',(1))',
                'Myrtrim' => 'VARCHAR(3) NOT NULL DEFAULT rtrim(\'abc \')',
                'Mystr' => 'VARCHAR(5) NOT NULL DEFAULT str((1.234),(5),(2))',
                'Mystuff' => 'VARCHAR(3) NOT NULL DEFAULT stuff(\'abc\',(1),(1),\'d\')',
                'Mysubstring' => 'VARCHAR(3) NOT NULL DEFAULT substring(\'abc\',(1),(1))',
                'Myupper' => 'VARCHAR(3) NOT NULL DEFAULT upper(\'abc\')',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
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
        ];
    }
}
