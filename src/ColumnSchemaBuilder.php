<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\AbstractColumnSchemaBuilder;

final class ColumnSchemaBuilder extends AbstractColumnSchemaBuilder
{
    /**
     * Getting the `Default` value for constraint
     *
     * @return Expression|string|null
     */
    public function getDefault(): Expression|string|null
    {
        if ($this->default instanceof Expression) {
            return $this->default;
        }

        return $this->buildDefaultValue();
    }
}
