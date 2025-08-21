<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\Expression\Function\Shortest;

/**
 * Builds SQL representation of function expressions which return the shortest string from a set of operands.
 *
 * ```SQL
 * (SELECT TOP 1 value FROM (
 *     SELECT operand1 AS value
 *     UNION
 *     SELECT operand2 AS value
 * ) AS t ORDER BY LEN(value) ASC)
 * ```
 *
 * @extends MultiOperandFunctionBuilder<Shortest>
 */
final class ShortestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL expression to represent the function which returns the shortest string.
     *
     * @param Shortest $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $selects = [];

        foreach ($expression->getOperands() as $operand) {
            $selects[] = 'SELECT ' . $this->buildOperand($operand, $params) . ' AS value';
        }

        $unions = implode(' UNION ', $selects);

        return "(SELECT TOP 1 value FROM ($unions) AS t ORDER BY LEN(value) ASC)";
    }
}
