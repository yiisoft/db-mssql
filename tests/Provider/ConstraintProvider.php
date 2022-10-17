<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Tests\Provider;

use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Tests\Provider\AbstractConstraintProvider;
use Yiisoft\Db\Tests\Support\AnyValue;

final class ConstraintProvider extends AbstractConstraintProvider
{
    public function tableConstraints(): array
    {
        $result = $this->getTableConstraints();

        $result['1: check'][2][0]->expression('([C_check]<>\'\')');
        $result['1: default'][2] = [];
        $result['1: default'][2][] = (new DefaultValueConstraint())
            ->name(AnyValue::getInstance())
            ->columnNames(['C_default'])
            ->value('((0))');

        $result['2: default'][2] = [];

        $result['3: foreign key'][2][0]->foreignSchemaName('dbo');
        $result['3: index'][2] = [];
        $result['3: default'][2] = [];
        $result['4: default'][2] = [];

        return $result;
    }
}
