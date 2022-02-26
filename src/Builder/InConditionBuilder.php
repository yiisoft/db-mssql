<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Builder;

use Traversable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\Conditions\Builder\InConditionBuilder as AbstractInConditionBuilder;
use Yiisoft\Db\Query\QueryBuilderInterface;

use function implode;
use function is_array;
use function strpos;

final class InConditionBuilder extends AbstractInConditionBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
    }

    /**
     * Builds SQL for IN condition.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    protected function buildSubqueryInCondition(
        string $operator,
        array|string $columns,
        ExpressionInterface $values,
        array &$params = []
    ): string {
        if (is_array($columns)) {
            throw new NotSupportedException(__METHOD__ . ' is not supported by MSSQL.');
        }

        return parent::buildSubqueryInCondition($operator, $columns, $values, $params);
    }

    /**
     * Builds SQL for IN condition.
     */
    protected function buildCompositeInCondition(
        ?string $operator,
        array|Traversable $columns,
        array|Traversable $values,
        array &$params = []
    ): string {
        $quotedColumns = [];

        /** @psalm-var string[] $columns */
        foreach ($columns as $i => $column) {
            $quotedColumns[$i] = strpos($column, '(') === false
                ? $this->queryBuilder->quoter()->quoteColumnName($column) : $column;
        }

        $vss = [];

        /** @psalm-var string[][] $values */
        foreach ($values as $value) {
            $vs = [];
            foreach ($columns as $i => $column) {
                if (isset($value[$column])) {
                    $phName = $this->queryBuilder->bindParam($value[$column], $params);
                    $vs[] = $quotedColumns[$i] . ($operator === 'IN' ? ' = ' : ' != ') . $phName;
                } else {
                    $vs[] = $quotedColumns[$i] . ($operator === 'IN' ? ' IS' : ' IS NOT') . ' NULL';
                }
            }
            $vss[] = '(' . implode($operator === 'IN' ? ' AND ' : ' OR ', $vs) . ')';
        }

        return '(' . implode($operator === 'IN' ? ' OR ' : ' AND ', $vss) . ')';
    }
}
