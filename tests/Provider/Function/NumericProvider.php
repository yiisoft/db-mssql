<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider\Function;

final class NumericProvider
{
    public static function columns(): array
    {
        return [
            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/abs-transact-sql?view=sql-server-ver16 */
            ['Myabs', 'numeric', 'double', 'abs((-1))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/acos-transact-sql?view=sql-server-ver16 */
            ['Myacos', 'numeric', 'double', 'acos((-1.0))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/asin-transact-sql?view=sql-server-ver16 */
            ['Myasin', 'numeric', 'double', 'asin((0.1472738))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/atan-transact-sql?view=sql-server-ver16 */
            ['Myatan', 'numeric', 'double', 'atan((197.1099392))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/ceiling-transact-sql?view=sql-server-ver16 */
            ['Myceiling', 'money', 'string', 'ceiling(($-123.4500))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/cos-transact-sql?view=sql-server-ver16 */
            ['Mycos', 'numeric', 'double', 'cos((14.78))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/cot-transact-sql?view=sql-server-ver16 */
            ['Mycot', 'numeric', 'double', 'cot((124.1332))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/degrees-transact-sql?view=sql-server-ver16 */
            ['Mydegrees', 'numeric', 'double', 'degrees(pi()/(2))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/exp-transact-sql?view=sql-server-ver16 */
            ['Myexp', 'numeric', 'double', 'exp((10.0))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/floor-transact-sql?view=sql-server-ver16 */
            ['Myfloor', 'int', 'integer', 'floor((-123.45))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/log-transact-sql?view=sql-server-ver16 */
            ['Mylog', 'numeric', 'double', 'log((10.0))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/log10-transact-sql?view=sql-server-ver16 */
            ['Mylog10', 'numeric', 'double', 'log10((145.175643))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/pi-transact-sql?view=sql-server-ver16 */
            ['Mypi', 'numeric', 'double', 'pi()'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/power-transact-sql?view=sql-server-ver16 */
            ['Mypower', 'numeric', 'double', 'power((2),(2.5))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/radians-transact-sql?view=sql-server-ver16 */
            ['Myradians', 'numeric', 'double', 'radians((180.0))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/rand-transact-sql?view=sql-server-ver16 */
            ['Myrand', 'numeric', 'double', 'rand()'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/round-transact-sql?view=sql-server-ver16 */
            ['Myround', 'numeric', 'double', 'round((123.9994),(3))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/sign-transact-sql?view=sql-server-ver16 */
            ['Mysign', 'float', 'double', 'sign((-125))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/sin-transact-sql?view=sql-server-ver16 */
            ['Mysin', 'numeric', 'double', 'sin((45.175643))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/sqrt-transact-sql?view=sql-server-ver16 */
            ['Mysqrt', 'float', 'double', 'sqrt((10.0))'],

            /** @link https://learn.microsoft.com/en-us/sql/t-sql/functions/stdev-transact-sql?view=sql-server-ver16 */
            //['Mystdev', 'numeric', 'double', 'stdev(10.0)'],
        ];
    }
}
