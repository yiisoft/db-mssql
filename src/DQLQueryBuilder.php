<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Mssql\Builder\InConditionBuilder;
use Yiisoft\Db\Mssql\Builder\LikeConditionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

use function array_merge;
use function preg_match;

final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    public function buildOrderByAndLimit(string $sql, array $orderBy, $limit, $offset, array &$params = []): string
    {
        if (!$this->hasOffset($offset) && !$this->hasLimit($limit)) {
            $orderByString = $this->buildOrderBy($orderBy, $params);

            return $orderByString === '' ? $sql : $sql . $this->separator . $orderByString;
        }

        return $this->newBuildOrderByAndLimit($sql, $orderBy, $limit, $offset, $params);
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END';
    }

    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            InCondition::class => InConditionBuilder::class,
            LikeCondition::class => LikeConditionBuilder::class,
        ]);
    }

    /**
     * Builds the ORDER BY/LIMIT/OFFSET clauses for SQL SERVER 2012 or newer.
     *
     * @param string $sql The existing SQL (without ORDER BY/LIMIT/OFFSET).
     * @param array $orderBy The order by columns. See {@see Query::orderBy} for more details on how to specify
     * this parameter.
     * @param Expression|int|null $limit The limit number. See {@see Query::limit} for more details.
     * @param Expression|int|null $offset The offset number. See {@see Query::offset} for more details.
     * @param array $params The binding parameters to be populated.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The SQL completed with ORDER BY/LIMIT/OFFSET (if any).
     */
    protected function newBuildOrderByAndLimit(
        string $sql,
        array $orderBy,
        Expression|int|null $limit,
        Expression|int|null $offset,
        array &$params = []
    ): string {
        $orderByString = $this->buildOrderBy($orderBy, $params);

        if ($orderByString === '') {
            /** ORDER BY clause is required when FETCH and OFFSET are in the SQL */
            $orderByString = 'ORDER BY (SELECT NULL)';
        }

        $sql .= $this->separator . $orderByString;

        /**
         * {@see http://technet.microsoft.com/en-us/library/gg699618.aspx}
         */
        $offsetString = $this->hasOffset($offset) ? (string) $offset : '0';
        $sql .= $this->separator . 'OFFSET ' . $offsetString . ' ROWS';

        if ($this->hasLimit($limit)) {
            $sql .= $this->separator . 'FETCH NEXT ' . (string) $limit . ' ROWS ONLY';
        }

        return $sql;
    }

    /**
     * Extracts table alias if there is one or returns false
     *
     * @psalm-return string[]|bool
     */
    protected function extractAlias(string $table): array|bool
    {
        if (preg_match('/^\[.*]$/', $table)) {
            return false;
        }

        return parent::extractAlias($table);
    }
}
