<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonBatchQueryResultTest;

/**
 * @group mssql
 */
final class BatchQueryResultTest extends CommonBatchQueryResultTest
{
    use TestTrait;
}
