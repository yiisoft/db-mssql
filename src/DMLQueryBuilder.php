<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use JsonException;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;

use function array_fill_keys;
use function array_intersect_key;
use function array_map;
use function implode;

/**
 * Implements a DML (Data Manipulation Language) SQL statements for MSSQL Server.
 */
final class DMLQueryBuilder extends AbstractDMLQueryBuilder
{
    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function insertReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        $primaryKeys = $this->schema->getTableSchema($table)?->getPrimaryKey() ?? [];

        return $this->insertReturning($table, $columns, $primaryKeys, $params);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resetSequence(string $table, int|string|null $value = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: '$table'.");
        }

        $sequenceName = $tableSchema->getSequenceName();

        if ($sequenceName === null) {
            throw new InvalidArgumentException("There is not sequence associated with table '$table'.'");
        }

        $tableName = $this->quoter->quoteTableName($table);

        if ($value === null) {
            return "DBCC CHECKIDENT ('$tableName', RESEED, 0) WITH NO_INFOMSGS;DBCC CHECKIDENT ('$tableName', RESEED)";
        }

        return "DBCC CHECKIDENT ('$tableName', RESEED, $value)";
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     */
    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string {
        return implode('', $this->prepareUpsertParts($table, $insertColumns, $updateColumns, $params)) . ';';
    }

    public function upsertReturning(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array|null $returnColumns = null,
        array &$params = [],
    ): string {
        [$uniqueNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $this->insertReturning($table, $insertColumns, $returnColumns, $params);
        }

        $tableSchema = $this->schema->getTableSchema($table);
        $returnColumns ??= $tableSchema?->getColumnNames();

        if (empty($returnColumns)) {
            return $this->upsert($table, $insertColumns, $updateColumns, $params);
        }

        /** @var TableSchema $tableSchema */
        [$declareSql, $outputSql, $selectSql] = $this->prepareReturningParts($tableSchema, $returnColumns);
        [$mergeSql, $updateSql, $insertSql] = $this->prepareUpsertParts($table, $insertColumns, $updateColumns, $params);

        return $declareSql
            . (!empty($insertSql) && empty($updateSql) ? 'DECLARE @temp int;' : '')
            . $mergeSql
            . (!empty($insertSql) && empty($updateSql) ? ' WHEN MATCHED THEN UPDATE SET @temp=1' : $updateSql)
            . $insertSql
            . $outputSql . ';'
            . $selectSql;
    }

    /**
     * @param string[] $returnColumns
     */
    private function insertReturning(
        string $table,
        array|QueryInterface $columns,
        array|null $returnColumns = null,
        array &$params = [],
    ): string {
        $tableSchema = $this->schema->getTableSchema($table);
        $returnColumns ??= $tableSchema?->getColumnNames();

        if (empty($returnColumns)) {
            return $this->insert($table, $columns, $params);
        }

        /** @var TableSchema $tableSchema */
        [$declareSql, $outputSql, $selectSql] = $this->prepareReturningParts($tableSchema, $returnColumns);
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);

        $quotedNames = array_map($this->quoter->quoteColumnName(...), $names);

        return $declareSql
            . 'INSERT INTO ' . $this->quoter->quoteTableName($table)
            . (!empty($quotedNames) ? ' (' . implode(', ', $quotedNames) . ')' : '')
            . $outputSql
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : ' ' . $values) . ';'
            . $selectSql;
    }

    /**
     * Prepares SQL parts for a returning query.
     *
     * @param TableSchema $tableSchema The table schema.
     * @param string[] $returnColumns The columns to return.
     *
     * @return string[] List of declare SQL, output SQL and select SQL.
     */
    private function prepareReturningParts(
        TableSchema $tableSchema,
        array $returnColumns,
    ): array {
        $createdCols = [];
        $insertedCols = [];
        $columns = array_intersect_key($tableSchema->getColumns(), array_fill_keys($returnColumns, true));

        $columnDefinitionBuilder = $this->queryBuilder->getColumnDefinitionBuilder();

        foreach ($columns as $name => $column) {
            if ($column->getDbType() === 'timestamp') {
                $dbType = $column->isNotNull() ? 'binary(8)' : 'varbinary(8)';
            } else {
                $dbType = $columnDefinitionBuilder->buildType($column);
            }

            $quotedName = $this->quoter->quoteColumnName($name);
            $createdCols[] = $quotedName . ' ' . $dbType . ($column->isNotNull() ? '' : ' NULL');
            $insertedCols[] = 'INSERTED.' . $quotedName;
        }

        return [
            'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE (' . implode(', ', $createdCols) . ');',
            ' OUTPUT ' . implode(',', $insertedCols) . ' INTO @temporary_inserted',
            'SELECT * FROM @temporary_inserted;',
        ];
    }

    /**
     * Prepares SQL parts for an upsert query.
     *
     * @psalm-param array<string, mixed>|QueryInterface $insertColumns
     *
     * @return string[] List of merge SQL, update SQL and insert SQL.
     */
    private function prepareUpsertParts(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): array {
        $constraints = [];

        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints
        );

        if (empty($uniqueNames)) {
            return [$this->insert($table, $insertColumns, $params), '', ''];
        }

        $onCondition = ['or'];
        $quotedTableName = $this->quoter->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $constraintCondition = ['and'];
            $columnNames = $constraint->columnNames;

            foreach ($columnNames as $name) {
                $quotedName = $this->quoter->quoteColumnName($name);
                $constraintCondition[] = "$quotedTableName.$quotedName=EXCLUDED.$quotedName";
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->queryBuilder->buildCondition($onCondition, $params);

        [, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);

        $quotedInsertNames = array_map($this->quoter->quoteColumnName(...), $insertNames);

        $mergeSql = 'MERGE ' . $quotedTableName . ' WITH (HOLDLOCK) USING ('
            . (!empty($placeholders) ? 'VALUES (' . implode(', ', $placeholders) . ')' : $values)
            . ') AS EXCLUDED (' . implode(', ', $quotedInsertNames) . ') ' . "ON ($on)";

        $insertValues = [];

        foreach ($quotedInsertNames as $quotedName) {
            $insertValues[] = 'EXCLUDED.' . $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $quotedInsertNames) . ')'
            . ' VALUES (' . implode(', ', $insertValues) . ')';

        if (empty($updateColumns) || $updateNames === []) {
            /** there are no columns to update */
            return [
                $mergeSql,
                '',
                ' WHEN NOT MATCHED THEN ' . $insertSql,
            ];
        }

        $updates = $this->prepareUpsertSets($table, $updateColumns, $updateNames, $params);

        return [
            $mergeSql,
            ' WHEN MATCHED THEN UPDATE SET ' . implode(', ', $updates),
            ' WHEN NOT MATCHED THEN ' . $insertSql,
        ];
    }
}
