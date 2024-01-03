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

use function array_flip;
use function array_intersect_key;
use function implode;
use function in_array;

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
    public function insertWithReturningPks(string $table, QueryInterface|array $columns, array &$params = []): string
    {
        $tableSchema = $this->schema->getTableSchema($table);
        $primaryKeys = $tableSchema?->getPrimaryKey();

        if (empty($primaryKeys)) {
            return $this->insert($table, $columns, $params);
        }

        $createdCols = [];
        $insertedCols = [];
        $returnColumns = array_intersect_key($tableSchema?->getColumns() ?? [], array_flip($primaryKeys));

        foreach ($returnColumns as $returnColumn) {
            $dbType = $returnColumn->getDbType();

            if (in_array($dbType, ['char', 'varchar', 'nchar', 'nvarchar', 'binary', 'varbinary'], true)) {
                $dbType .= '(MAX)';
            } elseif ($dbType === 'timestamp') {
                $dbType = $returnColumn->isAllowNull() ? 'varbinary(8)' : 'binary(8)';
            }

            $quotedName = $this->quoter->quoteColumnName($returnColumn->getName());
            $createdCols[] = $quotedName . ' ' . (string) $dbType . ' ' . ($returnColumn->isAllowNull() ? 'NULL' : '');
            $insertedCols[] = 'INSERTED.' . $quotedName;
        }

        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);

        $sql = 'INSERT INTO ' . $this->quoter->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . ' OUTPUT ' . implode(',', $insertedCols) . ' INTO @temporary_inserted'
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : ' ' . $values);

        return 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE (' . implode(', ', $createdCols) . ');'
            . $sql . ';SELECT * FROM @temporary_inserted;';
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resetSequence(string $table, int|string $value = null): string
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
        QueryInterface|array $insertColumns,
        bool|array $updateColumns,
        array &$params = []
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
}
