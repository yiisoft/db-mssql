<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Schema\SchemaInterface;

final class ColumnSchemaBuilderProvider extends \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider
{
    protected static string $driverName = 'mssql';

    public static function createColumnTypes(): array
    {
        $types = parent::createColumnTypes();
        $types['integer'][0] = '[column] int';

        $types['uuid'][0] = '[column] UNIQUEIDENTIFIER';
        $types['uuid not null'][0] = '[column] UNIQUEIDENTIFIER NOT NULL';

        $types['uuid with default'][0] = '[column] UNIQUEIDENTIFIER DEFAULT \'875343b3-6bd0-4bec-81bb-aa68bb52d945\'';
        $types['uuid with default'][3] = [['defaultValue', '875343b3-6bd0-4bec-81bb-aa68bb52d945']];

        $types['uuid pk'][0] = '[column] UNIQUEIDENTIFIER PRIMARY KEY';
        $types['uuid pk not null'][0] = '[column] UNIQUEIDENTIFIER PRIMARY KEY NOT NULL';
        $types['uuid pk not null with default'][0] = '[column] UNIQUEIDENTIFIER PRIMARY KEY NOT NULL DEFAULT NEWID()';
        $types['uuid pk not null with default'][3] = [['notNull'], ['defaultExpression', 'NEWID()']];
        $types['uuid pk sequence'][0] = '[column] UNIQUEIDENTIFIER PRIMARY KEY NOT NULL DEFAULT NEWID()';

        return $types;
        return $types + [
                [
                    '[column] UNIQUEIDENTIFIER',
                    SchemaInterface::TYPE_UUID,
                    null,
                    []
                ],
                [
                    '[column] UNIQUEIDENTIFIER NOT NULL',
                    SchemaInterface::TYPE_UUID,
                    null,
                    [['notNull']]
                ],
                [
                    '[column] UNIQUEIDENTIFIER DEFAULT \'875343b3-6bd0-4bec-81bb-aa68bb52d945\'',
                    SchemaInterface::TYPE_UUID,
                    null,
                    [['defaultValue', '875343b3-6bd0-4bec-81bb-aa68bb52d945']]
                ],
                [
                    '[column] UNIQUEIDENTIFIER PRIMARY KEY',
                    SchemaInterface::TYPE_UUID_PK,
                    null,
                    []
                ],
                [
                    '[column] UNIQUEIDENTIFIER PRIMARY KEY NOT NULL',
                    SchemaInterface::TYPE_UUID_PK,
                    null,
                    [['notNull']]
                ],
                [
                    '[column] UNIQUEIDENTIFIER PRIMARY KEY NOT NULL DEFAULT NEWID()',
                    SchemaInterface::TYPE_UUID_PK,
                    null,
                    [['notNull'], ['defaultExpression', 'NEWID()']]
                ],
                [
                    '[column] UNIQUEIDENTIFIER PRIMARY KEY NOT NULL DEFAULT NEWID()',
                    SchemaInterface::TYPE_UUID_PK_SEQ,
                    null,
                    []
                ],
            ];
    }
}
