<?php

namespace Dominiquevienne\LaravelMagic\Http\Filters;

use Dominiquevienne\LaravelMagic\Exceptions\ControllerAutomationException;
use Illuminate\Database\Eloquent\Builder;

class GenericFilter
{
    /**
     * @param Builder $query
     * @return Builder
     * @throws ControllerAutomationException
     */
    static public function applyFilter(Builder $query): Builder
    {
        if (strtolower(env('LARAVEL_MAGIC_FILTER_MODE')) === 'paranoid') {
            throw new ControllerAutomationException('Laravel Magic filtering mode is set to paranoid, please create a filter class for your model');
        }

        return $query;
    }
}
