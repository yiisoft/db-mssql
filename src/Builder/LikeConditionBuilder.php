<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Yiisoft\Db\QueryBuilder\Condition\Builder\LikeConditionBuilder as AbstractLikeConditionBuilder;

/**
 * LikeConditionBuilder builds conditions for {@see `\Yiisoft\Db\QueryBuilder\Condition\LikeCondition`} LIKE operator
 * for MSSQL Server.
 */
final class LikeConditionBuilder extends AbstractLikeConditionBuilder
{
    /**
     * @var array map of chars to their replacements in LIKE conditions.
     * By default, it's configured to escape `%`, `_`, `[` with `]`, `\\`.
     */
    protected array $escapingReplacements = [
        '%' => '[%]',
        '_' => '[_]',
        '[' => '[[]',
        ']' => '[]]',
        '\\' => '[\\]',
    ];
}
