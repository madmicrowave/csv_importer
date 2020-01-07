<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportCsv extends Command
{
    /**
     * @var string
     */
    protected $signature = 'import:csv';

    /**
     * @var string
     */
    protected $description = 'Import CSV files to db, specifying path or csv bucket';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        return true;
    }
}
