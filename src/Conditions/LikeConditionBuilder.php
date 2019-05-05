<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Db\Mssql\Conditions;

/**
 * {@inheritdoc}
 * @since 1.0
 */
class LikeConditionBuilder extends \Yiisoft\Db\Conditions\LikeConditionBuilder
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
