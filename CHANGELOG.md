# MSSQL Server driver for Yii Database Change Log

## 2.0.0 under development

- Chg #368: Use `\InvalidArgumentException` instead of `Yiisoft\Db\Exception\InvalidArgumentException` (@DikoIbragimov)
- New #277, #384: Implement `ColumnInterface` classes according to the data type of database table columns
  for type casting performance. Related with yiisoft/db#752 (@Tigrov)
- Enh #293, #357: Implement and use `SqlParser` class (@Tigrov)
- Chg #306: Remove parameter `$withColumn` from `Quoter::getTableNameParts()` method (@Tigrov)
- Chg #308: Replace call of `SchemaInterface::getRawTableName()` to `QuoterInterface::getRawTableName()` (@Tigrov)
- Enh #312: Refactor `bit` type (@Tigrov)
- Enh #315: Refactor PHP type of `ColumnSchemaInterface` instances (@Tigrov)
- Enh #317: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)
- New #316, #327: Implement `ColumnFactory` class (@Tigrov)
- Enh #319: Separate column type constants (@Tigrov)
- New #320: Realize `ColumnBuilder` class (@Tigrov)
- Enh #321: Update according changes in `ColumnSchemaInterface` (@Tigrov)
- New #322, #330, #340: Add `ColumnDefinitionBuilder` class (@Tigrov)
- Enh #323, #378: Refactor `Dsn` class (@Tigrov)
- Enh #324: Use constructor to create columns and initialize properties (@Tigrov)
- Enh #327: Refactor `Schema::findColumns()` method (@Tigrov)
- Enh #328: Refactor `Schema::normalizeDefaultValue()` method and move it to `ColumnFactory` class (@Tigrov)
- Enh #331: Refactor according to changes #902 in `yiisoft/db` package (@Tigrov)
- Chg #333: Update `QueryBuilder` constructor (@Tigrov)
- Enh #332: Use `ColumnDefinitionBuilder` to generate table column SQL representation (@Tigrov)
- Enh #335: Remove `ColumnInterface` (@Tigrov)
- Enh #337: Rename `ColumnSchemaInterface` to `ColumnInterface` (@Tigrov)
- Enh #338: Replace `DbArrayHelper::getColumn()` with `array_column()` (@Tigrov)
- New #339: Add `IndexType` and `IndexMethod` classes (@Tigrov)
- Bug #343: Explicitly mark nullable parameters (@vjik)
- New #342, #405: Support JSON type (@Tigrov)
- Chg #344: Change supported PHP versions to `8.1 - 8.4` (@Tigrov)
- Chg #344: Change return type of `Command::insertWithReturningPks()` method to `array|false` (@Tigrov)
- New #345: Add parameters `$ifExists` and `$cascade` to `CommandInterface::dropTable()` and
  `DDLQueryBuilderInterface::dropTable()` methods (@vjik)
- Chg #348: Remove usage of `hasLimit()` and `hasOffset()` methods of `DQLQueryBuilder` class (@Tigrov)
- Enh #350, #382: Refactor according changes in `db` package (@Tigrov)
- New #349: Add `caseSensitive` option to like condition (@vjik)
- Enh #352: Remove `getCacheKey()` and `getCacheTag()` methods from `Schema` class (@Tigrov)
- Enh #355, #356: Use `DbArrayHelper::arrange()` instead of `DbArrayHelper::index()` method (@Tigrov)
- New #353: Realize `Schema::loadResultColumn()` method (@Tigrov)
- Enh #300: Remove realization of `Connection::createBatchQueryResult()` method (@Tigrov)
- Bug #360: Fix columns order in composite primary key (@Tigrov)
- New #358: Use `DateTimeColumn` class for datetime column types (@Tigrov)
- Enh #361, #362: Refactor `DMLQueryBuilder::insertWithReturningPks()` method (@Tigrov)
- New #361, #362: Implement `DMLQueryBuilder::upsertReturning()` method (@Tigrov)
- Chg #363: Add alias in `DQLQueryBuilder::selectExists()` method for consistency with other DBMS (@Tigrov)
- Enh #364, #377: Refactor constraints (@Tigrov)
- Chg #366: Rename `DMLQueryBuilder::insertWithReturningPks()` to `DMLQueryBuilder::insertReturningPks()` (@Tigrov)
- Enh #372: Provide `yiisoft/db-implementation` virtual package (@vjik)
- Enh #375: Adapt to `Param` refactoring in `yiisoft/db` package (@vjik)
- Enh #376, #379: Adapt to conditions refactoring in `yiisoft/db` package (@vjik)
- Enh #380: Remove support dots in table names (@Tigrov)
- Enh #383: Refactor `TableSchema` and `Schema` classes (@Tigrov)
- Enh #386: Support column's collation (@Tigrov)
- New #391: Add `Connection::getColumnBuilderClass()` method (@Tigrov)
- New #390, #396, #404: Implement `ArrayMergeBuilder`, `GreatestBuilder`, `LeastBuilder`, `LengthBuilder`,
  `LongestBuilder` and `ShortestBuilder` classes (@Tigrov)
- Enh #393: Refactor `DMLQueryBuilder::upsert()` method (@Tigrov)
- Chg #398: Update expression namespaces according to changes in `yiisoft/db` package (@Tigrov)
- Enh #392: Update `DMLQueryBuilder::update()` method to adapt changes in `yiisoft/db` (@rustamwin)
- Enh #406: Adapt to `DQLQueryBuilderInterface::buildWithQueries()` signature changes in `yiisoft/db` package (@vjik)
- Enh #408: Add support of `tinyint identity`, `small identity`, `int identity`, `bigint identity`, `numeric identity`
  and `decimal identity` column types (@vjik)

## 1.2.0 March 21, 2024

- Enh #286: Change property `Schema::$typeMap` to constant `Schema::TYPE_MAP` (@Tigrov)
- Enh #291: Resolve deprecated methods (@Tigrov)
- Enh #292: Minor refactoring of `Command` and `Quoter` (@Tigrov)
- Bug #287: Fix `DMLQueryBuilder::insertWithReturningPks()` and `Command::insertWithReturningPks()` methods (@Tigrov)

## 1.1.0 November 12, 2023

- Enh #283: Move methods from `Command` to `AbstractPdoCommand` class (@Tigrov)
- Bug #275: Refactor `DMLQueryBuilder`, related with yiisoft/db#746 (@Tigrov)
- Bug #278: Remove `RECURSIVE` expression from CTE queries (@Tigrov)
- Bug #280: Fix type boolean (@terabytesoftw)
- Bug #282: Fix `DDLQueryBuilder::alterColumn()` for columns with default null (@Tigrov)

## 1.0.1 July 24, 2023

- Enh #271: Typecast refactoring (@Tigrov)

## 1.0.0 April 12, 2023

- Initial release.
