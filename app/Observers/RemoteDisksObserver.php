<?php

namespace App\Observers;

use App\Models\RemoteDisks;

/**
 * TODO: create json field masker
 *
 * Class RemoteDisksObserver
 * @package App\Observers
 */
class RemoteDisksObserver
{
    /**
     * Handle the remote disks "created" event.
     *
     * @param  \App\Models\RemoteDisks  $remoteDisks
     * @return void
     */
    public function created(RemoteDisks $remoteDisks)
    {
        //
    }

    /**
     * Handle the remote disks "updated" event.
     *
     * @param  \App\Models\RemoteDisks  $remoteDisks
     * @return void
     */
    public function updated(RemoteDisks $remoteDisks)
    {
        //
    }


//    public function beforeSave(): void
//    {
//        $this->maskParams();
//    }
//
//    public function afterFetch(): void
//    {
//        $this->maskParams(true);
//    }
//
//    /**
//     * @param bool $decode
//     */
//    public function maskParams($decode = false): void
//    {
//        $direction = $decode ? 'base64_decode' : 'base64_encode';
//
//        foreach ($this->disk_connection as $key => $value) {
//            if (in_array($key, $this->maskConnKeys)) {
//                $this->disk_connection[$key] = $direction($value);
//            }
//        }
//    }
}
