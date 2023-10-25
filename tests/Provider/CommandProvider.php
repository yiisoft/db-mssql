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
