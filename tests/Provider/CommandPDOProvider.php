<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use PDO;

final class CommandPDOProvider
{
    public static function bindParam(): array
    {
        return [
            [
                'id',
                ':id',
                1,
                PDO::PARAM_INT,
                null,
                null,
                [
                    'id' => '1',
                    'email' => 'user1@example.com',
                    'name' => 'user1',
                    'address' => 'address1',
                    'status' => '1',
                    'profile_id' => '1',
                ],
            ],
            [
                'id',
                ':id',
                1,
                PDO::PARAM_INT,
                0,
                null,
                [
                    'id' => '1',
                    'email' => 'user1@example.com',
                    'name' => 'user1',
                    'address' => 'address1',
                    'status' => '1',
                    'profile_id' => '1',
                ],
            ],
            [
                'status',
                ':status',
                2,
                PDO::PARAM_STR,
                0,
                PDO::SQLSRV_ENCODING_UTF8,
                [
                    'id' => '3',
                    'email' => 'user3@example.com',
                    'name' => 'user3',
                    'address' => 'address3',
                    'status' => '2',
                    'profile_id' => '2',
                ],
            ],
        ];
    }

    public static function bindParamsNonWhere(): array
    {
        return[
            [
                <<<SQL
                SELECT SUBSTRING([[name]], :len, 6) AS name FROM [[customer]] WHERE [[email]] = :email GROUP BY name
                SQL,
            ],
            [
                <<<SQL
                SELECT SUBSTRING([[name]], :len, 6) as name FROM [[customer]] WHERE [[email]] = :email ORDER BY name
                SQL,
            ],
            [
                <<<SQL
                SELECT SUBSTRING([[name]], :len, 6) FROM [[customer]] WHERE [[email]] = :email
                SQL,
            ],
        ];
    }
}
