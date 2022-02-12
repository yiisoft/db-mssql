<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;
use Yiisoft\Db\Schema\QuoterInterface;

final class Quoter extends BaseQuoter implements QuoterInterface
{
    public function __construct(
        private array $columnQuoteCharacter,
        private array $tableQuoteCharacter,
        private string $tablePrefix = ''
    ) {
        parent::__construct($columnQuoteCharacter, $tableQuoteCharacter, $tablePrefix);
    }

    public function quoteColumnName(string $name): string
    {
        if (preg_match('/^\[.*]$/', $name)) {
            return $name;
        }

        return parent::quoteColumnName($name);
    }

    protected function getTableNameParts(string $name): array
    {
        $parts = [$name];

        preg_match_all('/([^.\[\]]+)|\[([^\[\]]+)]/', $name, $matches);

        if (isset($matches[0]) && !empty($matches[0])) {
            $parts = $matches[0];
        }

        return str_replace(['[', ']'], '', $parts);
    }
}
