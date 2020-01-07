<?php

namespace App\Console\Commands\Bucket;

use Illuminate\Console\Command;

/**
 * Class AddCommand
 * @package App\Console\Commands\Bucket
 */
class AddCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bucket:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command add new bucket to bucket list';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'bucket:add';

    /**
     * Execute the console command.
     */
    public function handle()
    {

    }
}
