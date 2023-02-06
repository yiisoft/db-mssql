<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Provider\AbstractCommandProvider;

use function json_encode;
use function serialize;

final class CommandProvider extends AbstractCommandProvider
{
    use TestTrait;

    public function batchInsert(): array
    {
        $batchInsert = parent::batchInsert();

        $batchInsert['multirow']['expectedParams'][':qp3'] = 1;
        $batchInsert['multirow']['expectedParams'][':qp7'] = 0;

        $batchInsert['issue11242']['expectedParams'][':qp3'] = 1;

        $batchInsert['wrongBehavior']['expectedParams'][':qp3'] = 0;

        $batchInsert['batchInsert binds params from expression']['expectedParams'][':qp3'] = 0;

        return $batchInsert;
    }

    public function dataInsertVarbinary(): array
    {
        return [
            [
                json_encode(['string' => 'string', 'integer' => 1234]),
                json_encode(['string' => 'string', 'integer' => 1234]),
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
