<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Type;

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
 *
* @link https://learn.microsoft.com/en-us/sql/t-sql/data-types/date-transact-sql?view=sql-server-ver16
 */
final class JsonTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithDefaultValue(): void
    {
        $db = $this->buildTable();

        $tableSchema = $db->getTableSchema('json_default');

        $this->assertSame('nvarchar', $tableSchema?->getColumn('Myjson')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myjson')->getPhpType());
        $this->assertSame('{}', $tableSchema?->getColumn('Myjson')->getDefaultValue());

        $db->createCommand()->dropTable('json_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testCreateTableWithInsert(): void
    {
        $db = $this->buildTable();

        $command = $db->createCommand();
        $command->insert('json_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[json_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('json_default')->execute();
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
        $this->setFixture('Type/json.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('json_default');

        $this->assertSame('nvarchar', $tableSchema?->getColumn('Myjson')->getDbType());
        $this->assertSame('string', $tableSchema?->getColumn('Myjson')->getPhpType());
        $this->assertSame('{}', $tableSchema?->getColumn('Myjson')->getDefaultValue());

        $db->createCommand()->dropTable('json_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testDefaultValueWithInsert(): void
    {
        $this->setFixture('Type/json.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('json_default', [])->execute();

        $this->assertSame(
            $this->getColumns(),
            $command->setSql(
                <<<SQL
                SELECT * FROM [[json_default]]
                SQL
            )->queryOne(),
        );

        $db->createCommand()->dropTable('json_default')->execute();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testInvalidValue(): void
    {
        $this->setFixture('Type/json.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('json', ['Myjson' => 'invalid'])->execute();

        $this->assertSame(
            '0',
            $command->setSql(
                <<<SQL
                SELECT ISJSON([[Myjson]]) AS [[Myjson]] FROM [[json]] WHERE id = 1
                SQL
            )->queryScalar(),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testValidValue(): void
    {
        $this->setFixture('Type/json.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('json', ['Myjson' => '{"a":1,"b":2,"c":3,"d":4,"e":5}'])->execute();

        $this->assertSame(
            '1',
            $command->setSql(
                <<<SQL
                SELECT ISJSON([[Myjson]]) AS [[Myjson]] FROM [[json]] WHERE [[id]] = 1
                SQL
            )->queryScalar(),
        );
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws InvalidArgumentException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function testValue(): void
    {
        $this->setFixture('Type/json.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('json', ['Myjson' => '{"a":1,"b":2,"c":3,"d":4,"e":5}'])->execute();

        $this->assertSame(
            [
                'id' => '1',
                'Myjson' => '{"a":1,"b":2,"c":3,"d":4,"e":5}',
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[json]] WHERE [[id]] = 1
                SQL
            )->queryOne()
        );

        $command->insert('json', ['Myjson' => null])->execute();

        $this->assertSame(
            [
                'id' => '2',
                'Myjson' => null,
            ],
            $command->setSql(
                <<<SQL
                SELECT * FROM [[json]] WHERE [[id]] = 2
                SQL
            )->queryOne()
        );
        $this->assertSame(
            [
                'a' => '1',
                'b' => '2',
                'c' => '3',
                'd' => '4',
                'e' => '5',
            ],
            $command->setSql(
                <<<SQL
                SELECT JSON_VALUE([[Myjson]], '$.a') AS [[a]], JSON_VALUE([[Myjson]], '$.b') AS [[b]], JSON_VALUE([[Myjson]], '$.c') AS [[c]], JSON_VALUE([[Myjson]], '$.d') AS [[d]], JSON_VALUE([[Myjson]], '$.e') AS [[e]] FROM [[json]] WHERE [[id]] = 1
                SQL
            )->queryOne(),
        );

        $command->insert(
            'json',
            [
                'Myjson' => '{"info": {"type": 1,"address": {"town": "Cheltenham","country": "England"},"tags": ["Sport", "Water polo"]},"type": "Basic"}',
            ]
        )->execute();

        $this->assertSame(
            [
                'address' => '{"town": "Cheltenham","country": "England"}',
            ],
            $command->setSql(
                <<<SQL
                SELECT JSON_QUERY([[Myjson]], '$.info.address') AS address FROM [[json]] WHERE [[id]] = 3
                SQL
            )->queryOne(),
        );
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('json_default') !== null) {
            $command->dropTable('json_default')->execute();
        }

        $command->createTable(
            'json_default',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myjson' => 'NVARCHAR(MAX) DEFAULT \'{}\'',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myjson' => '{}',
        ];
    }
}
