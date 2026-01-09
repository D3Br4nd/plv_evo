<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasPushSubscriptions;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'avatar_path',
        'password',
        'must_set_password',
        'role',
        'plv_role',
        'membership_status',
        'birth_date',
        'birth_place_type',
        'birth_province_code',
        'birth_city',
        'birth_country',
        'residence_type',
        'residence_street',
        'residence_house_number',
        'residence_locality',
        'residence_province_code',
        'residence_city',
        'residence_country',
        'plv_joined_at',
        'plv_expires_at',
        'phone',
    ];

    /**
     * Computed attributes included when serializing the model (e.g. shared to Inertia).
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'plv_joined_at' => 'date',
            'plv_expires_at' => 'date',
            'must_set_password' => 'boolean',
            'role' => \App\Enums\UserRole::class,
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }
    
    /**
     * Get the memberships for the user.
     */
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function invitations()
    {
        return $this->hasMany(MemberInvitation::class);
    }

    /**
     * Get the committees associated with the user.
     */
    public function committees()
    {
        return $this->belongsToMany(Committee::class, 'committee_user')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }
}
