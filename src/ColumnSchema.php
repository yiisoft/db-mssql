<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function bin2hex;
use function is_string;
use function substr;

/**
 * Class ColumnSchema for MSSQL database
 */
final class ColumnSchema extends AbstractColumnSchema
{
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getType() === SchemaInterface::TYPE_BINARY && $this->getDbType() === 'varbinary') {
            if ($value instanceof ParamInterface && is_string($value->getValue())) {
                $value = (string) $value->getValue();
            }
            if (is_string($value)) {
                return new Expression('CONVERT(VARBINARY(MAX), ' . ('0x' . bin2hex($value)) . ')');
            }
        }

        return parent::dbTypecast($value);
    }
}
