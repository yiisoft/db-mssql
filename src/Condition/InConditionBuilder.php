<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Condition;

use Traversable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\Conditions\InConditionBuilder as AbstractInConditionBuilder;
use Yiisoft\Db\Query\Query;

use function implode;
use function is_array;
use function strpos;

final class InConditionBuilder extends AbstractInConditionBuilder
{
    /**
     * Builds SQL for IN condition.
     *
     * @param string $operator
     * @param array|string $columns
     * @param Query $values
     * @param array $params
     *
     * @return string SQL
     *
     * @throws NotSupportedException
     */
    protected function buildSubqueryInCondition(string $operator, $columns, Query $values, array &$params = []): string
    {
        if (is_array($columns)) {
            throw new NotSupportedException(__METHOD__ . ' is not supported by MSSQL.');
        }

        return parent::buildSubqueryInCondition($operator, $columns, $values, $params);
    }

    /**
     * Builds SQL for IN condition.
     *
     * @param string $operator
     * @param array|Traversable $columns
     * @param array $values
     * @param array $params
     *
     * @return string SQL
     *
     * @throws NotSupportedException
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function buildCompositeInCondition(?string $operator, $columns, $values, array &$params = []): string
    {
        $quotedColumns = [];
        foreach ($columns as $i => $column) {
            $quotedColumns[$i] = strpos($column, '(') === false
                ? $this->queryBuilder->getDb()->quoteColumnName($column) : $column;
        }

        $vss = [];
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
