<?php

namespace Dominiquevienne\LaravelMagic\Commands;

use Illuminate\Console\Command;

class MakeFilter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filter:make {modelName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Makes a filter used by LaravelMagic in-app filtering';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $directory = getcwd() . '/app/Http/Filters';
        $filterName = $this->argument('modelName') . 'Filter';

        if (!is_dir($directory)) {
            mkdir($directory);
            echo 'Directory ' . $directory . ' created' . "\n";
        }

        $stubFilename = __DIR__ . '/stubs/SampleFilter.php.stub';
        $stubContent = file_get_contents($stubFilename);
        $classContent = str_replace('SampleFilter', $filterName, $stubContent);
        file_put_contents($directory . DIRECTORY_SEPARATOR . $filterName . '.php', $classContent);
        echo $filterName . ' successfully created' . "\n";

        return 0;
    }
}
