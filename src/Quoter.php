<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;

use function array_map;
use function array_slice;
use function explode;

/**
 * Implements the MSSQL Server quoting and unquoting methods.
 */
final class Quoter extends BaseQuoter
{
    public function getTableNameParts(string $name, bool $withColumn = false): array
    {
        $parts = array_slice(explode('.', $name), -4, 4);

        return array_map($this->unquoteSimpleTableName(...), $parts);
    }
}
