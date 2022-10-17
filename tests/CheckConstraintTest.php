<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Tests\AbstractCheckConstraintTest;
use Yiisoft\Db\Mssql\Tests\Support\TestTrait;

/**
 * @group mssql
 */
final class CheckConstraintTest extends AbstractCheckConstraintTest
{
    use TestTrait;
}
