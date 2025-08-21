<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\Least;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;

use function implode;

/**
 * Builds SQL `LEAST()` function expressions for {@see Least} objects.
 *
 * @extends MultiOperandFunctionBuilder<Least>
 */
final class LeastBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL `LEAST()` function expression from the given {@see Least} object.
     *
     * @param Least $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL `LEAST()` function expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $serverVersion = $this->queryBuilder->getServerInfo()->getVersion();

        if (version_compare($serverVersion, '16', '<')) {
            $builtSelects = [];
            foreach ($expression->getOperands() as $operand) {
                $builtSelects[] = 'SELECT ' . $this->buildOperand($operand, $params) . ' AS value';
            }

            $unions = implode(' UNION ', $builtSelects);

            return "(SELECT MIN(value) FROM ($unions) AS t)";
        }

        $builtOperands = [];

        foreach ($expression->getOperands() as $operand) {
            $builtOperands[] = $this->buildOperand($operand, $params);
        }

        return 'LEAST(' . implode(', ', $builtOperands) . ')';
    }
}
