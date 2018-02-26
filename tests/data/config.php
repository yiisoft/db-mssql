<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * This is the configuration file for the Yii 2 unit tests.
 *
 * You can override configuration values by creating a `config.local.php` file
 * and manipulate the `$config` variable.
 * For example to change MSSQL username and password your `config.local.php` should
 * contain the following:
 *
 * ```php
 * <?php
 * $config['databases']['sqlsrv']['username'] = 'yiitest';
 * $config['databases']['sqlsrv']['password'] = 'changeme';
 * ```
 */
$config = [
    'databases' => [
        'sqlsrv' => [
            'dsn' => 'sqlsrv:Server=localhost;Database=test',
            'username' => '',
            'password' => '',
            'fixture' => __DIR__ . '/mssql.sql',
        ],
    ],
];

if (is_file(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}

return $config;
