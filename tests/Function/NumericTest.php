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
final class NumericTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\NumericProvider::columns
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

        $tableSchema = $db->getTableSchema('numeric');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('numeric')->execute();
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
        $command->insert('numeric', [])->execute();

        $row = $command->setSql(
            <<<SQL
            SELECT * FROM [numeric] WHERE [id] = 1
            SQL
        )->queryOne();

        unset($row['Myrand']);

        $this->assertSame($this->getColumns(), $row);

        $db->createCommand()->dropTable('numeric')->execute();
    }

    /**
     * @dataProvider \Yiisoft\Db\Mssql\Tests\Provider\Function\NumericProvider::columns
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
        $this->setFixture('Function/numeric.sql');

        $db = $this->getConnection(true);
        $tableSchema = $db->getTableSchema('numeric');

        $this->assertSame($dbType, $tableSchema?->getColumn($column)->getDbType());
        $this->assertSame($phpType, $tableSchema?->getColumn($column)->getPhpType());
        $this->assertSame($defaultValue, $tableSchema?->getColumn($column)->getDefaultValue());

        $db->createCommand()->dropTable('numeric')->execute();
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
        $this->setFixture('Function/numeric.sql');

        $db = $this->getConnection(true);
        $command = $db->createCommand();
        $command->insert('numeric', [])->execute();

        $row = $command->setSql(
            <<<SQL
            SELECT * FROM [numeric] WHERE [id] = 1
            SQL
        )->queryOne();

        unset($row['Myrand']);

        $this->assertSame($this->getColumns(), $row);

        $db->createCommand()->dropTable('numeric')->execute();
    }

    private function buildTable(): ConnectionInterface
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        if ($db->getSchema()->getTableSchema('numeric') !== null) {
            $command->dropTable('numeric')->execute();
        }

        $command->createTable(
            'numeric',
            [
                'id' => 'INT IDENTITY NOT NULL',
                'Myabs' => 'NUMERIC(3,1) NOT NULL DEFAULT ABS(-1)',
                'Myacos' => 'NUMERIC(8,5) NOT NULL DEFAULT ACOS(-1.0)',
                'Myasin' => 'NUMERIC(7,5) NOT NULL DEFAULT ASIN(0.1472738)',
                'Myatan' => 'NUMERIC(11,7) NOT NULL DEFAULT ATAN((197.1099392))',
                'Myceiling' => 'MONEY NOT NULL DEFAULT CEILING($-123.45)',
                'Mycos' => 'NUMERIC(9,6) NOT NULL DEFAULT COS(14.78)',
                'Mycot' => 'NUMERIC(9,6) NOT NULL DEFAULT COT(124.1332)',
                'Mydegrees' => 'NUMERIC(18,7) NOT NULL DEFAULT DEGREES((PI()/2))',
                'Myexp' => 'NUMERIC(11,5) NOT NULL DEFAULT EXP(10.0)',
                'Myfloor' => 'INT NOT NULL DEFAULT FLOOR(-123.45)',
                'Mylog' => 'NUMERIC(6,5) NOT NULL DEFAULT LOG(10.0)',
                'Mylog10' => 'NUMERIC(6,5) NOT NULL DEFAULT LOG10(145.175643)',
                'Mypi' => 'NUMERIC(6,5) NOT NULL DEFAULT PI()',
                'Mypower' => 'NUMERIC(6,3) NOT NULL DEFAULT POWER(2, 2.5)',
                'Myradians' => 'NUMERIC(7,5) NOT NULL DEFAULT RADIANS(180.0)',
                'Myrand' => 'NUMERIC(7,5) NOT NULL DEFAULT RAND()',
                'Myround' => 'NUMERIC(8,4) NOT NULL DEFAULT ROUND(123.9994, 3)',
                'Mysign' => 'FLOAT NOT NULL DEFAULT SIGN(-125)',
                'Mysin' => 'NUMERIC(8,6) NOT NULL DEFAULT SIN(45.175643)',
                'Mysqrt' => 'FLOAT NOT NULL DEFAULT SQRT(10.0)',
            ],
        )->execute();

        return $db;
    }

    private function getColumns(): array
    {
        return [
            'id' => '1',
            'Myabs' => '1.0',
            'Myacos' => '3.14159',
            'Myasin' => '.14781',
            'Myatan' => '1.5657231',
            'Myceiling' => '-123.0000',
            'Mycos' => '-.599465',
            'Mycot' => '-.040312',
            'Mydegrees' => '90.0000000',
            'Myexp' => '22026.46579',
            'Myfloor' => '-124',
            'Mylog' => '2.30259',
            'Mylog10' => '2.16189',
            'Mypi' => '3.14159',
            'Mypower' => '5.000',
            'Myradians' => '3.14159',
            'Myround' => '123.9990',
            'Mysign' => '-1.0',
            'Mysin' => '.929607',
            'Mysqrt' => '3.1622776601683795',
        ];
    }
}
