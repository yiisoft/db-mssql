<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Condition;

final class LikeConditionBuilder extends \Yiisoft\Db\Conditions\LikeConditionBuilder
{
    /**
     * {@inheritdoc}
     */
    protected $escapingReplacements = [
        '%' => '[%]',
        '_' => '[_]',
        '[' => '[[]',
        ']' => '[]]',
        '\\' => '[\\]',
    ];
}
