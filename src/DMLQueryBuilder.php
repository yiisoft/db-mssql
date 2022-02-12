<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use InvalidArgumentException;
use JsonException;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\DMLQueryBuilder as AbstractDMLQueryBuilder;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilderInterface;

final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
        parent::__construct($queryBuilder);
    }

    /**
     * @throws Exception|\Yiisoft\Db\Exception\InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function insert(string $table, Query|array $columns, array &$params = []): string
    {
        [$names, $placeholders, $values, $params] = $this->queryBuilder->prepareInsertValues($table, $columns, $params);

        $sql = 'INSERT INTO '
            . $this->queryBuilder->quoter()->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . ' OUTPUT INSERTED.* INTO @temporary_inserted'
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : $values);

        $schema = $this->queryBuilder->schema()->getTableSchema($table);
        $cols = [];

        foreach ($schema->getColumns() as $column) {
            $cols[] = $this->queryBuilder->quoter()->quoteColumnName($column->getName()) . ' '
                . $column->getDbType()
                . (in_array(
                    $column->getDbType(),
                    ['char', 'varchar', 'nchar', 'nvarchar', 'binary', 'varbinary']
                ) ? '(MAX)' : '')
                . ' ' . ($column->isAllowNull() ? 'NULL' : '');
        }

        return 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE (' . implode(', ', $cols) . ');'
            . $sql . ';SELECT * FROM @temporary_inserted';
    }

    public function resetSequence(string $tableName, $value = null): string
    {
        $table = $this->queryBuilder->schema()->getTableSchema($tableName);

        if ($table !== null && $table->getSequenceName() !== null) {
            $tableName = $this->queryBuilder->quoter()->quoteTableName($tableName);

            if ($value === null) {
                $pk = $table->getPrimaryKey();
                $key = $this->queryBuilder->quoter()->quoteColumnName(reset($pk));
                $value = "(SELECT COALESCE(MAX($key),0) FROM $tableName)+1";
            } else {
                $value = (int)$value;
            }

            return "DBCC CHECKIDENT ('$tableName', RESEED, $value)";
        }

        throw new InvalidArgumentException("There is not sequence associated with table '$tableName'.");
    }

    /**
     * @throws Exception|JsonException|\Yiisoft\Db\Exception\InvalidArgumentException|InvalidConfigException|NotSupportedException
     */
    public function upsert(
        string $table,
        Query|array $insertColumns,
        bool|array $updateColumns,
        array &$params = []
    ): string {
        /** @var Constraint[] $constraints */
        $constraints = [];
        [$uniqueNames, $insertNames, $updateNames] = $this->queryBuilder->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        $onCondition = ['or'];
        $quotedTableName = $this->queryBuilder->quoter()->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $constraintCondition = ['and'];

            foreach ($constraint->getColumnNames() as $name) {
                $quotedName = $this->queryBuilder->quoter()->quoteColumnName($name);
                $constraintCondition[] = "$quotedTableName.$quotedName=[EXCLUDED].$quotedName";
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->queryBuilder->buildCondition($onCondition, $params);
        [, $placeholders, $values, $params] = $this->queryBuilder->prepareInsertValues($table, $insertColumns, $params);
        $mergeSql = 'MERGE ' . $this->queryBuilder->quoter()->quoteTableName($table) . ' WITH (HOLDLOCK) '
            . 'USING (' . (!empty($placeholders)
            ? 'VALUES (' . implode(', ', $placeholders) . ')'
            : ltrim($values, ' ')) . ') AS [EXCLUDED] (' . implode(', ', $insertNames) . ') ' . "ON ($on)";
        $insertValues = [];

        foreach ($insertNames as $name) {
            $quotedName = $this->queryBuilder->quoter()->quoteColumnName($name);

            if (strrpos($quotedName, '.') === false) {
                $quotedName = '[EXCLUDED].' . $quotedName;
            }

            $insertValues[] = $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $insertNames) . ')' . ' VALUES (' . implode(', ', $insertValues) . ')';

        if ($updateColumns === false) {
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql;";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            foreach ($updateNames as $name) {
                $quotedName = $this->queryBuilder->quoter()->quoteColumnName($name);
                if (strrpos($quotedName, '.') === false) {
                    $quotedName = '[EXCLUDED].' . $quotedName;
                }

                $updateColumns[$name] = new Expression($quotedName);
            }
        }

        [$updates, $params] = $this->queryBuilder->prepareUpdateSets($table, $updateColumns, $params);
        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "$mergeSql WHEN MATCHED THEN $updateSql WHEN NOT MATCHED THEN $insertSql;";
    }
}
