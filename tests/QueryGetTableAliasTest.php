<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests;

use Yiisoft\Db\Mssql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Tests\Common\CommonQueryGetTableAliasTest;

/**
 * @group mssql
 */
final class QueryGetTableAliasTest extends CommonQueryGetTableAliasTest
{
    use IntegrationTestTrait;
}
