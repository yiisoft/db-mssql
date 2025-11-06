<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Function\Greatest;
use Yiisoft\Db\Expression\Function\Least;
use Yiisoft\Db\Expression\Function\Length;
use Yiisoft\Db\Expression\Function\Longest;
use Yiisoft\Db\Expression\Function\Shortest;
use Yiisoft\Db\Mssql\Builder\ArrayMergeBuilder;
use Yiisoft\Db\Mssql\Builder\GreatestBuilder;
use Yiisoft\Db\Mssql\Builder\InBuilder;
use Yiisoft\Db\Mssql\Builder\LeastBuilder;
use Yiisoft\Db\Mssql\Builder\LengthBuilder;
use Yiisoft\Db\Mssql\Builder\LikeBuilder;
use Yiisoft\Db\Mssql\Builder\LongestBuilder;
use Yiisoft\Db\Mssql\Builder\ShortestBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\WithQuery;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\In;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\NotIn;
use Yiisoft\Db\QueryBuilder\Condition\NotLike;

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
        array &$params = [],
    ): string {
        if (empty($offset) && $limit === null) {
            $orderByString = $this->buildOrderBy($orderBy, $params);

            return $orderByString === '' ? $sql : $sql . $this->separator . $orderByString;
        }

        return $this->newBuildOrderByAndLimit($sql, $orderBy, $limit, $offset, $params);
    }

    public function selectExists(string $rawSql): string
    {
        return 'SELECT CASE WHEN EXISTS(' . $rawSql . ') THEN 1 ELSE 0 END AS [0]';
    }

    public function buildWithQueries(array $withQueries, array &$params): string
    {
        $withQueries = array_map(
            static fn(WithQuery $withQuery) => new WithQuery(
                $withQuery->query,
                $withQuery->alias,
                false,
            ),
            $withQueries,
        );

        return parent::buildWithQueries($withQueries, $params);
    }

    protected function defaultExpressionBuilders(): array
    {
        return [
            ...parent::defaultExpressionBuilders(),
            In::class => InBuilder::class,
            NotIn::class => InBuilder::class,
            Like::class => LikeBuilder::class,
            NotLike::class => LikeBuilder::class,
            Length::class => LengthBuilder::class,
            ArrayMerge::class => ArrayMergeBuilder::class,
            Greatest::class => GreatestBuilder::class,
            Least::class => LeastBuilder::class,
            Longest::class => LongestBuilder::class,
            Shortest::class => ShortestBuilder::class,
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
        array &$params = [],
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
}
