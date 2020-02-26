<?php

namespace App\Console\Commands\RemoteDisks;

use App\Classes\Helpers;
use App\Models\RemoteDisks;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Class AddCommand
 * @package App\Console\Commands\RemoteDisks
 */
class AddCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'remote_disk:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command add new remote disk to list';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'remote_disk:add
                            {--driver= : Disk driver. Available: s3|ftp|sftp|rackspace}
                            {--name= : Unique name of disk}
                            {--key= : Disk Access Key. Driver: S3/RockSpace}
                            {--url=null : Disk url. Driver: S3}
                            {--secret= : Disk Secret Access Key. Driver: S3}
                            {--region= : Disk region. Driver: S3/RockSpace}
                            {--bucket= : Disk bucket name. Driver: S3}
                            {--path= : Disk bucket custom path. Driver: S3}
                            {--host= : Host/IP of disk. Driver: FTP/SFTP}
                            {--port=21 : Disk port. Driver: FTP/SFTP}
                            {--username= : Disk user username. Driver: FTP/SFTP/RockSpace}
                            {--password= : Disk user password. Driver: FTP/SFTP}
                            {--root= : Disk default path for *.CSV search. Driver: FTP/SFTP}
                            {--private_key=null : Disk SSH private key. Driver: FTP/SFTP}
                            {--container= : Disk container name. Driver: RockSpace}
                            {--endpoint= : Disk container endpoint url. Driver: RockSpace}
                            {--url_type= : Disk container endpoint url type. Driver: RockSpace}';

    private $drivers = [
        's3', 'ftp', 'sftp', 'rackspace',
    ];

    /** @var string */
    private $diskName;

    /** @var string */
    private $diskDriver;

    /** @var array */
    private $conn = [];

    /** @var bool */
    private $error = false;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->setOptions();
        $this->validateOptions();

        if ($this->error) {
            return;
        }

        if ($this->checkIsAvailable()) {
            $bucket = new RemoteDisks();
            $bucket->driver = $this->diskDriver;
            $bucket->name = $this->diskName;
            $bucket->disk_connection = $this->conn;

            if ($bucket->save()) {
                $this->info('Disk successfully added!');
                return;
            }

            $this->error('Whoops! Something went wrong. Disk is not saved.');
            return;
        }

        $this->error('Disk is not reachable! Check provided data!');
    }

    /**
     * @return bool
     */
    private function checkIsAvailable(): bool
    {
        $this->info('Connection to remote disk and verifying access rights...');

        $this->connectToDiskDriver();

        Storage::disk($this->diskName)->put(Helpers::resolvePath($this->diskName, 'connection.txt'), 'I can perform this action!');

        if (Storage::disk($this->diskName)->exists(Helpers::resolvePath($this->diskName, 'connection.txt'))) {
            Storage::disk($this->diskName)->delete(Helpers::resolvePath($this->diskName, 'connection.txt'));
            return true;
        }

        $this->error('Connection to remote disk is failed!');
        return false;
    }

    /**
     * @return void
     */
    private function connectToDiskDriver(): void
    {
        try {
            Helpers::setFileSystemDisk(
                $this->diskName,
                $this->diskDriver,
                $this->conn
            );
        } catch (\Exception $e) {
            $this->error('Set disk driver exception: '.$e->getMessage());
            exit;
        }
    }

    /**
     * @return void
     */
    private function validateOptions(): void
    {
        if (!$this->diskDriver || !in_array($this->diskDriver, $this->drivers)) {
            $this->error = true;
            $this->error('Disk driver is not supported. Supported drivers: '.implode(', ', $this->drivers));
        }

        if (!$this->diskName) {
            $this->error = true;
            $this->error('Disk name can not be empty!');
        } else {
            $disk = RemoteDisks::where('name', $this->diskName)->first();

            if ($disk) {
                $this->error = true;
                $this->error('Disk name should be unique!');
            }
        }

        switch ($this->diskDriver) {
            case 's3':
                if (empty($this->conn['key'])) {
                    $this->error = true;
                    $this->error('S3 Bucket Access Key can not be empty!');
                }

                if (empty($this->conn['secret'])) {
                    $this->error = true;
                    $this->error('S3 Bucket Secret Access Key can not be empty!');
                }

                if (empty($this->conn['region'])) {
                    $this->error = true;
                    $this->error('S3 Bucket Region can not be empty!');
                }

                if (empty($this->conn['bucket'])) {
                    $this->error = true;
                    $this->error('S3 Bucket name can not be empty!');
                }
                break;
            case 'ftp':
            case 'sftp':
                if (empty($this->conn['host'])) {
                    $this->error = true;
                    $this->error('FTP/SFTP host can not be empty!');
                }

                if (empty($this->conn['username'])) {
                    $this->error = true;
                    $this->error('FTP/SFTP username can not be empty!');
                }

                if (empty($this->conn['password'])) {
                    $this->error = true;
                    $this->error('FTP/SFTP password can not be empty!');
                }

                if (empty($this->conn['port']) || !is_numeric($this->conn['port'])) {
                    $this->error = true;
                    $this->error('FTP/SFTP port can not be empty and should be numeric!');
                }

                if (empty($this->conn['root'])) {
                    $this->error = true;
                    $this->error('FTP/SFTP root path can not be empty!');
                }

                break;
            case 'rackspace':
                // TODO: support 'rackspace' filesystem disk
                break;
        }
    }

    /**
     * @return void
     */
    private function setOptions(): void
    {
        $this->diskDriver = $this->option('driver');
        $this->diskName = $this->option('name');

        if (!$this->diskDriver) {
            $this->diskDriver = $this->choice('What driver will you use?', $this->drivers);
        }

        if (!$this->diskName) {
            $this->diskName = $this->ask('Name your disk driver?');
        }

        switch ($this->diskDriver) {
            case 's3':
                $this->info('Set up AWS S3 Bucket disk drive');
                $this->conn['key'] = $this->option('key');
                $this->conn['secret'] = $this->option('secret');
                $this->conn['region'] = $this->option('region');
                $this->conn['bucket'] = $this->option('bucket');
                $this->conn['path'] = $this->option('path');

                if (!$this->conn['key']) {
                    $this->conn['key'] = $this->ask('Bucket Access Key?');
                }

                if (!$this->conn['secret']) {
                    $this->conn['secret'] = $this->ask('Bucket Secret Access Key?');
                }

                if (!$this->conn['region']) {
                    $this->conn['region'] = $this->ask('Bucket region?');
                }

                if (!$this->conn['bucket']) {
                    $this->conn['bucket'] = $this->ask('Bucket name?');
                }

                if (!$this->conn['path']) {
                    $this->conn['path'] = $this->ask('Bucket custom path?');
                }

                $this->conn['url'] = $this->option('url');
                break;
            case 'ftp':
            case 'sftp':
                $this->info('Set up FTP/SFTP disk drive');
                $this->conn['host'] = $this->option('host');
                $this->conn['username'] = $this->option('username');
                $this->conn['password'] = $this->option('password');
                $this->conn['port'] = $this->option('port');
                $this->conn['root'] = $this->option('root');

                if (!$this->conn['host']) {
                    $this->conn['host'] = $this->ask('Disk drive host/IP?');
                }

                if (!$this->conn['username']) {
                    $this->conn['username'] = $this->ask('Disk drive username?');
                }

                if (!$this->conn['password']) {
                    $this->conn['password'] = $this->ask('Disk drive password?');
                }

                if (!$this->conn['port']) {
                    $this->conn['port'] = $this->ask('Disk drive port?');
                }

                if (!$this->conn['root']) {
                    $this->conn['root'] = $this->ask('Disk drive root path?');
                }

                $this->conn['privateKey'] = $this->option('private_key');

                break;
            case 'rackspace':
                // TODO: support 'rackspace' filesystem disk
                break;
        }
    }
}
