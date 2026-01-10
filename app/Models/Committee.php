<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Committee extends Model
{
    use HasFactory, HasUuids, \App\Traits\LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'status',
        'image_path',
        'created_by_user_id',
    ];

    /**
     * Computed attributes included when serializing the model (e.g. shared to Inertia).
     *
     * @var list<string>
     */
    protected $appends = [
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the members associated with the committee.
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'committee_user')
            ->using(CommitteeUser::class)
            ->withPivot('id', 'role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the posts for the committee.
     */
    public function posts()
    {
        return $this->hasMany(CommitteePost::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the user who created the committee.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
