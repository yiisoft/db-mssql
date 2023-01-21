<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Exception;
use Throwable;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\ColumnSchemaBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_diff;

final class DDLQueryBuilder extends AbstractDDLQueryBuilder
{
    public function __construct(
        private QueryBuilderInterface $queryBuilder,
        private QuoterInterface $quoter,
        private SchemaInterface $schema
    ) {
        parent::__construct($queryBuilder, $quoter, $schema);
    }

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
    public function addDefaultValue(string $name, string $table, string $column, mixed $value): string
    {
        return 'ALTER TABLE '
            . $this->quoter->quoteTableName($table)
            . ' ADD CONSTRAINT '
            . $this->quoter->quoteColumnName($name)
            . ' DEFAULT ' . ($value === null ? 'NULL' : (string) $this->quoter->quoteValue($value))
            . ' FOR ' . $this->quoter->quoteColumnName($column);
    }

    public function alterColumn(string $table, string $column, ColumnSchemaBuilderInterface|string $type): string
    {
        $sqlAfter = [$this->dropConstraintsForColumn($table, $column, 'D')];

        $columnName = $this->quoter->quoteColumnName($column);
        $tableName = $this->quoter->quoteTableName($table);
        $constraintBase = preg_replace('/[^a-z0-9_]/i', '', $table . '_' . $column);

        if ($type instanceof ColumnSchemaBuilderInterface) {
            $type->setFormat('{type}{length}{notnull}{append}');

            /** @psalm-var mixed $defaultValue */
            $defaultValue = $type->getDefault();
            if ($defaultValue !== null || $type->isNotNull() === false) {
                $sqlAfter[] = $this->addDefaultValue(
                    "DF_{$constraintBase}",
                    $table,
                    $column,
                    $defaultValue
                );
            }

            $checkValue = $type->getCheck();
            if ($checkValue !== null) {
                $sqlAfter[] = "ALTER TABLE {$tableName} ADD CONSTRAINT " .
                    $this->quoter->quoteColumnName("CK_{$constraintBase}") .
                    ' CHECK (' . ($defaultValue instanceof Expression ? $checkValue : new Expression($checkValue)) . ')';
            }

            if ($type->isUnique()) {
                $sqlAfter[] = "ALTER TABLE {$tableName} ADD CONSTRAINT " . $this->quoter->quoteColumnName("UQ_{$constraintBase}") . " UNIQUE ({$columnName})";
            }
        }

        return 'ALTER TABLE ' . $tableName
            . ' ALTER COLUMN '
            . $columnName . ' '
            . $this->queryBuilder->getColumnType($type) . "\n"
            . implode("\n", $sqlAfter);
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     * @throws \Yiisoft\Db\Exception\Exception
     */
    public function checkIntegrity(string $schema = '', string $table = '', bool $check = true): string
    {
        $enable = $check ? 'CHECK' : 'NOCHECK';

        /** @var Schema */
        $schemaInstance = $this->schema;
        $defaultSchema = $schema ?: $schemaInstance->getDefaultSchema() ?? '';
        /** @psalm-var string[] */
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

    public function dropDefaultValue(string $name, string $table): string
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
     * Builds a SQL command for adding or updating a comment to a table or a column. The command built will check if a
     * comment already exists. If so, it will be updated, otherwise, it will be added.
     *
     * @param string $comment The text of the comment to be added. The comment will be properly quoted by the method.
     * @param string $table The table to be commented or whose column is to be commented. The table name will be
     * properly quoted by the method.
     * @param string|null $column Optional, the name of the column to be commented. If empty, the command will add the
     * comment to the table instead. The column name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidArgumentException If the table does not exist.
     *
     * @return string The SQL statement for adding a comment.
     */
    private function buildAddCommentSql(string $comment, string $table, string $column = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: $table");
        }

        $schemaName = $tableSchema->getSchemaName()
            ? "N'" . (string) $tableSchema->getSchemaName() . "'" : 'SCHEMA_NAME()';
        $tableName = 'N' . (string) $this->quoter->quoteValue($tableSchema->getName());
        $columnName = $column ? 'N' . (string) $this->quoter->quoteValue($column) : null;
        $comment = 'N' . (string) $this->quoter->quoteValue($comment);
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
     * Builds a SQL command for removing a comment from a table or a column. The command built will check if a comment
     * already exists before trying to perform the removal.
     *
     * @param string $table The table that will have the comment removed or whose column will have the comment removed.
     * The table name will be properly quoted by the method.
     * @param string|null $column Optional, the name of the column whose comment will be removed. If empty, the command
     * will remove the comment from the table instead. The column name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidArgumentException If the table does not exist.
     *
     * @return string The SQL statement for removing the comment.
     */
    private function buildRemoveCommentSql(string $table, string $column = null): string
    {
        $tableSchema = $this->schema->getTableSchema($table);

        if ($tableSchema === null) {
            throw new InvalidArgumentException("Table not found: $table");
        }

        $schemaName = $tableSchema->getSchemaName()
            ? "N'" . (string) $tableSchema->getSchemaName() . "'" : 'SCHEMA_NAME()';
        $tableName = 'N' . (string) $this->quoter->quoteValue($tableSchema->getName());
        $columnName = $column ? 'N' . (string) $this->quoter->quoteValue($column) : null;

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
     * Builds a SQL statement for dropping constraints for column of table.
     *
     * @param string $table the table whose constraint is to be dropped. The name will be properly quoted by the method.
     * @param string $column the column whose constraint is to be dropped. The name will be properly quoted by the method.
     * @param string $type type of constraint, leave empty for all type of constraints(for example: D - default, 'UQ' - unique, 'C' - check)
     *
     * @return string the DROP CONSTRAINTS SQL
     *
     * @see https://docs.microsoft.com/sql/relational-databases/system-catalog-views/sys-objects-transact-sql
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
