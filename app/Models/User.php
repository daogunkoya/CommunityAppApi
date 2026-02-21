<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
        'role',
        'location',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'community_name',
        'borough',
        'location_verified',
        'gender',
        'date_of_birth',
        'bio',
        'phone',
        'profile_picture',
        'is_active',
        'email_verification_token',
        'email_verification_sent_at',
        'is_online',
        'last_seen_at',
        'radius',
        'main_goal',
        'auth_provider',
        'auth_provider_id',
        'allow_notifications',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name',
        'age',
        'display_name',
        'full_address',
        'short_address',
        'community_location',
        'last_seen_formatted'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_token',
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
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'location_verified' => 'boolean',
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
            'auth_provider' => \App\Enums\AuthType::class,
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Formats the profile picture URL.
     */
    public function getProfilePictureAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Return as-is if it's already a full URL (legacy external auth)
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // Return dynamic host URL so mobile apps on local networks don't try to load "localhost"
        return request()->getSchemeAndHttpHost() . '/storage/' . $value;
    }

    /**
     * Get the user's age calculated from date of birth.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        /** @var \Carbon\Carbon|null $dob */
        $dob = $this->date_of_birth;
        return (int) $dob?->diffInYears(now());
    }

    /**
     * Get the user's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Generate email verification token.
     */
    public function generateEmailVerificationToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'email_verification_token' => $token,
            'email_verification_sent_at' => now(),
        ]);
        return $token;
    }

    /**
     * Get the communities that the user belongs to.
     */
    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'user_communities')
            ->withPivot('is_primary', 'is_active', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the user's primary community.
     */
    public function primaryCommunity()
    {
        return $this->communities()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get the user's active communities.
     */
    public function activeCommunities(): BelongsToMany
    {
        return $this->communities()->wherePivot('is_active', true);
    }

    /**
     * Get the full address string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        return implode(', ', $parts);
    }

    /**
     * Get the short address string.
     */
    public function getShortAddressAttribute(): string
    {
        $parts = array_filter([$this->city, $this->state, $this->country]);
        return implode(', ', $parts);
    }

    /**
     * Get the community location string.
     */
    public function getCommunityLocationAttribute(): string
    {
        if ($this->community_name && $this->borough) {
            return "{$this->community_name}, {$this->borough}";
        }

        if ($this->community_name) {
            return $this->community_name;
        }

        return $this->short_address;
    }

    /**
     * Scope to get users by community.
     */
    public function scopeByCommunity($query, string $communityName, ?string $city = null, ?string $state = null)
    {
        $query->where('community_name', $communityName);

        if ($city) {
            $query->where('city', $city);
        }

        if ($state) {
            $query->where('state', $state);
        }

        return $query;
    }

    /**
     * Scope to get users within a radius.
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, float $radiusKm = 10)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        return $query->selectRaw("
                *,
                ({$earthRadius} * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }

    /**
     * Get nearby users.
     */
    public function getNearbyUsers(float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->latitude || !$this->longitude) {
            return collect();
        }

        return static::where('id', '!=', $this->id)
            ->where('is_active', true)
            ->withinRadius((float) $this->latitude, (float) $this->longitude, $radiusKm)
            ->get();
    }

    /**
     * Verify email with token.
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the devices (Push Tokens) registered to the user.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Get the granular notification preferences for the user.
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }
    public function verifyEmail(string $token): bool
    {
        if ($this->email_verification_token === $token) {
            $this->update([
                'email_verified_at' => now(),
                'email_verification_token' => null,
                'email_verification_sent_at' => null,
            ]);
            return true;
        }
        return false;
    }

    /**
     * Check if email verification token is expired.
     */
    public function isEmailVerificationTokenExpired(): bool
    {
        if (!$this->email_verification_sent_at) {
            return true;
        }

        // Token expires after 24 hours
        return $this->email_verification_sent_at->addHours(24)->isPast();
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Mark user as online.
     */
    public function markAsOnline(): void
    {
        $this->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Mark user as offline.
     */
    public function markAsOffline(): void
    {
        $this->update([
            'is_online' => false,
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Update last seen timestamp.
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    /**
     * Get formatted last seen time.
     */
    public function getLastSeenFormattedAttribute(): string
    {
        if (!$this->last_seen_at) {
            return 'Never';
        }

        // Use absolute difference to handle past timestamps correctly
        $diff = abs(now()->diffInMinutes($this->last_seen_at));

        if ($diff < 1) {
            return 'Just now';
        } elseif ($diff < 60) {
            return $diff . 'm ago';
        } elseif ($diff < 1440) { // 24 hours
            return abs(now()->diffInHours($this->last_seen_at)) . 'h ago';
        } else {
            return $this->last_seen_at->format('M j, Y');
        }
    }

    public function gameInterests()
    {
        return $this->belongsToMany(GameType::class, 'game_user_interest')->withPivot('skill_level');
    }

    public function joinedEvents()
    {
        return $this->belongsToMany(GameEvent::class, 'game_event_user')->withTimestamps();
    }

    /**
     * Get user's skill levels for different sports
     */
    public function skillLevels()
    {
        return $this->hasMany(UserSkillLevel::class);
    }

    /**
     * Get user's preferred facilities
     */
    public function preferredFacilities()
    {
        return $this->hasMany(UserPreferredFacility::class);
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function blockedUsers()
    {
        return $this->hasMany(BlockedUser::class);
    }

    public function usersBlockedByMe()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user_id', 'blocked_user_id');
    }
}
