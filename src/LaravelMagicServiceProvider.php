<?php

namespace Dominiquevienne\LaravelMagic;

use Dominiquevienne\LaravelMagic\Commands\MakeFilter;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelMagicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-magic')
            ->hasCommands([MakeFilter::class])
            ->hasMigrations([
                'create_statistics_table',
            ]);
    }
}
