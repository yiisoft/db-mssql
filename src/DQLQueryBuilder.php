<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Mssql\Builder\ExpressionBuilder;
use Yiisoft\Db\Mssql\Builder\InConditionBuilder;
use Yiisoft\Db\Mssql\Builder\LikeConditionBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

use function preg_match;

/**
 * Implements a DQL (Data Query Language) SQL statements for MSSQL Server.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    public function buildOrderByAndLimit(
        string $sql,
        array $orderBy,
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
        array &$params = []
    ): string {
        if (empty($offset) && $limit === null) {
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
        return [
            ...parent::defaultExpressionBuilders(),
            InCondition::class => InConditionBuilder::class,
            LikeCondition::class => LikeConditionBuilder::class,
            Expression::class => ExpressionBuilder::class,
        ];
    }

    /**
     * Builds the `ORDER BY`/`LIMIT`/`OFFSET` clauses for SQL SERVER 2012 or newer.
     *
     * @param string $sql The existing SQL (without `ORDER BY`/`LIMIT`/`OFFSET`).
     * @param array $orderBy The order by columns. See {@see Query::orderBy} for more details on how to specify
     * this parameter.
     * @param ExpressionInterface|int|null $limit The limit number. See {@see Query::limit} for more details.
     * @param ExpressionInterface|int|null $offset The offset number. See {@see Query::offset} for more details.
     * @param array $params The binding parameters to populate.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string The SQL completed with `ORDER BY`/`LIMIT`/`OFFSET` (if any).
     */
    protected function newBuildOrderByAndLimit(
        string $sql,
        array $orderBy,
        ExpressionInterface|int|null $limit,
        ExpressionInterface|int|null $offset,
        array &$params = []
    ): string {
        $orderByString = $this->buildOrderBy($orderBy, $params);

        if ($orderByString === '') {
            /** `ORDER BY` clause is required when `FETCH` and `OFFSET` are in the SQL */
            $orderByString = 'ORDER BY (SELECT NULL)';
        }

        $sql .= $this->separator . $orderByString;

        /**
         * @link https://technet.microsoft.com/en-us/library/gg699618.aspx
         */
        $offsetString = !empty($offset)
            ? ($offset instanceof ExpressionInterface ? $this->buildExpression($offset) : (string) $offset)
            : '0';
        $sql .= $this->separator . 'OFFSET ' . $offsetString . ' ROWS';

        if ($limit !== null) {
            $sql .= $this->separator . 'FETCH NEXT '
                . ($limit instanceof ExpressionInterface ? $this->buildExpression($limit) : (string) $limit)
                . ' ROWS ONLY';
        }

        return $sql;
    }

    /**
     * Extracts table alias if there is one or returns `false`.
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

    public function buildWithQueries(array $withs, array &$params): string
    {
        /** @psalm-var array{query:string|Query, alias:ExpressionInterface|string, recursive:bool}[] $withs */
        foreach ($withs as &$with) {
            $with['recursive'] = false;
        }

        return parent::buildWithQueries($withs, $params);
    }
}
