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
use Yiisoft\Db\Schema\SchemaInterface;

use function implode;
use function in_array;
use function ltrim;
use function strrpos;
use function is_array;

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
        /**
         * @psalm-var string[] $names
         * @psalm-var string[] $placeholders
         */
        [$names, $placeholders, $values, $params] = $this->prepareInsertValues($table, $columns, $params);


        $createdCols = $insertedCols = [];
        $tableSchema = $this->schema->getTableSchema($table);
        $returnColumns = $tableSchema?->getColumns() ?? [];
        foreach ($returnColumns as $returnColumn) {
            if ($returnColumn->isComputed()) {
                continue;
            }

            $dbType = $returnColumn->getDbType();

            if (in_array($dbType, ['char', 'varchar', 'nchar', 'nvarchar', 'binary', 'varbinary'], true)) {
                $dbType .= '(MAX)';
            }

            if ($returnColumn->getDbType() === SchemaInterface::TYPE_TIMESTAMP) {
                $dbType = $returnColumn->isAllowNull() ? 'varbinary(8)' : 'binary(8)';
            }

            $quotedName = $this->quoter->quoteColumnName($returnColumn->getName());
            $createdCols[] = $quotedName . ' ' . (string) $dbType . ' ' . ($returnColumn->isAllowNull() ? 'NULL' : '');
            $insertedCols[] = 'INSERTED.' . $quotedName;
        }

        $sql = 'INSERT INTO '
            . $this->quoter->quoteTableName($table)
            . (!empty($names) ? ' (' . implode(', ', $names) . ')' : '')
            . ' OUTPUT ' . implode(',', $insertedCols) . ' INTO @temporary_inserted'
            . (!empty($placeholders) ? ' VALUES (' . implode(', ', $placeholders) . ')' : (string) $values);


        return 'SET NOCOUNT ON;DECLARE @temporary_inserted TABLE (' . implode(', ', $createdCols) . ');'
            . $sql . ';SELECT * FROM @temporary_inserted;';
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resetSequence(string $tableName, int|string $value = null): string
    {
        $table = $this->schema->getTableSchema($tableName);

        if ($table === null) {
            throw new InvalidArgumentException("Table not found: '$tableName'.");
        }

        $sequenceName = $table->getSequenceName();

        if ($sequenceName === null) {
            throw new InvalidArgumentException("There is not sequence associated with table '$tableName'.'");
        }

        $tableName = $this->quoter->quoteTableName($tableName);

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

        /** @psalm-var string[] $insertNames */
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

            $columnNames = $constraint->getColumnNames() ?? [];

            if (is_array($columnNames)) {
                /** @psalm-var string[] $columnNames */
                foreach ($columnNames as $name) {
                    $quotedName = $this->quoter->quoteColumnName($name);
                    $constraintCondition[] = "$quotedTableName.$quotedName=[EXCLUDED].$quotedName";
                }
            }

            $onCondition[] = $constraintCondition;
        }

        $on = $this->queryBuilder->buildCondition($onCondition, $params);

        /** @psalm-var string[] $placeholders */
        [, $placeholders, $values, $params] = $this->prepareInsertValues($table, $insertColumns, $params);
        $mergeSql = 'MERGE ' . $this->quoter->quoteTableName($table) . ' WITH (HOLDLOCK) '
            . 'USING (' . (!empty($placeholders)
            ? 'VALUES (' . implode(', ', $placeholders) . ')'
            : ltrim((string) $values, ' ')) . ') AS [EXCLUDED] (' . implode(', ', $insertNames) . ') ' . "ON ($on)";
        $insertValues = [];

        foreach ($insertNames as $name) {
            $quotedName = $this->quoter->quoteColumnName($name);

            if (strrpos($quotedName, '.') === false) {
                $quotedName = '[EXCLUDED].' . $quotedName;
            }

            $insertValues[] = $quotedName;
        }

        $insertSql = 'INSERT (' . implode(', ', $insertNames) . ')' . ' VALUES (' . implode(', ', $insertValues) . ')';

        if ($updateNames === []) {
            /** there are no columns to update */
            $updateColumns = false;
        }

        if ($updateColumns === false) {
            return "$mergeSql WHEN NOT MATCHED THEN $insertSql;";
        }

        if ($updateColumns === true) {
            $updateColumns = [];

            /** @psalm-var string[] $updateNames */
            foreach ($updateNames as $name) {
                $quotedName = $this->quoter->quoteColumnName($name);
                if (strrpos($quotedName, '.') === false) {
                    $quotedName = '[EXCLUDED].' . $quotedName;
                }

                $updateColumns[$name] = new Expression($quotedName);
            }
        }

        /**
         * @var array $params
         *
         * @psalm-var string[] $updates
         * @psalm-var array<string, ExpressionInterface|string> $updateColumns
         */
        [$updates, $params] = $this->prepareUpdateSets($table, $updateColumns, $params);
        $updateSql = 'UPDATE SET ' . implode(', ', $updates);

        return "$mergeSql WHEN MATCHED THEN $updateSql WHEN NOT MATCHED THEN $insertSql;";
    }
}
