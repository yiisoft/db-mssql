<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\AbstractQuoter;

use function preg_match;
use function preg_match_all;

final class Quoter extends AbstractQuoter
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
        if (preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)]/', $name, $matches)) {
            $parts = array_slice($matches[0], -4, 4);
        } else {
            $parts = [$name];
        }

        return $this->unquoteParts($parts, $withColumn);
    }
}
