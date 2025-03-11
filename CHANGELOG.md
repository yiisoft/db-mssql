# MSSQL Server driver for Yii Database Change Log

## 2.0.0 under development

- New #277: Implement `ColumnSchemaInterface` classes according to the data type of database table columns
  for type casting performance. Related with yiisoft/db#752 (@Tigrov)
- Enh #293: Implement `SqlParser` and `ExpressionBuilder` driver classes (@Tigrov)
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
- Enh #323: Refactor `Dsn` class (@Tigrov)
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
- New #342: Support JSON type (@Tigrov)
- New #345: Add parameters `$ifExists` and `$cascade` to `CommandInterface::dropTable()` and
  `DDLQueryBuilderInterface::dropTable()` methods (@vjik)

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
