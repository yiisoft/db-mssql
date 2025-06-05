<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;
use Yiisoft\Db\Driver\Pdo\AbstractPdoSchema;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;

use function array_change_key_case;
use function array_column;
use function array_fill_keys;
use function array_map;
use function str_replace;

/**
 * Implements the MSSQL Server specific schema, supporting MSSQL Server 2017 and above.
 *
 * @psalm-type ColumnArray = array{
 *   check: string|null,
 *   column_name: string,
 *   column_default: string|null,
 *   is_nullable: string,
 *   data_type: string,
 *   size: int|string|null,
 *   numeric_scale: int|string|null,
 *   is_identity: string,
 *   is_computed: string,
 *   comment: string|null,
 *   primaryKey: bool,
 *   schema: string|null,
 *   table: string
 * }
 * @psalm-type ConstraintArray = array<
 *   array-key,
 *   array {
 *     name: string,
 *     column_name: string,
 *     type: string,
 *     foreign_table_schema: string,
 *     foreign_table_name: string,
 *     foreign_column_name: string,
 *     on_update: string,
 *     on_delete: string,
 *     check_expr: string,
 *     default_expr: string
 *   }
 * >
 */
final class Schema extends AbstractPdoSchema
{
    /**
     * @var string|null The default schema used for the current session.
     */
    protected string|null $defaultSchema = 'dbo';

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

        /** @var string[] */
        return $this->db->createCommand($sql)->queryColumn();
    }

    protected function findTableComment(TableSchemaInterface $tableSchema): void
    {
        $schemaName = $tableSchema->getSchemaName()
            ? "N'" . (string) $tableSchema->getSchemaName() . "'" : 'SCHEMA_NAME()';
        $tableName = 'N' . $this->db->getQuoter()->quoteValue($tableSchema->getName());

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

        /** @var string[] */
        return $this->db->createCommand($sql, [':schema' => $schema])->queryColumn();
    }

    /**
     * @psalm-param array{
     *     flags: int,
     *     "sqlsrv:decl_type": string,
     *     native_type: string,
     *     table: string,
     *     pdo_type: int,
     *     name: string,
     *     len: int,
     *     precision: int,
     * } $metadata
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    protected function loadResultColumn(array $metadata): ColumnInterface|null
    {
        if (empty($metadata['sqlsrv:decl_type'])) {
            return null;
        }

        $dbType = $metadata['sqlsrv:decl_type'];

        $columnInfo = ['fromResult' => true];

        if (!empty($metadata['table'])) {
            $columnInfo['table'] = $metadata['table'];
            $columnInfo['name'] = $metadata['name'];
        } elseif (!empty($metadata['name'])) {
            $columnInfo['name'] = $metadata['name'];
        }

        if (!empty($metadata['len'])) {
            $columnInfo['size'] = match ($dbType) {
                'time', 'datetime', 'datetime2', 'datetimeoffset' => $metadata['precision'],
                default => $metadata['len'],
            };
        }

        match ($dbType) {
            'decimal', 'numeric' => $columnInfo['scale'] = $metadata['precision'],
            default => null,
        };

        return $this->db->getColumnFactory()->fromDbType($dbType, $columnInfo);
    }

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

    protected function loadTablePrimaryKey(string $tableName): IndexConstraint|null
    {
        /** @var IndexConstraint|null */
        return $this->loadTableConstraints($tableName, self::PRIMARY_KEY);
    }

    protected function loadTableForeignKeys(string $tableName): array
    {
        /** @var ForeignKeyConstraint[] */
        return $this->loadTableConstraints($tableName, self::FOREIGN_KEYS);
    }

    protected function loadTableIndexes(string $tableName): array
    {
        $sql = <<<SQL
        SELECT
            [i].[name] AS [name],
            [iccol].[name] AS [column_name],
            [i].[is_unique],
            [i].[is_primary_key]
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

        $indexes = array_map(array_change_key_case(...), $indexes);
        $indexes = DbArrayHelper::arrange($indexes, ['name']);

        $result = [];

        /**
         * @psalm-var array<
         *   string,
         *   list<array{name: string, column_name: string, is_unique: string, is_primary_key: string}>
         * > $indexes
         */
        foreach ($indexes as $name => $index) {
            $result[] = new IndexConstraint(
                $name,
                array_column($index, 'column_name'),
                (bool) $index[0]['is_unique'],
                (bool) $index[0]['is_primary_key'],
            );
        }

        return $result;
    }

    protected function loadTableUniques(string $tableName): array
    {
        /** @var IndexConstraint[] */
        return $this->loadTableConstraints($tableName, self::UNIQUES);
    }

    protected function loadTableChecks(string $tableName): array
    {
        /** @var CheckConstraint[] */
        return $this->loadTableConstraints($tableName, self::CHECKS);
    }

    protected function loadTableDefaultValues(string $tableName): array
    {
        /** @var DefaultValueConstraint[] */
        return $this->loadTableConstraints($tableName, self::DEFAULTS);
    }

    /**
     * Loads the column information into a {@see ColumnInterface} object.
     *
     * @psalm-param ColumnArray $info The column information.
     */
    private function loadColumn(array $info): ColumnInterface
    {
        return $this->db->getColumnFactory()->fromDbType($info['data_type'], [
            'autoIncrement' => $info['is_identity'] === '1',
            'check' => $info['check'],
            'comment' => $info['comment'],
            'computed' => $info['is_computed'] === '1',
            'defaultValueRaw' => $info['column_default'],
            'name' => $info['column_name'],
            'notNull' => $info['is_nullable'] !== 'YES',
            'primaryKey' => $info['primaryKey'],
            'scale' => $info['numeric_scale'] !== null ? (int) $info['numeric_scale'] : null,
            'schema' => $info['schema'],
            'size' => $info['size'] !== null ? (int) $info['size'] : null,
            'table' => $info['table'],
        ]);
    }

    /**
     * Collects the metadata of table columns.
     *
     * @param TableSchemaInterface $table The table metadata.
     *
     * @return bool Whether the table exists in the database.
     */
    protected function findColumns(TableSchemaInterface $table): bool
    {
        $schemaName = $table->getSchemaName();
        $tableName = $table->getName();

        $columnsTableName = '[INFORMATION_SCHEMA].[COLUMNS]';
        $whereSql = '[t].[table_name] = :table_name';
        $whereParams = [':table_name' => $tableName];

        if ($table->getCatalogName() !== null) {
            $columnsTableName = "[{$table->getCatalogName()}].$columnsTableName";
            $whereSql .= ' AND [t].[table_catalog] = :catalog';
            $whereParams[':catalog'] = $table->getCatalogName();
        }

        if ($schemaName !== null) {
            $whereSql .= ' AND [t].[table_schema] = :schema_name';
            $whereParams[':schema_name'] = $schemaName;
        }

        $sql = <<<SQL
        SELECT
            [t].[column_name],
            [t].[column_default],
            [t].[is_nullable],
            [t].[data_type],
            COALESCE(NULLIF([t].[character_maximum_length], -1), [t].[numeric_precision], [t].[datetime_precision]) AS [size],
            [t].[numeric_scale],
            COLUMNPROPERTY(OBJECT_ID([t].[table_schema] + '.' + [t].[table_name]), [t].[column_name], 'IsIdentity') AS [is_identity],
            COLUMNPROPERTY(OBJECT_ID([t].[table_schema] + '.' + [t].[table_name]), [t].[column_name], 'IsComputed') AS [is_computed],
            [ext].[value] as [comment],
            [c].[definition] AS [check]
        FROM $columnsTableName AS [t]
        LEFT JOIN [sys].[extended_properties] AS [ext]
            ON [ext].[class] = 1
                AND [ext].[class_desc] = 'OBJECT_OR_COLUMN'
                AND [ext].[name] = 'MS_Description'
                AND [ext].[major_id] = OBJECT_ID([t].[table_schema] + '.' + [t].[table_name])
                AND [ext].[minor_id] = COLUMNPROPERTY([ext].[major_id], [t].[column_name], 'ColumnID')
        LEFT JOIN [sys].[check_constraints] AS [c]
            ON [c].[parent_object_id] = OBJECT_ID([t].[table_schema] + '.' + [t].[table_name])
                AND [c].[parent_column_id] = COLUMNPROPERTY([c].[parent_object_id], [t].[column_name], 'ColumnID')
        WHERE $whereSql
        SQL;

        try {
            $columns = $this->db->createCommand($sql, $whereParams)->queryAll();

            if (empty($columns)) {
                return false;
            }
        } catch (Exception) {
            return false;
        }

        $primaryKeys = array_fill_keys($table->getPrimaryKey(), true);

        foreach ($columns as $info) {
            $info = array_change_key_case($info);

            /** @psalm-var ColumnArray $info */
            $info['primaryKey'] = isset($primaryKeys[$info['column_name']]);
            $info['schema'] = $schemaName;
            $info['table'] = $tableName;

            $column = $this->loadColumn($info);

            if ($column->isPrimaryKey() && $column->isAutoIncrement()) {
                $table->sequenceName('');
            }

            $table->column($info['column_name'], $column);
        }

        return true;
    }

    /**
     * Collects the constraint details for the given table and constraint type.
     *
     * @param string $type Either PRIMARY KEY or UNIQUE.
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
        ORDER BY [kcu].[ordinal_position] ASC
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

        /** @var string[] */
        return $this->db->createCommand($sql, [':schema' => $schema])->queryColumn();
    }

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
     * @return CheckConstraint[]|DefaultValueConstraint[]|ForeignKeyConstraint[]|IndexConstraint[]|IndexConstraint|null
     * Constraints of the specified type.
     */
    private function loadTableConstraints(string $tableName, string $returnType): array|IndexConstraint|null
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

        $constraints = array_map(array_change_key_case(...), $constraints);
        $constraints = DbArrayHelper::arrange($constraints, ['type', 'name']);

        $result = [
            self::PRIMARY_KEY => null,
            self::FOREIGN_KEYS => [],
            self::UNIQUES => [],
            self::CHECKS => [],
            self::DEFAULTS => [],
        ];

        foreach ($constraints as $type => $names) {
            /**
             * @var string $name
             * @psalm-var ConstraintArray $constraint
             */
            foreach ($names as $name => $constraint) {
                /** @psalm-suppress ArgumentTypeCoercion */
                match ($type) {
                    'PK' => $result[self::PRIMARY_KEY] = new IndexConstraint(
                        $name,
                        array_column($constraint, 'column_name'),
                        true,
                        true,
                    ),
                    'F' => $result[self::FOREIGN_KEYS][] = new ForeignKeyConstraint(
                        $name,
                        array_column($constraint, 'column_name'),
                        $constraint[0]['foreign_table_schema'] . '.' . $constraint[0]['foreign_table_name'],
                        array_column($constraint, 'foreign_column_name'),
                        str_replace('_', ' ', $constraint[0]['on_update']),
                        str_replace('_', ' ', $constraint[0]['on_delete']),
                    ),
                    'UQ' => $result[self::UNIQUES][] = new IndexConstraint(
                        $name,
                        array_column($constraint, 'column_name'),
                        true,
                    ),
                    'C' => $result[self::CHECKS][] = new CheckConstraint(
                        $name,
                        array_column($constraint, 'column_name'),
                        $constraint[0]['check_expr'],
                    ),
                    'D' => $result[self::DEFAULTS][] = new DefaultValueConstraint(
                        $name,
                        array_column($constraint, 'column_name'),
                        $constraint[0]['default_expr'],
                    ),
                };
            }
        }

        foreach ($result as $type => $data) {
            $this->setTableMetadata($tableName, $type, $data);
        }

        return $result[$returnType];
    }
}
