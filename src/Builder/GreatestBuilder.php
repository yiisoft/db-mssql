<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;

use function implode;

/**
 * Builds SQL `GREATEST()` function expressions for {@see Greatest} objects.
 *
 * @extends MultiOperandFunctionBuilder<Greatest>
 */
final class GreatestBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL `GREATEST()` function expression from the given {@see Greatest} object.
     *
     * @param Greatest $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL `GREATEST()` function expression.
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

            return "(SELECT MAX(value) FROM ($unions) AS t)";
        }

        $builtOperands = [];

        foreach ($expression->getOperands() as $operand) {
            $builtOperands[] = $this->buildOperand($operand, $params);
        }

        return 'GREATEST(' . implode(', ', $builtOperands) . ')';
    }
}
