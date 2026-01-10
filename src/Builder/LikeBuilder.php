<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

/**
 * Build an object of {@see LikeCondition} into SQL expressions for MSSQL Server.
 */
final class LikeBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder
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

    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        if ($expression->caseSensitive === true) {
            throw new NotSupportedException('MSSQL doesn\'t support case-sensitive "LIKE" conditions.');
        }
        return parent::build($expression, $params);
    }
}
