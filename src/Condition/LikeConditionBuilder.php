<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Condition;

use Yiisoft\Db\Query\Conditions\LikeConditionBuilder as AbstractLikeConditionBuilder;

final class LikeConditionBuilder extends AbstractLikeConditionBuilder
{
    /**
     * @var array map of chars to their replacements in LIKE conditions. By default it's configured to escape `%`, `_`,
     * `[` with `]`, `\\`.
     */
    protected array $escapingReplacements = [
        '%' => '[%]',
        '_' => '[_]',
        '[' => '[[]',
        ']' => '[]]',
        '\\' => '[\\]',
    ];
}
