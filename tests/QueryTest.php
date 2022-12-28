<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Common\CommonQueryTest;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class QueryTest extends CommonQueryTest
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testUnion(): void
    {
        $db = $this->getConnection();

        $subQueryFromItem = (new Query($db))->select(['id', 'name'])->from('item')->limit(2);
        $subQueryFromCategory = (new Query($db))->select(['id', 'name'])->from(['category'])->limit(2);
        $subQueryUnion = (new Query($db))->select(['id', 'name'])->from($subQueryFromCategory);

        $query = (new Query($db))->select(['id', 'name'])->from($subQueryFromItem)->union($subQueryUnion);
        $data = $query->all();

        $this->assertCount(4, $data);
    }
}
