<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Schema\Quoter as BaseQuoter;
use Yiisoft\Db\Schema\QuoterInterface;

use function preg_match;
use function preg_match_all;
use function str_replace;

final class Quoter extends BaseQuoter implements QuoterInterface
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
