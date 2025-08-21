<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;

use function implode;

/**
 * Builds SQL expressions which merge arrays for {@see ArrayMerge} objects.
 *
 * ```sql
 * (SELECT '[' + STRING_AGG('"' + STRING_ESCAPE(value, 'json') + '"', ',') + ']' AS value FROM (
 *     SELECT value FROM OPENJSON(operand1)
 *     UNION
 *     SELECT value FROM OPENJSON(operand2)
 * ) AS t)
 * ```
 *
 * @extends MultiOperandFunctionBuilder<ArrayMerge>
 */
final class ArrayMergeBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL expression which merges arrays from the given {@see ArrayMerge} object.
     *
     * @param ArrayMerge $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $selects = [];

        foreach ($expression->getOperands() as $operand) {
            $selects[] = 'SELECT value FROM OPENJSON(' . $this->buildOperand($operand, $params) . ')';
        }

        $unions = implode(' UNION ', $selects);

        return <<<SQL
            (SELECT '[' + STRING_AGG('"' + STRING_ESCAPE(value, 'json') + '"', ',') + ']' AS value FROM ($unions) AS t)
            SQL;
    }
}
