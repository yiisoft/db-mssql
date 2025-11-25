<?php

declare(strict_types=1);

use Yiisoft\Db\Mssql\Column\ColumnDefinitionParser;
use Yiisoft\Db\Syntax\ColumnDefinitionParserInterface;
use Yiisoft\Db\Tests\Common\CommonColumnDefinitionParserTest;

/**
 * @group mssql
 */
final class ColumnDefinitionParserTest extends CommonColumnDefinitionParserTest
{
    protected function createColumnDefinitionParser(): ColumnDefinitionParserInterface
    {
        return new ColumnDefinitionParser();
    }
}
