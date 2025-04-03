<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

/**
 * Defines the available index types for {@see DDLQueryBuilder::createIndex()} method.
 */
final class IndexType
{
    /**
     * Define the type of the index as `CLUSTERED`.
     */
    public const CLUSTERED = 'CLUSTERED';
    /**
     * Define the type of the index as `CLUSTERED COLUMNSTORE`.
     */
    public const CLUSTERED_COLUMNSTORE = 'CLUSTERED COLUMNSTORE';
    /**
     * Define the type of the index as `COLUMNSTORE`.
     */
    public const COLUMNSTORE = 'COLUMNSTORE';
    /**
     * Define the type of the index as `NONCLUSTERED`.
     */
    public const NONCLUSTERED = 'NONCLUSTERED';
    /**
     * Define the type of the index as `PRIMARY XML`.
     */
    public const PRIMARY_XML = 'PRIMARY XML';
    /**
     * Define the type of the index as `SPATIAL`.
     */
    public const SPATIAL = 'SPATIAL';
    /**
     * Define the type of the index as `UNIQUE`.
     */
    public const UNIQUE = 'UNIQUE';
    /**
     * Define the type of the index as `UNIQUE CLUSTERED`.
     */
    public const UNIQUE_CLUSTERED = 'UNIQUE CLUSTERED';
    /**
     * Define the type of the index as `XML`.
     */
    public const XML = 'XML';
}
