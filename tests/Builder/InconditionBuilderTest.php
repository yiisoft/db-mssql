<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Builder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Mssql\Builder\InConditionBuilder;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;

/**
 * @group mssql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class InconditionBuilderTest extends TestCase
{
    use TestTrait;

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function testBuildSubqueryInCondition(): void
    {
        $db = $this->getConnection();
        $inCondition = new InCondition(
            ['id'],
            'in',
            (new Query($db))->select('id')->from('users')->where(['active' => 1]),
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Mssql\Builder\InConditionBuilder::buildSubqueryInCondition is not supported by MSSQL.'
        );

        (new InConditionBuilder($db->getQueryBuilder()))->build($inCondition);
    }
}
