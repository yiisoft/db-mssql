<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Function;

final class ConvertionProvider
{
    public static function castColumns(): array
    {
        return [
            ['Mycast1', 'int', 'integer', 'CONVERT([int],\'1\')'],
            ['Mycast2', 'int', 'integer', 'CONVERT([int],(14.85))'],
            ['Mycast3', 'float', 'double', 'CONVERT([float],\'14.85\')'],
            ['Mycast4', 'varchar(4)', 'string', 'CONVERT([varchar](4),(15.6))'],
            ['Mycast5', 'datetime', 'string', 'CONVERT([datetime],\'2023-02-21\')'],
            ['Mycast6', 'binary(10)', 'resource', 'CONVERT([binary](10),\'testme\')'],
        ];
    }

    public static function convertColumns(): array
    {
        return [
            ['Myconvert1', 'int', 'integer', 'CONVERT([int],\'1\')'],
            ['Myconvert2', 'int', 'integer', 'CONVERT([int],(14.85))'],
            ['Myconvert3', 'float', 'double', 'CONVERT([float],\'14.85\')'],
            ['Myconvert4', 'varchar(4)', 'string', 'CONVERT([varchar](4),(15.6))'],
            ['Myconvert5', 'datetime', 'string', 'CONVERT([datetime],\'2023-02-21\')'],
            ['Myconvert6', 'binary(10)', 'resource', 'CONVERT([binary](10),\'testme\')'],
        ];
    }

    public static function tryCastColumns(): array
    {
        return [
            ['Mytrycast1', 'int', 'integer', 'TRY_CAST(\'1\' AS [int])'],
            ['Mytrycast2', 'int', 'integer', 'TRY_CAST((14.85) AS [int])'],
            ['Mytrycast3', 'float', 'double', 'TRY_CAST(\'14.85\' AS [float])'],
            ['Mytrycast4', 'varchar(4)', 'string', 'TRY_CAST((15.6) AS [varchar](4))'],
            ['Mytrycast5', 'datetime', 'string', 'TRY_CAST(\'2023-02-21\' AS [datetime])'],
            ['Mytrycast6', 'binary(10)', 'resource', 'TRY_CAST(\'testme\' AS [binary](10))'],
        ];
    }

    public static function tryConvertColumns(): array
    {
        return [
            ['Mytryconvert1', 'int', 'integer', 'TRY_CAST(\'1\' AS [int])'],
            ['Mytryconvert2', 'int', 'integer', 'TRY_CAST((14.85) AS [int])'],
            ['Mytryconvert3', 'float', 'double', 'TRY_CAST(\'14.85\' AS [float])'],
            ['Mytryconvert4', 'varchar(4)', 'string', 'TRY_CAST((15.6) AS [varchar](4))'],
            ['Mytryconvert5', 'datetime', 'string', 'TRY_CAST(\'2023-02-21\' AS [datetime])'],
            ['Mytryconvert6', 'binary(10)', 'resource', 'TRY_CAST(\'testme\' AS [binary](10))'],
        ];
    }
}
