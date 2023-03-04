<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\BatchQueryResult;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Common\CommonBatchQueryResultTest;

/**
 * @group mssql
 */
final class BatchQueryResultTest extends CommonBatchQueryResultTest
{
    use TestTrait;

    public function testBatchQueryResultWithoutPopulate(): void
    {
        $db = $this->getConnection(true);

        $query = new Query($db);
        $query->from('customer')->orderBy('id')->limit(3)->indexBy('id');

        $batchQueryResult = new BatchQueryResult($query);

        $customers = $this->getAllRowsFromBatch($batchQueryResult);

        $this->assertCount(3, $customers);
        $this->assertEquals('user1', $customers[0]['name']);
        $this->assertEquals('user2', $customers[1]['name']);
        $this->assertEquals('user3', $customers[2]['name']);
    }
}
