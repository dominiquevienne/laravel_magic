<?php

namespace Dominiquevienne\LaravelMagic;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dominiquevienne\LaravelMagic\Commands\LaravelMagicCommand;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-magic_table')
            ->hasCommand(LaravelMagicCommand::class);
    }
}
