<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use JsonException;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\AbstractDMLQueryBuilder;

use function array_fill_keys;
use function array_intersect_key;
use function implode;
use function rtrim;

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
    public function insertWithReturningPks(string $table, array|QueryInterface $columns, array &$params = []): string
    {
        $tableSchema = $this->schema->getTableSchema($table);
        $primaryKeys = $tableSchema?->getPrimaryKey();

        if (empty($primaryKeys)) {
            return $this->insert($table, $columns, $params);
        }

        /** @var TableSchema $tableSchema */
        [$declareSql, $outputSql, $selectSql] = $this->prepareReturningParts($tableSchema, $primaryKeys);
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);

        return $declareSql
            . 'INSERT INTO ' . $this->quoter->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . $outputSql
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : ' ' . $values) . ';'
            . $selectSql;
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
        /** @psalm-var Constraint[] $constraints */
        $constraints = [];

        [$uniqueNames, $insertNames, $updateNames] = $this->prepareUpsertColumns(
            $table,
            $insertColumns,
            $updateColumns,
            $constraints
        );

        if (empty($uniqueNames)) {
            return $this->insert($table, $insertColumns, $params);
        }

        $onCondition = ['or'];
        $quotedTableName = $this->quoter->quoteTableName($table);

        foreach ($constraints as $constraint) {
            $constraintCondition = ['and'];
            $columnNames = (array) $constraint->getColumnNames();

            /** @psalm-var string[] $columnNames */
            foreach ($columnNames as $name) {
                $quotedName = $this->quoter->quoteColumnName($name);
                $constraintCondition[] = "$quotedTableName.$quotedName=[EXCLUDED].$quotedName";
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->queryBuilder->buildCondition($onCondition, $params);

        [, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);

        $mergeSql = 'MERGE ' . $quotedTableName . ' WITH (HOLDLOCK) USING ('
            . (!empty($placeholders) ? 'VALUES (' . implode(', ', $placeholders) . ')' : $values)
            . ') AS [EXCLUDED] (' . implode(', ', $insertNames) . ') ' . "ON ($on)";

        $insertValues = [];

        foreach ($insertNames as $quotedName) {
            $insertValues[] = '[EXCLUDED].' . $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $insertNames) . ') VALUES (' . implode(', ', $insertValues) . ')';

        if ($updateColumns === false || $updateNames === []) {
            /** there are no columns to update */
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql;";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $quotedName) {
                $updateColumns[$quotedName] = new Expression('[EXCLUDED].' . $quotedName);
            }
        }

        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);

        return "$mergeSql WHEN MATCHED THEN UPDATE SET " . implode(', ', $updates)
            . " WHEN NOT MATCHED THEN $insertSql;";
    }

    public function upsertWithReturningPks(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array &$params = [],
    ): string {
        [$uniqueNames] = $this->prepareUpsertColumns($table, $insertColumns, $updateColumns);

        if (empty($uniqueNames)) {
            return $this->insertWithReturningPks($table, $insertColumns, $params);
        }

        $upsertSql = $this->upsert($table, $insertColumns, $updateColumns, $params);

        $tableSchema = $this->schema->getTableSchema($table);
        $primaryKeys = $tableSchema?->getPrimaryKey();

        if (empty($primaryKeys)) {
            return $upsertSql;
        }

        /** @var TableSchema $tableSchema */
        [$declareSql, $outputSql, $selectSql] = $this->prepareReturningParts($tableSchema, $primaryKeys);

        return $declareSql
            . rtrim($upsertSql, ';')
            . $outputSql . ';'
            . $selectSql;
    }

    /**
     * Prepares SQL parts for a returning query.
     *
     * @param TableSchema $tableSchema The table schema.
     * @param string[] $returnColumns The columns to return.
     *
     * @return string[] List of Declare SQL, output SQL and select SQL.
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
}
