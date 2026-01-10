<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Project extends Model
{
    use HasFactory, HasUuids, \App\Traits\LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'assignee_id',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
