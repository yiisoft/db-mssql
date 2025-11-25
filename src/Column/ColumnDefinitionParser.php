<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Syntax\AbstractColumnDefinitionParser;

final class ColumnDefinitionParser extends AbstractColumnDefinitionParser
{
    protected function parseTypeParams(string $type, string $params): array
    {
        return match ($type) {
            'bigint',
            'binary',
            'char',
            'datetime2',
            'datetimeoffset',
            'decimal',
            'float',
            'int',
            'nchar',
            'numeric',
            'nvarchar',
            'smallint',
            'string',
            'time',
            'tinyint',
            'varbinary',
            'varchar' => $this->parseSizeInfo($params),
            default => [],
        };
    }
}
