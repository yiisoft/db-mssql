<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Mssql\Tests\Support\MssqlHelper;
use Yiisoft\Db\Tests\Provider\AbstractCommandProvider;

use function json_encode;
use function serialize;

final class CommandProvider extends AbstractCommandProvider
{
    public function batchInsertSql()
    {
        $data = $this->getbatchInsertSql('sqlsrv');

        $data['multirow']['expectedParams'][':qp1'] = '0.0';
        $data['multirow']['expectedParams'][':qp3'] = 1;
        $data['multirow']['expectedParams'][':qp5'] = '0';
        $data['multirow']['expectedParams'][':qp7'] = 0;

        $data['issue11242']['expectedParams'][':qp1'] = '1.1';
        $data['issue11242']['expectedParams'][':qp3'] = 1;

        $data['wrongBehavior']['expectedParams'][':qp1'] = '0.0';
        $data['wrongBehavior']['expectedParams'][':qp3'] = 0;

        $data['batchInsert binds params from expression']['expectedParams'][':qp1'] = '1';
        $data['batchInsert binds params from expression']['expectedParams'][':qp3'] = 0;

        return $data;
    }

    public function bindParamsNonWhere(): array
    {
        return[
            ['SELECT SUBSTRING(name, :len, 6) AS name FROM {{customer}} WHERE [[email]] = :email GROUP BY name'],
            ['SELECT SUBSTRING(name, :len, 6) as name FROM {{customer}} WHERE [[email]] = :email ORDER BY name'],
            ['SELECT SUBSTRING(name, :len, 6) FROM {{customer}} WHERE [[email]] = :email'],
        ];
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

    public function invalidSelectColumn(): array
    {
        return parent::invalidSelectColumn();
    }

    public function rawSql(): array
    {
        return parent::rawSql();
    }

    public function upsert(): array
    {
        $mssqlHelper = new MssqlHelper();

        return $this->getUpsert($mssqlHelper->createConnection());
    }
}
