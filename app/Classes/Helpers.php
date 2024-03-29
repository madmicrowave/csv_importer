<?php

namespace App\Classes;

/**
 * Class Helpers
 * @package App\Classes
 */
class Helpers
{
    /**
     * @param string $diskName
     * @param string $diskDriver
     * @param array $conn
     * @return void
     * @throws \Exception
     */
    public static function setFileSystemDisk(string $diskName, string $diskDriver, array $conn): void
    {
        switch ($diskDriver) {
            case 's3':
                $fileSystem = [
                    'filesystems.disks.'.$diskName => [
                        'driver' => $diskDriver,
                        'key' => $conn['key'],
                        'secret' => $conn['secret'],
                        'region' => $conn['region'],
                        'bucket' => $conn['bucket'],
                        'url' => $conn['url'] ?? '',
                        'path' => $conn['path'] ?? '',
                    ]
                ];
                break;
            case 'ftp':
            case 'sftp':
                $fileSystem = [
                    'filesystems.disks.'.$diskName => [
                        'driver' => $diskDriver,
                        'host' => $conn['host'],
                        'username' => $conn['username'],
                        'password' => $conn['password'],
                        'port' => $conn['port'],
                        'root' => $conn['root'],
                    ]
                ];

                if (!empty($conn['privateKey'])) {
                    $fileSystem['filesystems.disks.'.$diskName]['privateKey'] = $conn['privateKey'];
                }
                break;
            case 'rackspace':
                $fileSystem = [
                    'filesystems.disks.'.$diskName => [
                        'driver' => $diskDriver,
                        'username'  => $conn['username'],
                        'key'       => $conn['key'],
                        'container' => $conn['container'],
                        'endpoint'  => $conn['endpoint'],
                        'region'    => $conn['region'],
                        'url_type'  => $conn['url_type'],
                    ]
                ];
                break;
            default:
                throw new \Exception('File system disk driver not supported!');
        }

        config($fileSystem);
    }

    /**
     * TODO: refactor this to FileSystem facade and don`t use resolve path in future
     *
     * @param string $diskName
     * @param string $filePath
     * @return string
     */
    public static function resolvePath(string $diskName, string $filePath = ''): string
    {
        $configPath = config(sprintf('filesystems.disks.%s.path', $diskName));

        if (empty($configPath)) {
            return $filePath;
        }

        return $configPath.'/'.$filePath;
    }
}
