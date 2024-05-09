<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;

use function array_map;
use function array_slice;
use function preg_match;
use function preg_match_all;

/**
 * Implements the MSSQL Server quoting and unquoting methods.
 */
final class Quoter extends BaseQuoter
{
    public function quoteColumnName(string $name): string
    {
        if (preg_match('/^\[.*]$/', $name)) {
            return $name;
        }

        return parent::quoteColumnName($name);
    }

    public function getTableNameParts(string $name, bool $withColumn = false): array
    {
        if (preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)]/', $name, $matches) > 0) {
            $parts = array_slice($matches[0], -4, 4);

            return array_map([$this, 'unquoteSimpleTableName'], $parts);
        }

        return [$this->unquoteSimpleTableName($name)];
    }
}
