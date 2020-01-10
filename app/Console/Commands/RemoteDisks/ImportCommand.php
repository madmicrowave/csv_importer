<?php

namespace App\Console\Commands\RemoteDisks;

use App\Models\RemoteDisks;
use App\Models\ImportHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Class ImportCommand
 * @package App\Console\Commands\RemoteDisks
 */
class ImportCommand extends Command
{
    const SUPPORTED_FILES_EXT = ['csv'];
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'remote_disk:import';

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
    protected $signature = 'remote_disk:import
                            {--disk_name= : Specify specific disk name}
                            {--file_path= : Specify specific file path witch should be re-imported}';

    /** @var RemoteDisks */
    private $activeDisk;

    /** @var float */
    private $fileStartTime;

    /** @var float */
    private $fileElapsedTime;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        RemoteDisks::where('status', RemoteDisks::STATUS_ACTIVE)->chunk(20, function($disks) {
            foreach ($disks as $disk) {
                $this->activeDisk = $disk->setConfig();
                $this->info(
                    sprintf('Connected to: %s (%s)', $disk->name, strtoupper($disk->driver))
                );

                foreach (Storage::disk($disk->name)->allFiles() as $file) {
                    $this->fileStartTime = microtime(true);
                    $this->importFile($file);
                }
            }
        });
    }

    /**
     * @param string $filePath
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function importFile(string $filePath): void
    {
        if (!$this->isFileSupported($filePath)) {
            $this->line('File type is not supported - skip');
            return;
        }

        $fileHistory = ImportHistory::where('file_path', $filePath)->get();

        if ($fileHistory) {
            $lastModified = Storage::disk($this->activeDisk->name)->lastModified($filePath);

            if ($lastModified != $fileHistory->file_modification_time) {
                $this->performFileImport($filePath, $fileHistory);

                $this->info(
                    sprintf('File "%s" is updated. Reason: file modified', $filePath)
                );
                return;
            }

            $this->line(
                sprintf('File "%s" already imported and no changes detected', $filePath)
            );
            return;
        }

        $this->performFileImport($filePath, new ImportHistory());
    }

    /**
     * @param string $filePath
     * @param ImportHistory $importHistory
     * @return bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function performFileImport(string $filePath, ImportHistory $importHistory): bool
    {
        $this->line(
            sprintf('File "%s" import start', $filePath)
        );

        $fileContents = Storage::disk($this->activeDisk->name)->get($filePath);



        $this->setTimeElapsed();

        $importHistory->remote_disk = $this->activeDisk->name;
        $importHistory->remote_disks_id = $this->activeDisk->id;
        $importHistory->file_name = basename($filePath);
        $importHistory->file_path = $filePath;
        $importHistory->file_size = Storage::disk($this->activeDisk->name)->size($filePath);
        $importHistory->file_processing_time = $this->fileElapsedTime;
        $importHistory->file_modification_time = Storage::disk($this->activeDisk->name)->lastModified($filePath);
        $importHistory->records_in_file = ''; // TODO?
        $importHistory->last_error = ''; // TODO?
        $importHistory->save();

        $this->line(
            sprintf('File "%s" imported', $filePath)
        );
        return true;
    }

    /**
     * @param string $filePath
     * @return bool
     */
    private function isFileSupported(string $filePath): bool
    {
        if (in_array(pathinfo($filePath, PATHINFO_EXTENSION), self::SUPPORTED_FILES_EXT)) {
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    private function setTimeElapsed(): void
    {
        $this->fileElapsedTime = microtime(true) - $this->fileStartTime;
    }
}
