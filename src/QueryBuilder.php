<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Mssql\Column\ColumnDefinitionBuilder;
use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;

/**
 * Implements the MSSQL Server specific query builder.
 */
final class QueryBuilder extends AbstractQueryBuilder
{
    protected const FALSE_VALUE = '0';

    protected const TRUE_VALUE = '1';

    public function __construct(ConnectionInterface $db)
    {
        $quoter = $db->getQuoter();
        $schema = $db->getSchema();

        parent::__construct(
            $db,
            new DDLQueryBuilder($this, $quoter, $schema),
            new DMLQueryBuilder($this, $quoter, $schema),
            new DQLQueryBuilder($this, $quoter),
            new ColumnDefinitionBuilder($this),
        );
    }

    protected function createSqlParser(string $sql): SqlParser
    {
        return new SqlParser($sql);
    }
}
