<?php

namespace Dominiquevienne\LaravelMagic\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;

class QueryService
{
    /**
     * Creates a fingerprint used as a cache key
     *
     * @todo Give the opportunity to pass page name parameter
     * @todo Give the opportunity to pass page number
     * @param Builder $query
     * @return string
     */
    public static function makeQueryFingerPrint(Builder $query): string
    {
        $queryString = $query->toSql() . '-' .
            Paginator::resolveCurrentPage() . '.' .
            implode('_', $query->getBindings());

        return Str::slug($queryString);
    }
}
