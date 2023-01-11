<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\AbstractColumnSchema;
use Yiisoft\Db\Schema\Schema;

use function bin2hex;
use function is_string;
use function substr;

/**
 * Class ColumnSchema for MSSQL database
 */
final class ColumnSchema extends AbstractColumnSchema
{
    /**
     * Prepares default value and converts it according to {@see phpType}.
     *
     * @param mixed $value default value
     *
     * @return mixed converted value
     */
    public function defaultPhpTypecast(mixed $value): mixed
    {
        if ($value !== null) {
            /** convert from MSSQL column_default format, e.g. ('1') -> 1, ('string') -> string */
            $value = substr(substr((string) $value, 2), 0, -2);
        }

        return $this->phpTypecast($value);
    }

    public function dbTypecast(mixed $value): mixed
    {
        if ($this->getType() === Schema::TYPE_BINARY && $this->getDbType() === 'varbinary') {
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
