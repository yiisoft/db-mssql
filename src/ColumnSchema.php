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
            /**
             * convert from MSSQL column_default format, e.g. ('1') -> 1, ('string') -> string
             * exclude cases for functions as default value. Example: (getdate())
             */
            $offset = ($value[1]==='\'' && $value[1]===$value[-2]) ? 2 : 1;
            $value = substr(substr((string) $value, $offset), 0, -$offset);
        }

        return $this->phpTypecast($value);
    }

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
