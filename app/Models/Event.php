<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Event extends Model
{
    use HasFactory, HasUuids, \App\Traits\LogsActivity;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'type',
        'description',
        'committee_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function checkins()
    {
        return $this->hasMany(EventCheckin::class);
    }

    public function committee()
    {
        return $this->belongsTo(Committee::class);
    }
}
