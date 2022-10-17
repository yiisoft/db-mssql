<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractQueryCacheTest;

/**
 * @group mssql
 */
final class QueryCacheTest extends AbstractQueryCacheTest
{
    use TestTrait;
}
