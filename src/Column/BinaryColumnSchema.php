<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\Column\BinaryColumnSchema as BaseBinaryColumnSchema;

use function bin2hex;
use function is_string;

final class BinaryColumnSchema extends BaseBinaryColumnSchema
{
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getDbType() === 'varbinary') {
            if ($value instanceof ParamInterface && is_string($value->getValue())) {
                /** @psalm-var string */
                $value = $value->getValue();
            }

            if (is_string($value)) {
                return new Expression('CONVERT(VARBINARY(MAX), ' . ('0x' . bin2hex($value)) . ')');
            }
        }

        return parent::dbTypecast($value);
    }
}
