<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

/**
 * Build an object of {@see \Yiisoft\Db\QueryBuilder\Condition\LikeCondition} into SQL expressions for MSSQL Server.
 */
final class LikeConditionBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder
{
    /**
     * @var array Map of chars to their replacements in `LIKE` conditions. By default, it's configured to escape
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
