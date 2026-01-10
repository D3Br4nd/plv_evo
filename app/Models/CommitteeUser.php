<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CommitteeUser extends Pivot
{
    use HasUuids, LogsActivity;

    protected $table = 'committee_user';

    public $incrementing = false;

    protected $keyType = 'string';

    public function committee()
    {
        return $this->belongsTo(Committee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
