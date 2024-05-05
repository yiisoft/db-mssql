<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use JsonException;
use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

use function json_encode;
use function serialize;

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
}
