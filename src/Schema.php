<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Throwable;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Helper\ArrayHelper;
use Yiisoft\Db\Schema\AbstractSchema;
use Yiisoft\Db\Schema\ColumnSchemaBuilderInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function explode;
use function is_array;
use function md5;
use function preg_match;
use function serialize;
use function str_replace;
use function strcasecmp;
use function stripos;

/**
 * Implements the MSSQL Server specific schema, supporting MSSQL Server 2017 and above.
 *
 * @psalm-type ColumnArray = array{
 *   column_name: string,
 *   is_nullable: string,
 *   data_type: string,
 *   column_default: mixed,
 *   is_identity: string,
 *   is_computed: string,
 *   comment: null|string
 * }
 *
 * @psalm-type ConstraintArray = array<
 *   array-key,
 *   array {
 *     name: string,
 *     column_name: string,
 *     type: string,
 *     foreign_table_schema: string|null,
 *     foreign_table_name: string|null,
 *     foreign_column_name: string|null,
 *     on_update: string,
 *     on_delete: string,
 *     check_expr: string,
 *     default_expr: string
 *   }
 * >
 */
final class Schema extends AbstractSchema
{
    /**
     * @var string|null The default schema used for the current session.
     */
    protected string|null $defaultSchema = 'dbo';

    /**
     * @var array Mapping from physical column types (keys) to abstract column types (values).
     *
     * @psalm-var string[]
     */
    private array $typeMap = [
        /** Exact numbers */
        'bigint' => self::TYPE_BIGINT,
        'numeric' => self::TYPE_DECIMAL,
        'bit' => self::TYPE_SMALLINT,
        'smallint' => self::TYPE_SMALLINT,
        'decimal' => self::TYPE_DECIMAL,
        'smallmoney' => self::TYPE_MONEY,
        'int' => self::TYPE_INTEGER,
        'tinyint' => self::TYPE_TINYINT,
        'money' => self::TYPE_MONEY,

        /** Approximate numbers */
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,

        /** Date and time */
        'date' => self::TYPE_DATE,
        'datetimeoffset' => self::TYPE_DATETIME,
        'datetime2' => self::TYPE_DATETIME,
        'smalldatetime' => self::TYPE_DATETIME,
        'datetime' => self::TYPE_DATETIME,
        'time' => self::TYPE_TIME,

        /** Character strings */
        'char' => self::TYPE_CHAR,
        'varchar' => self::TYPE_STRING,
        'text' => self::TYPE_TEXT,

        /** Unicode character strings */
        'nchar' => self::TYPE_CHAR,
        'nvarchar' => self::TYPE_STRING,
        'ntext' => self::TYPE_TEXT,

        /** Binary strings */
        'binary' => self::TYPE_BINARY,
        'varbinary' => self::TYPE_BINARY,
        'image' => self::TYPE_BINARY,

        /**
         * Other data types 'cursor' type can't be used with tables
         */
        'timestamp' => self::TYPE_TIMESTAMP,
        'hierarchyid' => self::TYPE_STRING,
        'uniqueidentifier' => self::TYPE_STRING,
        'sql_variant' => self::TYPE_STRING,
        'xml' => self::TYPE_STRING,
        'table' => self::TYPE_STRING,
    ];

    /**
     * Resolves the table name and schema name (if any).
     *
     * @param string $name The table name.
     *
     * @return TableSchemaInterface The resolved table name.
     */
    protected function resolveTableName(string $name): TableSchemaInterface
    {
        $resolvedName = new TableSchema();

        $parts = array_reverse(
            $this->db->getQuoter()->getTableNameParts($name)
        );

        $resolvedName->name($parts[0] ?? '');
        $resolvedName->schemaName($parts[1] ?? $this->defaultSchema);
        $resolvedName->catalogName($parts[2] ?? null);
        $resolvedName->serverName($parts[3] ?? null);

        if (empty($parts[2]) && $resolvedName->getSchemaName() === $this->defaultSchema) {
            $resolvedName->fullName($parts[0]);
        } else {
            $resolvedName->fullName(implode('.', array_reverse($parts)));
        }

        return $resolvedName;
    }

    /**
     * Returns all schema names in the database, including the default one but not system schemas.
     *
     * This method should be overridden by child classes to support this feature because the default implementation
     * simply throws an exception.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All schema name in the database, except system schemas.
     *
     * @link https://docs.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-database-principals-transact-sql
     */
    protected function findSchemaNames(): array
    {
        $sql = <<<SQL
        SELECT [s].[name]
        FROM [sys].[schemas] AS [s]
        INNER JOIN [sys].[database_principals] AS [p] ON [p].[principal_id] = [s].[principal_id]
        WHERE [p].[is_fixed_role] = 0 AND [p].[sid] IS NOT NULL
        ORDER BY [s].[name] ASC
        SQL;

        return $this->db->createCommand($sql)->queryColumn();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findTableComment(TableSchemaInterface $tableSchema): void
    {
        $schemaName = $tableSchema->getSchemaName()
            ? "N'" . (string) $tableSchema->getSchemaName() . "'" : 'SCHEMA_NAME()';
        $tableName = 'N' . (string) $this->db->getQuoter()->quoteValue($tableSchema->getName());

        $sql = <<<SQL
        SELECT [value]
        FROM fn_listextendedproperty (
            N'MS_description',
            'SCHEMA', $schemaName,
            'TABLE', $tableName,
            DEFAULT, DEFAULT)
        SQL;

        $comment = $this->db->createCommand($sql)->queryScalar();

        $tableSchema->comment(is_string($comment) ? $comment : null);
    }

    /**
     * Returns all table names in the database.
     *
     * This method should be overridden by child classes to support this feature because the default implementation
     * simply throws an exception.
     *
     * @param string $schema The schema of the tables.
     * Defaults to empty string, meaning the current or default schema.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All tables name in the database. The names have NO schema name prefix.
     */
    protected function findTableNames(string $schema = ''): array
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<SQL
        SELECT [t].[table_name]
        FROM [INFORMATION_SCHEMA].[TABLES] AS [t]
        WHERE [t].[table_schema] = :schema AND [t].[table_type] IN ('BASE TABLE', 'VIEW')
        ORDER BY [t].[table_name]
        SQL;

        return $this->db->createCommand($sql, [':schema' => $schema])->queryColumn();
    }

    /**
     * Loads the metadata for the specified table.
     *
     * @param string $name The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return TableSchemaInterface|null DBMS-dependent table metadata, `null` if the table doesn't exist.
     */
    protected function loadTableSchema(string $name): TableSchemaInterface|null
    {
        $table = $this->resolveTableName($name);
        $this->findPrimaryKeys($table);
        $this->findTableComment($table);

        if ($this->findColumns($table)) {
            $this->findForeignKeys($table);
            return $table;
        }

        return null;
    }

    /**
     * Loads a primary key for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return Constraint|null The primary key for the given table, `null` if the table has no primary key.
     */
    protected function loadTablePrimaryKey(string $tableName): Constraint|null
    {
        /** @psalm-var mixed $tablePrimaryKey */
        $tablePrimaryKey = $this->loadTableConstraints($tableName, self::PRIMARY_KEY);
        return $tablePrimaryKey instanceof Constraint ? $tablePrimaryKey : null;
    }

    /**
     * Loads all foreign keys for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array The foreign keys for the given table.
     */
    protected function loadTableForeignKeys(string $tableName): array
    {
        /** @psalm-var mixed $tableForeignKeys */
        $tableForeignKeys = $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);
        return is_array($tableForeignKeys) ? $tableForeignKeys : [];
    }

    /**
     * Loads all indexes for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array Indexes for the given table.
     */
    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<SQL
        SELECT
            [i].[name] AS [name],
            [iccol].[name] AS [column_name],
            [i].[is_unique] AS [index_is_unique],
            [i].[is_primary_key] AS [index_is_primary]
        FROM [sys].[indexes] AS [i]
        INNER JOIN [sys].[index_columns] AS [ic]
            ON [ic].[object_id] = [i].[object_id] AND [ic].[index_id] = [i].[index_id]
        INNER JOIN [sys].[columns] AS [iccol]
            ON [iccol].[object_id] = [ic].[object_id] AND [iccol].[column_id] = [ic].[column_id]
        WHERE [i].[object_id] = OBJECT_ID(:fullName)
        ORDER BY [ic].[key_ordinal] ASC
        SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $indexes = $this->db->createCommand($sql, [':fullName' => $resolvedName->getFullName()])->queryAll();

        /** @psalm-var array[] $indexes */
        $indexes = $this->normalizeRowKeyCase($indexes, true);
        $indexes = ArrayHelper::index($indexes, null, ['name']);

        $result = [];

        /**
         * @psalm-var array<
         *   string,
         *   array<
         *     array-key,
         *     array{name: string, column_name: string, index_is_unique: string, index_is_primary: string}
         *   >
         * > $indexes
         */
        foreach ($indexes as $name => $index) {
            $result[] = (new IndexConstraint())
                ->primary((bool) $index[0]['index_is_primary'])
                ->unique((bool) $index[0]['index_is_unique'])
                ->columnNames(ArrayHelper::getColumn($index, 'column_name'))
                ->name($name);
        }

        return $result;
    }

    /**
     * Loads all unique constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception|InvalidConfigException|Throwable
     *
     * @return array The unique constraints for the given table.
     */
    protected function loadTableUniques(string $tableName): array
    {
        /** @psalm-var mixed $tableUniques */
        $tableUniques = $this->loadTableConstraints($tableName, self::UNIQUES);
        return is_array($tableUniques) ? $tableUniques : [];
    }

    /**
     * Loads all check constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array The check constraints for the given table.
     */
    protected function loadTableChecks(string $tableName): array
    {
        /** @psalm-var mixed $tableCheck */
        $tableCheck = $this->loadTableConstraints($tableName, self::CHECKS);
        return is_array($tableCheck) ? $tableCheck : [];
    }

    /**
     * Loads all default value constraints for the given table.
     *
     * @param string $tableName The table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array The default value constraints for the given table.
     */
    protected function loadTableDefaultValues(string $tableName): array
    {
        /** @psalm-var mixed $tableDefault */
        $tableDefault = $this->loadTableConstraints($tableName, self::DEFAULTS);
        return is_array($tableDefault) ? $tableDefault : [];
    }

    /**
     * Creates a column schema for the database.
     *
     * This method may be overridden by child classes to create a DBMS-specific column schema.
     */
    protected function createColumnSchema(): ColumnSchema
    {
        return new ColumnSchema();
    }

    /**
     * Loads the column information into a {@see ColumnSchemaInterface} object.
     *
     * @psalm-param ColumnArray $info The column information.
     */
    protected function loadColumnSchema(array $info): ColumnSchemaInterface
    {
        $column = $this->createColumnSchema();

        $column->name($info['column_name']);
        $column->allowNull($info['is_nullable'] === 'YES');
        $column->dbType($info['data_type']);
        $column->enumValues([]); // MSSQL has only vague equivalents to enum.
        $column->primaryKey(false); // The primary key will be determined in the `findColumns()` method.
        $column->autoIncrement($info['is_identity'] === '1');
        $column->computed($info['is_computed'] === '1');
        $column->unsigned(stripos($column->getDbType(), 'unsigned') !== false);
        $column->comment($info['comment'] ?? '');
        $column->type(self::TYPE_STRING);

        if (preg_match('/^(\w+)(?:\(([^)]+)\))?/', $column->getDbType(), $matches)) {
            $type = $matches[1];

            if (isset($this->typeMap[$type])) {
                $column->type($this->typeMap[$type]);
            }

            if (!empty($matches[2])) {
                $values = explode(',', $matches[2]);
                $column->precision((int) $values[0]);
                $column->size((int) $values[0]);

                if (isset($values[1])) {
                    $column->scale((int) $values[1]);
                }

                if ($column->getSize() === 1 && ($type === 'tinyint' || $type === 'bit')) {
                    $column->type(self::TYPE_BOOLEAN);
                } elseif ($type === 'bit') {
                    if ($column->getSize() > 32) {
                        $column->type(self::TYPE_BIGINT);
                    } elseif ($column->getSize() === 32) {
                        $column->type(self::TYPE_INTEGER);
                    }
                }
            }
        }

        $column->phpType($this->getColumnPhpType($column));

        if ($info['column_default'] === '(NULL)') {
            $column->defaultValue(null);
        }

        if (!$column->isPrimaryKey() && !$column->isComputed() && $info['column_default'] !== null) {
            /** @psalm-var mixed $value */
            $value = $this->parseDefaultValue($info['column_default']);

            if (is_numeric($value)) {
                /** @psalm-var mixed $value */
                $value = $column->phpTypeCast($value);
            }

            $column->defaultValue($value);
        }

        return $column;
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchemaInterface $table The table metadata.
     *
     * @throws Throwable
     *
     * @return bool Whether the table exists in the database.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $columnsTableName = 'INFORMATION_SCHEMA.COLUMNS';

        $whereParams = [':table_name' => $table->getName()];
        $whereSql = '[t1].[table_name] = :table_name';

        if ($table->getCatalogName() !== null) {
            $columnsTableName = "{$table->getCatalogName()}.$columnsTableName";
            $whereSql .= ' AND [t1].[table_catalog] = :catalog';
            $whereParams[':catalog'] = $table->getCatalogName();
        }

        if ($table->getSchemaName() !== null) {
            $whereSql .= " AND [t1].[table_schema] = '{$table->getSchemaName()}'";
        }

        $columnsTableName = $this->db->getQuoter()->quoteTableName($columnsTableName);

        $sql = <<<SQL
        SELECT
            [t1].[column_name],
            [t1].[is_nullable],
        CASE WHEN [t1].[data_type] IN ('char','varchar','nchar','nvarchar','binary','varbinary') THEN
        CASE WHEN [t1].[character_maximum_length] = NULL OR [t1].[character_maximum_length] = -1 THEN
            [t1].[data_type]
        ELSE
            [t1].[data_type] + '(' + LTRIM(RTRIM(CONVERT(CHAR,[t1].[character_maximum_length]))) + ')'
        END
        WHEN [t1].[data_type] IN ('decimal','numeric') THEN
        CASE WHEN [t1].[numeric_precision] = NULL OR [t1].[numeric_precision] = -1 THEN
            [t1].[data_type]
        ELSE
            [t1].[data_type] + '(' + LTRIM(RTRIM(CONVERT(CHAR,[t1].[numeric_precision]))) + ',' + LTRIM(RTRIM(CONVERT(CHAR,[t1].[numeric_scale]))) + ')'
        END
        ELSE
            [t1].[data_type]
        END AS 'data_type',
        [t1].[column_default],
        COLUMNPROPERTY(OBJECT_ID([t1].[table_schema] + '.' + [t1].[table_name]), [t1].[column_name], 'IsIdentity') AS is_identity,
        COLUMNPROPERTY(OBJECT_ID([t1].[table_schema] + '.' + [t1].[table_name]), [t1].[column_name], 'IsComputed') AS is_computed,
        (
        SELECT CONVERT(VARCHAR, [t2].[value])
        FROM [sys].[extended_properties] AS [t2]
        WHERE
        [t2].[class] = 1 AND
        [t2].[class_desc] = 'OBJECT_OR_COLUMN' AND
        [t2].[name] = 'MS_Description' AND
        [t2].[major_id] = OBJECT_ID([t1].[TABLE_SCHEMA] + '.' + [t1].[table_name]) AND
        [t2].[minor_id] = COLUMNPROPERTY(OBJECT_ID([t1].[TABLE_SCHEMA] + '.' + [t1].[TABLE_NAME]), [t1].[COLUMN_NAME], 'ColumnID')
        ) as comment
        FROM $columnsTableName AS [t1]
        WHERE $whereSql
        SQL;

        try {
            /** @psalm-var ColumnArray[] $columns */
            $columns = $this->db->createCommand($sql, $whereParams)->queryAll();

            if (empty($columns)) {
                return false;
            }
        } catch (Exception) {
            return false;
        }

        foreach ($columns as $column) {
            $column = $this->loadColumnSchema($column);
            foreach ($table->getPrimaryKey() as $primaryKey) {
                if (strcasecmp($column->getName(), $primaryKey) === 0) {
                    $column->primaryKey(true);
                    break;
                }
            }

            if ($column->isPrimaryKey() && $column->isAutoIncrement()) {
                $table->sequenceName('');
            }

            $table->columns($column->getName(), $column);
        }

        return true;
    }

    /**
     * Collects the constraint details for the given table and constraint type.
     *
     * @param string $type Either PRIMARY KEY or UNIQUE.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array Each entry has index_name and field_name.
     */
    protected function findTableConstraints(TableSchemaInterface $table, string $type): array
    {
        $keyColumnUsageTableName = 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE';
        $tableConstraintsTableName = 'INFORMATION_SCHEMA.TABLE_CONSTRAINTS';

        $catalogName = $table->getCatalogName();
        if ($catalogName !== null) {
            $keyColumnUsageTableName = $catalogName . '.' . $keyColumnUsageTableName;
            $tableConstraintsTableName = $catalogName . '.' . $tableConstraintsTableName;
        }

        $keyColumnUsageTableName = $this->db->getQuoter()->quoteTableName($keyColumnUsageTableName);
        $tableConstraintsTableName = $this->db->getQuoter()->quoteTableName($tableConstraintsTableName);

        $sql = <<<SQL
        SELECT
            [kcu].[constraint_name] AS [index_name],
            [kcu].[column_name] AS [field_name]
        FROM $keyColumnUsageTableName AS [kcu]
        LEFT JOIN $tableConstraintsTableName AS [tc] ON
            [kcu].[table_schema] = [tc].[table_schema] AND
            [kcu].[table_name] = [tc].[table_name] AND
            [kcu].[constraint_name] = [tc].[constraint_name]
        WHERE
            [tc].[constraint_type] = :type AND
            [kcu].[table_name] = :tableName AND
            [kcu].[table_schema] = :schemaName
        SQL;

        return $this->db->createCommand(
            $sql,
            [
                ':tableName' => $table->getName(),
                ':schemaName' => $table->getSchemaName(),
                ':type' => $type,
            ]
        )->queryAll();
    }

    /**
     * Collects the primary key column details for the given table.
     *
     * @param TableSchemaInterface $table The table metadata
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findPrimaryKeys(TableSchemaInterface $table): void
    {
        /** @psalm-var array<array-key, array{index_name: string, field_name: string}> $primaryKeys */
        $primaryKeys = $this->findTableConstraints($table, 'PRIMARY KEY');

        foreach ($primaryKeys as $row) {
            $table->primaryKey($row['field_name']);
        }
    }

    /**
     * Collects the foreign key column details for the given table.
     *
     * @param TableSchemaInterface $table The table metadata
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findForeignKeys(TableSchemaInterface $table): void
    {
        $catalogName = $table->getCatalogName();
        $fk = [];
        $object = $table->getName();
        $schemaName = $table->getSchemaName();

        if ($schemaName !== null) {
            $object = $schemaName . '.' . $object;
        }

        if ($catalogName !== null) {
            $object = $catalogName . '.' . $object;
        }

        $sql = <<<SQL
        SELECT
        [fk].[name] AS [fk_name],
        [cp].[name] AS [fk_column_name],
        OBJECT_NAME([fk].[referenced_object_id]) AS [uq_table_name],
        [cr].[name] AS [uq_column_name]
        FROM [sys].[foreign_keys] AS [fk]
        INNER JOIN [sys].[foreign_key_columns] AS [fkc]
            ON [fk].[object_id] = [fkc].[constraint_object_id]
        INNER JOIN [sys].[columns] AS [cp]
            ON [fk].[parent_object_id] = [cp].[object_id] AND [fkc].[parent_column_id] = [cp].[column_id]
        INNER JOIN [sys].[columns] AS [cr]
            ON [fk].[referenced_object_id] = [cr].[object_id] AND [fkc].[referenced_column_id] = [cr].[column_id]
        WHERE [fk].[parent_object_id] = OBJECT_ID(:object)
        SQL;

        /**
         * @psalm-var array<
         *   array-key,
         *   array{fk_name: string, fk_column_name: string, uq_table_name: string, uq_column_name: string}
         * > $rows
         */
        $rows = $this->db->createCommand($sql, [':object' => $object])->queryAll();
        $table->foreignKeys([]);

        foreach ($rows as $row) {
            if (!isset($table->getForeignKeys()[$row['fk_name']])) {
                $fk[$row['fk_name']][] = $row['uq_table_name'];
                $table->foreignKeys($fk);
            }

            $fk[$row['fk_name']][$row['fk_column_name']] = $row['uq_column_name'];
            $table->foreignKeys($fk);
        }
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    protected function findViewNames(string $schema = ''): array
    {
        if ($schema === '') {
            $schema = $this->defaultSchema;
        }

        $sql = <<<SQL
        SELECT [t].[table_name]
        FROM [INFORMATION_SCHEMA].[TABLES] AS [t]
        WHERE [t].[table_schema] = :schema AND [t].[table_type] = 'VIEW'
        ORDER BY [t].[table_name]
        SQL;

        return $this->db->createCommand($sql, [':schema' => $schema])->queryColumn();
    }

    /**
     * Returns all unique indexes for the given table.
     *
     * Each array element is of the following structure:
     *
     * ```php
     * [
     *     'IndexName1' => ['col1' [, ...]],
     *     'IndexName2' => ['col2' [, ...]],
     * ]
     * ```
     *
     * @param TableSchemaInterface $table The table metadata.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return array All unique indexes for the given table.
     */
    public function findUniqueIndexes(TableSchemaInterface $table): array
    {
        $result = [];

        /** @psalm-var array<array-key, array{index_name: string, field_name: string}> $tableUniqueConstraints */
        $tableUniqueConstraints = $this->findTableConstraints($table, 'UNIQUE');

        foreach ($tableUniqueConstraints as $row) {
            $result[$row['index_name']][] = $row['field_name'];
        }

        return $result;
    }

    /**
     * Loads multiple types of constraints and returns the specified ones.
     *
     * @param string $tableName table name.
     * @param string $returnType return type:
     * - primaryKey
     * - foreignKeys
     * - uniques
     * - checks
     * - defaults
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * @return mixed Constraints of the specified type.
     */
    private function loadTableConstraints(string $tableName, string $returnType): mixed
    {
        $sql = <<<SQL
        SELECT
            [o].[name] AS [name],
            COALESCE([ccol].[name], [dcol].[name], [fccol].[name], [kiccol].[name]) AS [column_name],
            RTRIM([o].[type]) AS [type],
            OBJECT_SCHEMA_NAME([f].[referenced_object_id]) AS [foreign_table_schema],
            OBJECT_NAME([f].[referenced_object_id]) AS [foreign_table_name],
            [ffccol].[name] AS [foreign_column_name],
            [f].[update_referential_action_desc] AS [on_update],
            [f].[delete_referential_action_desc] AS [on_delete],
            [c].[definition] AS [check_expr],
            [d].[definition] AS [default_expr]
        FROM (SELECT OBJECT_ID(:fullName) AS [object_id]) AS [t]
        INNER JOIN [sys].[objects] AS [o]
            ON [o].[parent_object_id] = [t].[object_id] AND [o].[type] IN ('PK', 'UQ', 'C', 'D', 'F')
        LEFT JOIN [sys].[check_constraints] AS [c]
            ON [c].[object_id] = [o].[object_id]
        LEFT JOIN [sys].[columns] AS [ccol]
            ON [ccol].[object_id] = [c].[parent_object_id] AND [ccol].[column_id] = [c].[parent_column_id]
        LEFT JOIN [sys].[default_constraints] AS [d]
            ON [d].[object_id] = [o].[object_id]
        LEFT JOIN [sys].[columns] AS [dcol]
            ON [dcol].[object_id] = [d].[parent_object_id] AND [dcol].[column_id] = [d].[parent_column_id]
        LEFT JOIN [sys].[key_constraints] AS [k]
            ON [k].[object_id] = [o].[object_id]
        LEFT JOIN [sys].[index_columns] AS [kic]
            ON [kic].[object_id] = [k].[parent_object_id] AND [kic].[index_id] = [k].[unique_index_id]
        LEFT JOIN [sys].[columns] AS [kiccol]
            ON [kiccol].[object_id] = [kic].[object_id] AND [kiccol].[column_id] = [kic].[column_id]
        LEFT JOIN [sys].[foreign_keys] AS [f]
            ON [f].[object_id] = [o].[object_id]
        LEFT JOIN [sys].[foreign_key_columns] AS [fc]
            ON [fc].[constraint_object_id] = [o].[object_id]
        LEFT JOIN [sys].[columns] AS [fccol]
            ON [fccol].[object_id] = [fc].[parent_object_id] AND [fccol].[column_id] = [fc].[parent_column_id]
        LEFT JOIN [sys].[columns] AS [ffccol]
            ON [ffccol].[object_id] = [fc].[referenced_object_id] AND [ffccol].[column_id] = [fc].[referenced_column_id]
        ORDER BY [kic].[key_ordinal] ASC, [fc].[constraint_column_id] ASC
        SQL;

        $resolvedName = $this->resolveTableName($tableName);
        $constraints = $this->db->createCommand($sql, [':fullName' => $resolvedName->getFullName()])->queryAll();

        /** @psalm-var array[] $constraints */
        $constraints = $this->normalizeRowKeyCase($constraints, true);
        $constraints = ArrayHelper::index($constraints, null, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
            self::CHECKS => [],
            self::DEFAULTS => [],
        ];

        /** @psalm-var array<array-key, array> $constraints */
        foreach ($constraints as $type => $names) {
            /**
             * @psalm-var object|string|null $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                switch ($type) {
                    case 'PK':
                        /** @psalm-var Constraint */
                        $result[self::PRIMARY_KEY] = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'F':
                        $result[self::FOREIGN_KEYS][] = (new ForeignKeyConstraint())
                            ->foreignSchemaName($constraint[0]['foreign_table_schema'])
                            ->foreignTableName($constraint[0]['foreign_table_name'])
                            ->foreignColumnNames(ArrayHelper::getColumn($constraint, 'foreign_column_name'))
                            ->onDelete(str_replace('_', '', $constraint[0]['on_delete']))
                            ->onUpdate(str_replace('_', '', $constraint[0]['on_update']))
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'UQ':
                        $result[self::UNIQUES][] = (new Constraint())
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'C':
                        $result[self::CHECKS][] = (new CheckConstraint())
                            ->expression($constraint[0]['check_expr'])
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                    case 'D':
                        $result[self::DEFAULTS][] = (new DefaultValueConstraint())
                            ->value($constraint[0]['default_expr'])
                            ->columnNames(ArrayHelper::getColumn($constraint, 'column_name'))
                            ->name($name);
                        break;
                }
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }

    public function createColumnSchemaBuilder(
        string $type,
        array|int|string $length = null
    ): ColumnSchemaBuilderInterface {
        return new ColumnSchemaBuilder($type, $length);
    }

    /**
     * Returns the cache key for the specified table name.
     *
     * @param string $name The table name.
     *
     * @return array The cache key.
     */
    protected function getCacheKey(string $name): array
    {
        return array_merge([self::class], $this->db->getCacheKey(), [$this->getRawTableName($name)]);
    }

    /**
     * Returns the cache tag name.
     *
     * This allows {@see refresh()} to invalidate all cached table schemas.
     *
     * @return string The cache tag name.
     */
    protected function getCacheTag(): string
    {
        return md5(serialize(array_merge([self::class], $this->db->getCacheKey())));
    }

    private function parseDefaultValue(mixed $value): mixed
    {
        $value = (string) $value;

        if (preg_match('/^\'(.*)\'$/', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^\((.*)\)$/', $value, $matches)) {
            return $this->parseDefaultValue($matches[1]);
        }

        return $value;
    }
}
