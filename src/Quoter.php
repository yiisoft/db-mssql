<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;

use function array_map;
use function array_reverse;
use function array_slice;
use function explode;

/**
 * Implements the MSSQL Server quoting and unquoting methods.
 */
final class Quoter extends BaseQuoter
{
    /**
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @psalm-return array{serverName?: string, catalogName?: string, schemaName?: string, name: string}
     */
    public function getTableNameParts(string $name): array
    {
        $parts = array_reverse(array_slice(explode('.', $name), -4, 4));
        /** @var string[] */
        $parts = array_map($this->unquoteSimpleTableName(...), $parts);

        if (!isset($parts[1])) {
            return ['name' => $parts[0]];
        }

        if (!isset($parts[2])) {
            return [
                'schemaName' => $parts[1],
                'name' => $parts[0],
            ];
        }

        if (!isset($parts[3])) {
            return [
                'catalogName' => $parts[2],
                'schemaName' => $parts[1],
                'name' => $parts[0],
            ];
        }

        return [
            'serverName' => $parts[3],
            'catalogName' => $parts[2],
            'schemaName' => $parts[1],
            'name' => $parts[0],
        ];
    }
}
