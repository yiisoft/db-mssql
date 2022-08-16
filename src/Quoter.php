<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;

use function preg_match;
use function preg_match_all;
use function str_replace;

final class Quoter extends BaseQuoter
{
    /**
     * @psalm-param string[] $columnQuoteCharacter
     * @psalm-param string[] $tableQuoteCharacter
     */
    public function __construct(
        array $columnQuoteCharacter,
        array $tableQuoteCharacter,
        string $tablePrefix = ''
    ) {
        parent::__construct($columnQuoteCharacter, $tableQuoteCharacter, $tablePrefix);
    }

    public function getTableNameParts(string $name): array
    {
        $parts = [$name];
        preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)]/', $name, $matches);

        if (isset($matches[0]) && !empty($matches[0])) {
            $parts = array_slice($matches[0], -4, 4, true);
        }

        return str_replace(['[', ']'], '', $parts);
    }

    public function quoteColumnName(string $name): string
    {
        if (preg_match('/^\[.*]$/', $name)) {
            return $name;
        }

        return parent::quoteColumnName($name);
    }
}
