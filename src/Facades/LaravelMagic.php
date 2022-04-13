<?php

namespace Dominiquevienne\LaravelMagic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dominiquevienne\LaravelMagic\LaravelMagic
 */
class LaravelMagic extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-magic';
    }
}
