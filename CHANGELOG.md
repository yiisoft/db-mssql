# MSSQL Server driver for Yii Database Change Log

## 1.1.1 under development

- Enh #286: Change property `Schema::$typeMap` to constant `Schema::TYPE_MAP` (@Tigrov)

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
