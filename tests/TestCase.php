<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use PHPUnit\Framework\TestCase as AbstractTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Db\Connection\Connection;
use Yiisoft\Db\Factory\DatabaseFactory;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Mssql\Connection\MssqlConnection;
use Yiisoft\Db\Mssql\Helper\MssqlDsn;
use Yiisoft\Db\TestUtility\IsOneOfAssert;
use Yiisoft\Di\Container;
use Yiisoft\Files\FileHelper;
use Yiisoft\Log\Logger;
use Yiisoft\Profiler\Profiler;

class TestCase extends AbstractTestCase
{
    protected Aliases $aliases;
    protected CacheInterface $cache;
    protected ContainerInterface $container;
    protected LoggerInterface $logger;
    protected MssqlDsn $mssqlDsn;
    protected MssqlConnection $mssqlConnection;
    protected Profiler $profiler;
    protected array $dataProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configContainer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->aliases,
            $this->cache,
            $this->container,
            $this->logger,
            $this->mssqlDsn,
            $this->mssqlConnection,
            $this->profiler
        );
    }
    /**
     * Asserting two strings equality ignoring line endings.
     * @param string $expected
     * @param string $actual
     * @param string $message
     *
     * @return void
     */
    protected function assertEqualsWithoutLE(string $expected, string $actual, string $message = ''): void
    {
        $expected = str_replace("\r\n", "\n", $expected);
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertEquals($expected, $actual, $message);
    }

    /**
     * Asserts that value is one of expected values.
     *
     * @param mixed $actual
     * @param array $expected
     * @param string $message
     */
    protected function assertIsOneOf($actual, array $expected, $message = ''): void
    {
        self::assertThat($actual, new IsOneOfAssert($expected), $message);
    }

    protected function configContainer(): void
    {
        $this->container = new Container($this->config());

        $this->aliases = $this->container->get(Aliases::class);
        $this->cache = $this->container->get(CacheInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);
        $this->profiler = $this->container->get(Profiler::class);
        $this->mssqlDsn = $this->container->get(MssqlDsn::class);
        $this->mssqlConnection = $this->container->get(Connection::class);

        DatabaseFactory::initialize($this->container, []);
    }

    protected function createDsn(): string
    {

    }

    /**
     * Invokes a inaccessible method.
     *
     * @param $object
     * @param $method
     * @param array $args
     * @param bool $revoke whether to make method inaccessible after execution.
     *
     * @return mixed
     */
    protected function invokeMethod($object, $method, $args = [], $revoke = true)
    {
        $reflection = new \ReflectionObject($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        $result = $method->invokeArgs($object, $args);
        if ($revoke) {
            $method->setAccessible(false);
        }
        return $result;
    }

    /**
     * @param  bool $reset whether to clean up the test database.
     * @param  bool $open  whether to open and populate test database.
     *
     * @return \Yiisoft\Db\Mssql\Connection\MssqlConnection
     */
    protected function getConnection($reset = false): MssqlConnection
    {
        if ($reset === false && isset($this->mssqlConnection)) {
            return $this->mssqlConnection;
        } elseif ($reset === false) {
            $this->configContainer();
            return $this->mssqlConnection;
        }

        try {
            $this->prepareDatabase();
        } catch (\Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->mssqlConnection;
    }

    protected function prepareDatabase(): void
    {
        $fixture = $this->params()['yiisoft/db-mssql']['fixture'];

        $this->mssqlConnection->open();

        if ($fixture !== null) {
            $lines = explode(';', file_get_contents($fixture));

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $this->mssqlConnection->getPDO()->exec($line);
                }
            }
        }
    }

    /**
     * Gets an inaccessible object property.
     *
     * @param object $object
     * @param string $propertyName
     * @param bool $revoke whether to make property inaccessible after getting.
     *
     * @return mixed
     */
    protected function getInaccessibleProperty(object $object, string $propertyName, bool $revoke = true)
    {
        $class = new \ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $result = $property->getValue($object);

        if ($revoke) {
            $property->setAccessible(false);
        }

        return $result;
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param string $sql
     *
     * @return string
     */
    protected function replaceQuotes(string $sql): string
    {
        return str_replace(['[[', ']]'], ['[', ']'], $sql);
    }

    /**
     * Sets an inaccessible object property to a designated value.
     * @param object $object
     * @param string $propertyName
     * @param $value
     * @param bool $revoke whether to make property inaccessible after setting
     */
    protected function setInaccessibleProperty(object $object, string $propertyName, $value, bool $revoke = true): void
    {
        $class = new \ReflectionClass($object);

        while (!$class->hasProperty($propertyName)) {
            $class = $class->getParentClass();
        }

        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);

        if ($revoke) {
            $property->setAccessible(false);
        }
    }

    private function config(): array
    {
        $params = $this->params();

        return [
            ContainerInterface::class => static function (ContainerInterface $container) {
                return $container;
            },

            Aliases::class => [
                '@root' => dirname(__DIR__, 1),
                '@data' =>  '@root/tests/data',
                '@runtime' => '@data/runtime',
            ],

            CacheInterface::class => static function () {
                return new Cache(new ArrayCache());
            },

            FileRotatorInterface::class => static function () {
                return new FileRotator(10);
            },

            LoggerInterface::class => Logger::class,

            Profiler::class => static function (ContainerInterface $container) {
                return new Profiler($container->get(LoggerInterface::class));
            },

            MssqlDsn::class => static function (ContainerInterface $container) use ($params) {
                return new MssqlDsn(
                    $params['yiisoft/db-mssql']['dsn']['driver'],
                    $params['yiisoft/db-mssql']['dsn']['server'],
                    $params['yiisoft/db-mssql']['dsn']['database'],
                    $params['yiisoft/db-mssql']['dsn']['port'],
                );
            },

            Connection::class  => static function (ContainerInterface $container) use ($params) {
                $connection = new MssqlConnection(
                    $container->get(CacheInterface::class),
                    $container->get(LoggerInterface::class),
                    $container->get(Profiler::class),
                    $container->get(MssqlDsn::class)->getDsn(),
                );

                $connection->setUsername($params['yiisoft/db-mssql']['username']);
                $connection->setPassword($params['yiisoft/db-mssql']['password']);

                return $connection;
            }
        ];
    }

    private function params(): array
    {
        return [
            'yiisoft/db-mssql' => [
                'dsn' => [
                    'driver' => 'sqlsrv',
                    'server' => '127.0.0.1',
                    'database' => 'yiitest',
                    'port' => '1433'
                ],
                'username' => 'SA',
                'password' => 'YourStrong!Passw0rd',
                'fixture' => __DIR__ . '/Data/mssql.sql',
            ]
        ];
    }
}
