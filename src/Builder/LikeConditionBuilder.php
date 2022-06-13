<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Yiisoft\Db\QueryBuilder\Conditions\Builder\LikeConditionBuilder as AbstractLikeConditionBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

final class LikeConditionBuilder extends AbstractLikeConditionBuilder
{
    /**
     * @var array map of chars to their replacements in LIKE conditions.
     * By default it's configured to escape `%`, `_`, `[` with `]`, `\\`.
     */
    protected array $escapingReplacements = [
        '%' => '[%]',
        '_' => '[_]',
        '[' => '[[]',
        ']' => '[]]',
        '\\' => '[\\]',
    ];

    public function __construct(QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
    }
}
