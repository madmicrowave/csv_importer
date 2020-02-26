<?php

namespace App\Console\Commands\RemoteDisks;

use App\Classes\Helpers;
use App\Classes\Import\Exception\InstructionNotFoundOrFailed;
use App\Console\Classes\Import;
use Exception;
use App\Models\RemoteDisks;
use App\Models\ImportHistory;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use League\Flysystem\FileNotFoundException as LeagueFileNotFoundException;
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

                foreach (Storage::disk($disk->name)->allFiles(Helpers::resolvePath($disk->name)) as $file) {
                    $this->fileStartTime = microtime(true);
                    $this->importFile($file);
                }
            }
        });

        if (!$this->fileStartTime) {
            $this->warn('Nothing was imported!');
        }

        $this->info(
            sprintf('Process exited...')
        );
    }

    /**
     * @param string $filePath
     * @return void
     */
    private function importFile(string $filePath): void
    {
        if (!$this->isFileSupported($filePath)) {
            $this->warn('File "'.$filePath.'" type is not supported - skip');
            return;
        }

        $fileHistory = ImportHistory::where('file_path', $filePath)->first();

        if ($fileHistory) {
            try {
                $lastModified = Storage::disk($this->activeDisk->name)->lastModified($filePath);
            } catch (\Exception $e) {
                $lastModified = 0;
            }

            $isFailed = ($fileHistory->status != ImportHistory::STATUS_SUCCESS
                && $fileHistory->attempts <= ImportHistory::ATTEMPTS_LIMIT);
            $isModified = $lastModified != $fileHistory->file_modification_time;

            if ($isFailed || $isModified) {
                $this->info(
                    sprintf('Updating due to: %s', $isFailed ? 'previous import error' : 'file modified')
                );

                $this->performFileImport($filePath, $fileHistory);
                return;
            }

            $this->info(
                sprintf('Skip import "%s" - no changes detected', basename($filePath))
            );
            return;
        }

        $this->performFileImport($filePath, new ImportHistory);
    }

    /**
     * @param string $filePath
     * @param ImportHistory $importHistory
     * @return bool
     */
    private function performFileImport(string $filePath, ImportHistory $importHistory): bool
    {
        $this->line(
            sprintf('File "%s" processing', $filePath)
        );

        try {
            $importResult = (new Import\Process(
                $filePath,
                Storage::disk($this->activeDisk->name)->get($filePath)
            ))->start();

            $fileSize = Storage::disk($this->activeDisk->name)->size($filePath);
            $fileLastModified = Storage::disk($this->activeDisk->name)->lastModified($filePath);
        } catch (FileNotFoundException $e) {
            $this->error(
                sprintf('File "%s". Error: %s', $filePath, 'File not found')
            );
            $importResult['meta'][] = $e->getMessage();
            $importResult['status'] = false;
            $fileSize = 0;
            $fileLastModified = 0;
        } catch (InstructionNotFoundOrFailed $e) {
            $this->info('File skipped! '. $e->getMessage());

            return false;
        }

        $this->setTimeElapsed();

        $importHistory->remote_disk = $this->activeDisk->name;
        $importHistory->remote_disks_id = $this->activeDisk->id;
        $importHistory->file_name = basename($filePath);
        $importHistory->file_path = $filePath;
        $importHistory->file_size = $fileSize;
        $importHistory->file_processing_time = $this->fileElapsedTime;
        $importHistory->file_modification_time = $fileLastModified;
        $importHistory->attempts = $importHistory->status === ImportHistory::STATUS_SUCCESS ? 1 : $importHistory->attempts + 1;
        $importHistory->status = $importResult['status'] ? ImportHistory::STATUS_SUCCESS : ImportHistory::STATUS_FAILED;
        $importHistory->meta = $importResult['meta'] ?? 'no meta data';
        $importHistory->save();

        $this->info(
            sprintf('done. attempts: %s', $importHistory->attempts)
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
