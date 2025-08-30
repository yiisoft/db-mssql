<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql\Column;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Schema\Column\BinaryColumn as BaseBinaryColumn;
use Yiisoft\Db\Schema\Data\StringableStream;

use function bin2hex;
use function is_string;

final class BinaryColumn extends BaseBinaryColumn
{
    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getDbType() === 'varbinary') {
            if ($value instanceof Param) {
                $value = $value->value;
            } elseif ($value instanceof StringableStream) {
                $value = $value->getValue();
            }

            if (is_string($value)) {
                return new Expression('CONVERT(VARBINARY(MAX), ' . ('0x' . bin2hex($value)) . ')');
            }
        }

        return parent::dbTypecast($value);
    }
}
