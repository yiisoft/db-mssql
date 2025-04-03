<?php

declare(strict_types=1);

namespace Yiisoft\Db\Mssql;

/**
 * Defines the available index methods for {@see DDLQueryBuilder::createIndex()} method.
 */
final class IndexMethod
{
    /**
     * Define the method of the index as `GEOMETRY_GRID`.
     */
    public const GEOMETRY_GRID = 'GEOMETRY_GRID';
    /**
     * Define the method of the index as `GEOMETRY_AUTO_GRID`.
     */
    public const GEOMETRY_AUTO_GRID = 'GEOMETRY_AUTO_GRID';
    /**
     * Define the method of the index as `GEOGRAPHY_GRID`.
     */
    public const GEOGRAPHY_GRID = 'GEOGRAPHY_GRID';
    /**
     * Define the method of the index as `GEOGRAPHY_AUTO_GRID`.
     */
    public const GEOGRAPHY_AUTO_GRID = 'GEOGRAPHY_AUTO_GRID';
}
