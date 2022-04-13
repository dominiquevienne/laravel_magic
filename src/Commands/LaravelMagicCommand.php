<?php

namespace Dominiquevienne\LaravelMagic\Commands;

use Illuminate\Console\Command;

class LaravelMagicCommand extends Command
{
    public $signature = 'laravel-magic';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
