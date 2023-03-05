<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

/**
 * Builds conditions for {@see `\Yiisoft\Db\QueryBuilder\Condition\LikeCondition`} LIKE operator for MSSQL Server.
 */
final class LikeConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder
{
    /**
     * @var array Map of chars to their replacements in LIKE conditions. By default, it's configured to escape
     * `%`, `_`, `[` with `]`, `\\`.
     */
    protected array $escapingReplacements = [
        '%' => '[%]',
        '_' => '[_]',
        '[' => '[[]',
        ']' => '[]]',
        '\\' => '[\\]',
    ];
}
