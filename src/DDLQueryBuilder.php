<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Exception;
use Throwable;
use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_diff;
use function implode;
use function preg_replace;
use function is_string;

/**
 * Implements a (Data Definition Language) SQL statements for MSSQL Server.
 */
final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    /**
     * @throws InvalidArgumentException
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): string
    {
        return $this->buildAddCommentSql($comment, $table, $column);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function addCommentOnTable(string $table, string $comment): string
    {
        return $this->buildAddCommentSql($comment, $table);
    }

    /**
     * @throws Exception
     */
    public function addDefaultValue(string $table, string $name, string $column, mixed $value): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT '
            . $this->quoter->quoteColumnName($name)
            . ' DEFAULT ' . $this->queryBuilder->prepareValue($value)
            . ' FOR ' . $this->quoter->quoteColumnName($column);
    }

    /**
     * @throws Exception
     */
    public function alterColumn(string $table, string $column, ColumnInterface|string $type): string
    {
        $columnName = $this->quoter->quoteColumnName($column);
        $tableName = $this->quoter->quoteTableName($table);
        $constraintBase = preg_replace('/\W/', '', $table . '_' . $column);

        if (is_string($type)) {
            $type = $this->queryBuilder->getColumnFactory()->fromDefinition($type);
        }

        $columnDefinitionBuilder = $this->queryBuilder->getColumnDefinitionBuilder();
        $statements = [
            $this->dropConstraintsForColumn($table, $column, 'D'),
            "ALTER TABLE $tableName ALTER COLUMN $columnName " . $columnDefinitionBuilder->buildAlter($type),
        ];

        if ($type->hasDefaultValue()) {
            $defaultValue = $type->dbTypecast($type->getDefaultValue());
            $statements[] = $this->addDefaultValue($table, "DF_$constraintBase", $column, $defaultValue);
        }

        $checkValue = $type->getCheck();
        if (!empty($checkValue)) {
            $statements[] = "ALTER TABLE $tableName ADD CONSTRAINT "
                . $this->quoter->quoteColumnName("CK_$constraintBase")
                . " CHECK ($checkValue)";
        }

        if ($type->isUnique()) {
            $statements[] = "ALTER TABLE $tableName ADD CONSTRAINT "
                . $this->quoter->quoteColumnName("UQ_$constraintBase")
                . " UNIQUE ($columnName)";
        }

        return implode("\n", $statements);
    }

    /**
     * @throws NotSupportedException
     * @throws Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        $enable = $check ? 'CHECK' : 'NOCHECK';

        /** @psalm-var Schema $schemaInstance */
        $schemaInstance = $this->schema;
        $defaultSchema = $schema ?: $schemaInstance->getDefaultSchema();
        /** @psalm-var string[] $tableNames */
        $tableNames = $schemaInstance->getTableSchema($table)
             ? [$table] : $schemaInstance->getTableNames($defaultSchema);
        $viewNames = $schemaInstance->getViewNames($defaultSchema);
        $tableNames = array_diff($tableNames, $viewNames);
        $command = '';

        foreach ($tableNames as $tableName) {
            $tableName = $this->quoter->quoteTableName("$defaultSchema.$tableName");
            $command .= "ALTER TABLE $tableName $enable CONSTRAINT ALL; ";
        }

        return $command;
    }

    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        ?string $indexType = null,
        ?string $indexMethod = null,
    ): string {
        return 'CREATE ' . (!empty($indexType) ? $indexType . ' ' : '') . 'INDEX '
            . $this->quoter->quoteTableName($name) . ' ON '
            . $this->quoter->quoteTableName($table)
            . (!empty($columns) ? ' (' . $this->queryBuilder->buildColumns($columns) . ')' : '')
            . (!empty($indexMethod) ? " USING $indexMethod" : '');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function dropCommentFromColumn(string $table, string $column): string
    {
        return $this->buildRemoveCommentSql($table, $column);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function dropCommentFromTable(string $table): string
    {
        return $this->buildRemoveCommentSql($table);
    }

    public function dropDefaultValue(string $table, string $name): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' DROP CONSTRAINT '
            . $this->quoter->quoteColumnName($name);
    }

    public function dropColumn(string $table, string $column): string
    {
        return $this->dropConstraintsForColumn($table, $column)
            . "\nALTER TABLE "
            . $this->quoter->quoteTableName($table)
            . ' DROP COLUMN '
            . $this->quoter->quoteColumnName($column);
    }

    public function renameTable(string $oldName, string $newName): string
    {
        return 'sp_rename '
            . $this->quoter->quoteTableName($oldName) . ', '
            . $this->quoter->quoteTableName($newName);
    }

    public function renameColumn(string $table, string $oldName, string $newName): string
    {
        return 'sp_rename '
            . "'" . $this->quoter->quoteTableName($table) . '.' . $this->quoter->quoteColumnName($oldName) . "'" . ', '
            . $this->quoter->quoteColumnName($newName) . ', '
            . "'COLUMN'";
    }

    /**
     * @throws NotSupportedException MSSQL doesn't support cascade drop table.
     */
    public function dropTable(string $table, bool $ifExists = false, bool $cascade = false): string
    {
        if ($cascade) {
            throw new NotSupportedException('MSSQL doesn\'t support cascade drop table.');
        }
        return parent::dropTable($table, $ifExists, false);
    }

    /**
     * Builds an SQL command for adding or updating a comment to a table or a column.
     *
     * The command built will check if a comment already exists. If so, it will be updated, otherwise, it will be added.
     *
     * @param string $comment The text of the comment to add.
     * @param string $table The table to comment or whose column is to comment.
     * @param string|null $column Optional, the name of the column to comment.
     * If empty, the command will add the comment to the table instead.
     *
     * @throws Exception
     * @throws InvalidArgumentException If the table doesn't exist.
     *
     * @return string The SQL statement for adding a comment.
     *
     * Note: The method will quote the `comment`, `table`, `column` parameter before using it in the generated SQL.
     */
    private function buildAddCommentSql(string $comment, string $table, ?string $column = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: $table");
        }

        $schemaName = $tableSchema->getSchemaName() ?: $this->schema->getDefaultSchema();
        $schemaName = "N'$schemaName'";
        $tableName = 'N' . $this->quoter->quoteValue($tableSchema->getName());
        $columnName = $column ? 'N' . $this->quoter->quoteValue($column) : null;
        $comment = 'N' . $this->quoter->quoteValue($comment);
        $functionParams = "
            @name = N'MS_description',
            @value = $comment,
            @level0type = N'SCHEMA', @level0name = $schemaName,
            @level1type = N'TABLE', @level1name = $tableName"
            . ($column ? ", @level2type = N'COLUMN', @level2name = $columnName" : '') . ';';

        return "
            IF NOT EXISTS (
                    SELECT 1
                    FROM fn_listextendedproperty (
                        N'MS_description',
                        'SCHEMA', $schemaName,
                        'TABLE', $tableName,
                        " . ($column ? "'COLUMN', $columnName " : ' DEFAULT, DEFAULT ') . "
                    )
            )
                EXEC sys.sp_addextendedproperty $functionParams
            ELSE
                EXEC sys.sp_updateextendedproperty $functionParams
        ";
    }

    /**
     * Builds an SQL command for removing a comment from a table or a column. The command built will check if a comment
     * already exists before trying to perform the removal.
     *
     * @param string $table The table that will have the comment removed or whose column will have the comment removed.
     * @param string|null $column Optional, the name of the column whose comment will be removed. If empty, the command
     * will remove the comment from the table instead.
     *
     * @throws Exception
     * @throws InvalidArgumentException If the table doesn't exist.
     *
     * @return string The SQL statement for removing the comment.
     *
     * Note: The method will quote the `table`, `column` parameter before using it in the generated SQL.
     */
    private function buildRemoveCommentSql(string $table, ?string $column = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: $table");
        }

        $schemaName = $tableSchema->getSchemaName() ?: $this->schema->getDefaultSchema();
        $schemaName = "N'$schemaName'";
        $tableName = 'N' . $this->quoter->quoteValue($tableSchema->getName());
        $columnName = $column ? 'N' . $this->quoter->quoteValue($column) : null;

        return "
            IF EXISTS (
                    SELECT 1
                    FROM fn_listextendedproperty (
                        N'MS_description',
                        'SCHEMA', $schemaName,
                        'TABLE', $tableName,
                        " . ($column ? "'COLUMN', $columnName " : ' DEFAULT, DEFAULT ') . "
                    )
            )
                EXEC sys.sp_dropextendedproperty
                    @name = N'MS_description',
                    @level0type = N'SCHEMA', @level0name = $schemaName,
                    @level1type = N'TABLE', @level1name = $tableName"
                    . ($column ? ", @level2type = N'COLUMN', @level2name = $columnName" : '') . ';';
    }

    /**
     * Builds an SQL statement for dropping constraints for column of table.
     *
     * @param string $table The table whose constraint is to be dropped.
     * @param string $column the column whose constraint is to be dropped.
     * @param string $type type of constraint, leave empty for all types of constraints(for example: D - default,
     * 'UQ' - unique, 'C' - check)
     *
     * @return string the DROP CONSTRAINTS SQL
     *
     * @link https://docs.microsoft.com/sql/relational-databases/system-catalog-views/sys-objects-transact-sql
     *
     * Note: The method will quote the `table`, `column` parameter before using it in the generated SQL.
     */
    private function dropConstraintsForColumn(string $table, string $column, string $type = ''): string
    {
        return "DECLARE @tableName VARCHAR(MAX) = '" . $this->quoter->quoteTableName($table) . "'
DECLARE @columnName VARCHAR(MAX) = '{$column}'
WHILE 1=1 BEGIN
    DECLARE @constraintName NVARCHAR(128)
    SET @constraintName = (SELECT TOP 1 OBJECT_NAME(cons.[object_id])
        FROM (
            SELECT sc.[constid] object_id
            FROM [sys].[sysconstraints] sc
            JOIN [sys].[columns] c ON c.[object_id]=sc.[id] AND c.[column_id]=sc.[colid] AND c.[name]=@columnName
            WHERE sc.[id] = OBJECT_ID(@tableName)
            UNION
            SELECT object_id(i.[name]) FROM [sys].[indexes] i
            JOIN [sys].[columns] c ON c.[object_id]=i.[object_id] AND c.[name]=@columnName
            JOIN [sys].[index_columns] ic ON ic.[object_id]=i.[object_id] AND i.[index_id]=ic.[index_id] AND c.[column_id]=ic.[column_id]
            WHERE i.[is_unique_constraint]=1 and i.[object_id]=OBJECT_ID(@tableName)
        ) cons
        JOIN [sys].[objects] so ON so.[object_id]=cons.[object_id]
        " . (!empty($type) ? " WHERE so.[type]='{$type}'" : '') . ")
    IF @constraintName IS NULL BREAK
    EXEC (N'ALTER TABLE ' + @tableName + ' DROP CONSTRAINT [' + @constraintName + ']')
END";
    }
}
