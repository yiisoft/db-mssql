<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use PDO;
use Yiisoft\Db\Schema\Quoter as BaseQuoter;

use function preg_match;
use function preg_match_all;

final class Quoter extends BaseQuoter
{
    /**
     * @psalm-param string[] $columnQuoteCharacter
     * @psalm-param string[] $tableQuoteCharacter
     */
    public function __construct(
        array $columnQuoteCharacter,
        array $tableQuoteCharacter,
        string $tablePrefix = '',
        protected PDO|null $pdo = null,
    ) {
        parent::__construct($columnQuoteCharacter, $tableQuoteCharacter, $tablePrefix, $pdo);
    }

    public function quoteColumnName(string $name): string
    {
        if (preg_match('/^\[.*]$/', $name)) {
            return $name;
        }

        return parent::quoteColumnName($name);
    }

    public function getTableNameParts(string $name): array
    {
        if (preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)]/', $name, $matches)) {
            $parts = array_slice($matches[0], -4, 4);
        } else {
            $parts = [$name];
        }

        return array_map(function ($part) {
            return $this->unquoteSimpleTableName($part);
        }, $parts);
    }
}
