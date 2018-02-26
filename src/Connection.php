<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mssql;

/**
 * Database connection class prefilled for MSSQL Server.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class Connection extends \yii\db\Connection
{
    /**
     * {@inheritdoc}
     */
    public $schemaMap = [
        'sqlsrv' => Schema::class, // newer MSSQL driver on MS Windows hosts
        'mssql' => Schema::class, // older MSSQL driver on MS Windows hosts
        'dblib' => Schema::class, // dblib drivers on GNU/Linux (and maybe other OSes) hosts
    ];
    /**
     * {@inheritdoc}
     */
    public $pdoClass = PDO::class;
}