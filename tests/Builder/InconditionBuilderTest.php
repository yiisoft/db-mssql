<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Builder\InBuilder;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\In;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class InconditionBuilderTest extends TestCase
{
    use TestTrait;

    public function testBuildSubqueryInCondition(): void
    {
        $db = $this->getConnection();
        $inCondition = new In(
            ['id'],
            'in',
            (new Query($db))->select('id')->from('users')->where(['active' => 1]),
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mssql\Builder\InBuilder::buildSubqueryInCondition is not supported by MSSQL.'
        );

        (new InBuilder($db->getQueryBuilder()))->build($inCondition);
    }
}
