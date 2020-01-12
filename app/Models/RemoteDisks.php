<?php

namespace App\Models;

use App\Classes\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TODO: should log new connection creation log to history table
 * TODO: should mask db field specified in $maskDiskConnectionsKeys
 *
 * Class RemoteDisks
 * @package App\Models
 */
class RemoteDisks extends Model
{
    const
        STATUS_DISABLED = 0,
        STATUS_ACTIVE = 1;

    /** @var string */
    protected $table = 'remote_disks';

    /** @var array */
    protected $casts = [
        'disk_connection' => 'json'
    ];

    /**
     * TODO: activate mask for disk_connection table
     *
     * @var array
     */
    protected $maskDiskConnectionsKeys = [
        'secret', 'password', 'privateKey', 'privateKeyPassword'
    ];

    /**
     * @return HasMany
     */
    public function importHistory(): HasMany
    {
        return  $this->hasMany(RemoteDisks::class);
    }

    /**
     * @return self
     * @throws \Exception
     */
    public function setConfig(): self
    {
        Helpers::setFileSystemDisk(
            $this->name,
            $this->driver,
            $this->disk_connection
        );

        return $this;
    }
}
