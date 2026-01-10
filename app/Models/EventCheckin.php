<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventCheckin extends Model
{
    use HasFactory, HasUuids, \App\Traits\LogsActivity;

    protected $fillable = [
        'event_id',
        'membership_id',
        'checked_in_by_user_id',
        'checked_in_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }

    public function checkedInBy()
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }
}


