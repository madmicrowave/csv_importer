<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImportHistory extends Model
{
    const
        STATUS_SUCCESS = 0,
        STATUS_FAILED = 1,

        ATTEMPTS_LIMIT = 10;

    /** @var string */
    protected $table = 'import_history';

    protected $casts = [
        'meta' => 'json',
        'last_error' => 'json'
    ];

    /**
     * @return HasOne
     */
    public function remoteDisk(): HasOne
    {
        return $this->hasOne(RemoteDisks::class);
    }
}
