<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;

/**
 * Builds SQL representation of function expressions which returns the longest string from a set of operands.
 *
 * ```SQL
 * (SELECT TOP 1 value FROM (
 *     SELECT operand1 AS value
 *     UNION
 *     SELECT operand2 AS value
 * ) AS t ORDER BY LEN(value) DESC)
 * ```
 *
 * @extends MultiOperandFunctionBuilder<Longest>
 */
final class LongestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL expression to represent the function which returns the longest string.
     *
     * @param Greatest $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $builtSelects = [];

        foreach ($expression->getOperands() as $operand) {
            $builtSelects[] = $this->buildSelect($operand, $params);
        }

        $unions = implode(' UNION ', $builtSelects);

        return "(SELECT TOP 1 value FROM ($unions) AS t ORDER BY LEN(value) DESC)";
    }

    protected function buildSelect(mixed $operand, array &$params): string
    {
        return 'SELECT ' . $this->buildOperand($operand, $params) . ' AS value';
    }
}
