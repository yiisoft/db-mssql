<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use JsonException;
use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Mssql\Column\ColumnBuilder;
use Yiisoft\Db\Mssql\IndexMethod;
use Yiisoft\Db\Mssql\IndexType;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

use function json_encode;
use function serialize;
use function strtr;

final class CommandProvider extends \Yiisoft\Db\Tests\Provider\CommandProvider
{
    use TestTrait;

    protected static string $driverName = 'sqlsrv';

    public static function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['multirow']['expectedParams'][':qp3'] = 1;
        $batchInsert['multirow']['expectedParams'][':qp7'] = 0;

        $batchInsert['issue11242']['expectedParams'][':qp3'] = 1;

        $batchInsert['table name with column name with brackets']['expectedParams'][':qp3'] = 0;

        $batchInsert['binds params from expression']['expectedParams'][':qp3'] = 0;
        $batchInsert['with associative values']['expectedParams'][':qp3'] = 1;

        return $batchInsert;
    }

    /**
     * @throws JsonException
     */
    public static function dataInsertVarbinary(): array
    {
        return [
            [
                json_encode(['string' => 'string', 'integer' => 1234], JSON_THROW_ON_ERROR),
                json_encode(['string' => 'string', 'integer' => 1234], JSON_THROW_ON_ERROR),
            ],
            [
                serialize(['string' => 'string', 'integer' => 1234]),
                new Param(serialize(['string' => 'string', 'integer' => 1234]), PDO::PARAM_LOB),
            ],
            [
                'simple string',
                'simple string',
            ],
        ];
    }

    public static function rawSql(): array
    {
        $rawSql = parent::rawSql();

        foreach ($rawSql as &$values) {
            $values[2] = strtr($values[2], [
                'FALSE' => '0',
                'TRUE' => '1',
            ]);
        }

        return $rawSql;
    }

    public static function createIndex(): array
    {
        return [
            ...parent::createIndex(),
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::CLUSTERED, null],
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::NONCLUSTERED, null],
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::UNIQUE, null],
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::UNIQUE_CLUSTERED, null],
            [['id' => ColumnBuilder::primaryKey(), 'col1' => 'geometry'], ['col1'], IndexType::SPATIAL, IndexMethod::GEOMETRY_GRID . ' WITH(BOUNDING_BOX = (0, 0, 100, 100))'],
            [['id' => ColumnBuilder::primaryKey(), 'col1' => 'geometry'], ['col1'], IndexType::SPATIAL, IndexMethod::GEOMETRY_AUTO_GRID . ' WITH(BOUNDING_BOX = (0, 0, 100, 100))'],
            [['id' => ColumnBuilder::primaryKey(), 'col1' => 'geography'], ['col1'], IndexType::SPATIAL, null],
            [['id' => ColumnBuilder::primaryKey(), 'col1' => 'geography'], ['col1'], IndexType::SPATIAL, IndexMethod::GEOGRAPHY_GRID],
            [['id' => ColumnBuilder::primaryKey(), 'col1' => 'geography'], ['col1'], IndexType::SPATIAL, IndexMethod::GEOGRAPHY_AUTO_GRID],
            [['col1' => ColumnBuilder::integer()], ['col1'], IndexType::COLUMNSTORE, null],
            [['id' => ColumnBuilder::primaryKey(), 'col1' => 'xml'], ['col1'], IndexType::PRIMARY_XML, null],
        ];
    }
}
