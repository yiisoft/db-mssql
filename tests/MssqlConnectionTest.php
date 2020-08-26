<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Mssql\Connection\MssqlConnection;
use Yiisoft\Db\Mssql\Helper\MssqlDsn;
use Yiisoft\Db\TestUtility\TestConnectionTrait;

/**
 * @group mssql
 */
final class MssqlConnectionTest extends TestCase
{
    use TestConnectionTrait;

    public function testConstruct(): void
    {
        $connection = $this->getConnection();

        $this->assertEquals($this->cache, $connection->getSchemaCache());
        $this->assertEquals($this->logger, $connection->getLogger());
        $this->assertEquals($this->profiler, $connection->getProfiler());
        $this->assertEquals($this->mssqlDsn->getDsn(), $connection->getDsn());
    }

    public function testGetDriverName(): void
    {
        $connection = $this->getConnection();

        $this->assertEquals($this->mssqlDsn->getDriver(), $connection->getDriverName());
    }

    public function testQuoteValue(): void
    {
        $connection = $this->getConnection();

        $this->assertEquals(123, $connection->quoteValue(123));
        $this->assertEquals("'string'", $connection->quoteValue('string'));
        $this->assertEquals("'It''s interesting'", $connection->quoteValue("It's interesting"));
    }

    public function testQuoteTableName()
    {
        $connection = $this->getConnection();

        $this->assertEquals('[table]', $connection->quoteTableName('table'));
        $this->assertEquals('[table]', $connection->quoteTableName('[table]'));
        $this->assertEquals('[schema].[table]', $connection->quoteTableName('schema.table'));
        $this->assertEquals('[schema].[table]', $connection->quoteTableName('schema.[table]'));
        $this->assertEquals('[schema].[table]', $connection->quoteTableName('[schema].[table]'));
        $this->assertEquals('{{table}}', $connection->quoteTableName('{{table}}'));
        $this->assertEquals('(table)', $connection->quoteTableName('(table)'));
    }

    public function testQuoteColumnName(): void
    {
        $connection = $this->getConnection(false);

        $this->assertEquals('[column]', $connection->quoteColumnName('column'));
        $this->assertEquals('[column]', $connection->quoteColumnName('[column]'));
        $this->assertEquals('[[column]]', $connection->quoteColumnName('[[column]]'));
        $this->assertEquals('{{column}}', $connection->quoteColumnName('{{column}}'));
        $this->assertEquals('(column)', $connection->quoteColumnName('(column)'));
        $this->assertEquals('[column]', $connection->quoteSql('[[column]]'));
        $this->assertEquals('[column]', $connection->quoteSql('{{column}}'));
    }

    public function testQuoteFullColumnName(): void
    {
        $connection = $this->getConnection();

        $this->assertEquals('[table].[column]', $connection->quoteColumnName('table.column'));
        $this->assertEquals('[table].[column]', $connection->quoteColumnName('table.[column]'));
        $this->assertEquals('[table].[column]', $connection->quoteColumnName('[table].column'));
        $this->assertEquals('[table].[column]', $connection->quoteColumnName('[table].[column]'));
        $this->assertEquals('[[table.column]]', $connection->quoteColumnName('[[table.column]]'));
        $this->assertEquals('{{table}}.[column]', $connection->quoteColumnName('{{table}}.column'));
        $this->assertEquals('{{table}}.[column]', $connection->quoteColumnName('{{table}}.[column]'));
        $this->assertEquals('{{table}}.[[column]]', $connection->quoteColumnName('{{table}}.[[column]]'));
        $this->assertEquals('{{%table}}.[column]', $connection->quoteColumnName('{{%table}}.column'));
        $this->assertEquals('{{%table}}.[column]', $connection->quoteColumnName('{{%table}}.[column]'));
        $this->assertEquals('[column.name]', $connection->quoteColumnName('[column.name]'));
        $this->assertEquals(
            '[column.name.with.dots]',
            $connection->quoteColumnName('[column.name.with.dots]')
        );
        $this->assertEquals(
            '[table].[column.name.with.dots]',
            $connection->quoteColumnName('[table].[column.name.with.dots]')
        );
        $this->assertEquals('[table].[column]', $connection->quoteSql('[[table.column]]'));
        $this->assertEquals('[table].[column]', $connection->quoteSql('{{table}}.[[column]]'));
        $this->assertEquals('[table].[column]', $connection->quoteSql('{{table}}.[column]'));
        $this->assertEquals('[table].[column]', $connection->quoteSql('{{%table}}.[[column]]'));
        $this->assertEquals('[table].[column]', $connection->quoteSql('{{%table}}.[column]'));
    }

    /**
     * Test whether slave connection is recovered when call getSlavePdo() after close().
     *
     * @see https://github.com/yiisoft/yii2/issues/14165
     */
    public function testGetPdoAfterClose(): void
    {
        $connection = $this->getConnection();

        $connection->setSlaves(
            '1',
            [
                '__class' => MssqlConnection::class,
                '__construct()' => [
                    $this->cache,
                    $this->logger,
                    $this->profiler,
                    $this->mssqlDsn->getDsn()
                ],
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()]
            ]
        );

        $this->assertNotNull($connection->getSlavePdo(false));

        $connection->close();

        $masterPdo = $connection->getMasterPdo();
        $this->assertNotFalse($masterPdo);
        $this->assertNotNull($masterPdo);

        $slavePdo = $connection->getSlavePdo(false);
        $this->assertNotFalse($slavePdo);
        $this->assertNotNull($slavePdo);
        $this->assertNotSame($masterPdo, $slavePdo);
    }

    public function testServerStatusCacheWorks(): void
    {
        $connection = $this->getConnection();

        $connection->setMasters(
            '1',
            [
                '__class' => MssqlConnection::class,
                '__construct()' => [
                    $this->cache,
                    $this->logger,
                    $this->profiler,
                    $this->mssqlDsn->getDsn()
                ],
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()]
            ]
        );

        $connection->setShuffleMasters(false);

        $cacheKey = ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', $connection->getDsn()];

        $this->assertFalse($this->cache->has($cacheKey));

        $connection->open();

        $this->assertFalse(
            $this->cache->has($cacheKey),
            'Connection was successful – cache must not contain information about this DSN'
        );

        $connection->close();

        $connection = $this->getConnection();

        $cacheKey = ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid'];

        $connection->setMasters(
            '1',
            [
                '__class' => MssqlConnection::class,
                '__construct()' => [
                    $this->cache,
                    $this->logger,
                    $this->profiler,
                    'host:invalid'
                ],
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()]
            ]
        );

        $connection->setShuffleMasters(true);

        try {
            $connection->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertTrue(
            $this->cache->has($cacheKey),
            'Connection was not successful – cache must contain information about this DSN'
        );

        $connection->close();
    }

    public function testServerStatusCacheCanBeDisabled(): void
    {
        $this->cache->clear();

        $connection = $this->getConnection();

        $connection->setMasters(
            '1',
            [
                '__class' => MssqlConnection::class,
                '__construct()' => [
                    $this->cache,
                    $this->logger,
                    $this->profiler,
                    $this->mssqlDsn->getDsn()
                ],
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()]
            ]
        );

        $connection->setSchemaCache(null);

        $connection->setShuffleMasters(false);

        $cacheKey = ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', $connection->getDsn()];

        $this->assertFalse($this->cache->has($cacheKey));

        $connection->open();

        $this->assertFalse($this->cache->has($cacheKey), 'Caching is disabled');

        $connection->close();

        $cacheKey = ['Yiisoft\Db\Connection\Connection::openFromPoolSequentially', 'host:invalid'];

        $connection->setMasters(
            '1',
            [
                '__class' => MssqlConnection::class,
                '__construct()' => [
                    $this->cache,
                    $this->logger,
                    $this->profiler,
                    'host:invalid'
                ],
                'setUsername()' => [$connection->getUsername()],
                'setPassword()' => [$connection->getPassword()]
            ]
        );

        try {
            $connection->open();
        } catch (InvalidConfigException $e) {
        }

        $this->assertFalse($this->cache->has($cacheKey), 'Caching is disabled');

        $connection->close();
    }
}
