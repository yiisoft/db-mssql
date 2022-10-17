<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Tests\AbstractForeignKeyConstraintTest;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 */
final class ForeignKeyConstraintTest extends AbstractForeignKeyConstraintTest
{
    use TestTrait;
}
