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
     * Ensure no ambiguous column error occurs on indexBy with JOIN.
     *
     * @link https://github.com/yiisoft/yii2/issues/13859
     */
    public function testAmbiguousColumnIndexBy(): void
    {
        $db = $this->getConnection(true);

        $selectExpression = 'CONCAT(customer.name, \' in \', p.description) name';

        $result = (new Query($db))
            ->select([$selectExpression])
            ->from('customer')
            ->innerJoin('profile p', '[[customer]].[[profile_id]] = [[p]].[[id]]')
            ->indexBy('id')
            ->column();

        $this->assertSame([1 => 'user1 in profile customer 1', 3 => 'user3 in profile customer 3'], $result);
    }

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
