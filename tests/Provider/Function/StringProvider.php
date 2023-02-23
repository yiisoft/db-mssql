<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Function;

final class StringProvider
{
    public static function columns(): array
    {
        return [
            /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/ascii-transact-sql?view=sql-server-ver16 */
            ['Myascii', 'int', 'integer', 'ascii(\'a\')'],

            /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/char-transact-sql?view=sql-server-ver16 */
            ['Mychar', 'char(1)', 'string', 'char((97))'],

            /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/charindex-transact-sql?view=sql-server-ver16 */
            ['Mycharindex', 'int', 'integer', 'charindex(\'B\',\'aBc\')'],

            /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/concat-transact-sql?view=sql-server-ver16 */
            ['Myconcat', 'varchar(3)', 'string', 'concat(\'a\',\'b\',\'c\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/concat-ws-transact-sql?view=sql-server-ver16 */
            ['Myconcatws', 'varchar(3)', 'string', 'concat_ws(\'a\',\'b\',\'C\')'],

            /* edge case */
            [
                'Mycomplex',
                'varchar(10)',
                'string',
                'substring(stuff(concat(\'a\',\'b\',\'c\'),(3),(1),concat_ws(\'f\',\'g\',\'h\')),(5),(1))',
            ],

            /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/data-length-transact-sql?view=sql-server-ver16 */
            ['Mydatalength', 'int', 'integer', 'datalength(\'abc\')'],

            /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/left-transact-sql?view=sql-server-ver16 */
            ['Myleft', 'varchar(1)', 'string', 'left(\'abc\',(1))'],

            /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/len-transact-sql?view=sql-server-ver16 */
            ['Mylen', 'int', 'integer', 'len(\'abc\')'],

            /* @link https://learn.microsoft.com/en-us/sql/t-sql/functions/lower-transact-sql?view=sql-server-ver16 */
            ['Mylower', 'varchar(3)', 'string', 'lower(\'ABC\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/ltrim-transact-sql?view=sql-server-ver16 */
            ['Myltrim', 'varchar(3)', 'string', 'ltrim(\' abc\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/nchar-transact-sql?view=sql-server-ver16 */
            ['Mynchar', 'nchar(1)', 'string', 'nchar((50))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/patindex-transact-sql?view=sql-server-ver16 */
            ['Mypatindex', 'int', 'integer', 'patindex(\'a\',\'abc\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/replace-transact-sql?view=sql-server-ver16 */
            ['Myreplace', 'varchar(3)', 'string', 'replace(\'abc\',\'a\',\'d\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/right-transact-sql?view=sql-server-ver16 */
            ['Myright', 'varchar(1)', 'string', 'right(\'abc\',(1))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/rtrim-transact-sql?view=sql-server-ver16 */
            ['Myrtrim', 'varchar(3)', 'string', 'rtrim(\'abc \')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/str-transact-sql?view=sql-server-ver16 */
            ['Mystr', 'varchar(5)', 'string', 'str((1.234),(5),(2))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/stuff-transact-sql?view=sql-server-ver16 */
            ['Mystuff', 'varchar(3)', 'string', 'stuff(\'abc\',(1),(1),\'d\')'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/substring-transact-sql?view=sql-server-ver16 */
            ['Mysubstring', 'varchar(3)', 'string', 'substring(\'abc\',(1),(1))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/upper-transact-sql?view=sql-server-ver16 */
            ['Myupper', 'varchar(3)', 'string', 'upper(\'abc\')'],
        ];
    }
}
