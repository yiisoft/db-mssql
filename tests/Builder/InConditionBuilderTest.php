<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Builder;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Builder\InBuilder;
use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\In;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

/**
 * @group mssql
 */
final class InConditionBuilderTest extends IntegrationTestCase
{
    use IntegrationTestTrait;

    public function testBuildSubqueryInCondition(): void
    {
        $db = $this->getSharedConnection();

        $inCondition = new In(
            ['id'],
            (new Query($db))->select('id')->from('users')->where(['active' => 1]),
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mssql\Builder\InBuilder::buildSubqueryInCondition is not supported by MSSQL.',
        );

        (new InBuilder($db->getQueryBuilder()))->build($inCondition);
    }
}
