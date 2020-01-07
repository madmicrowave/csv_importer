<?php

namespace App\Console\Commands\Bucket;

use Illuminate\Console\Command;

/**
 * Class ImportCommand
 * @package App\Console\Commands\Bucket
 */
class ImportCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bucket:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command import csv files from all/specific buckets';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'bucket:import';

    /**
     * Execute the console command.
     */
    public function handle()
    {

    }
}
