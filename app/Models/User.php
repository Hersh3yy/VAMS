<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use App\Models\EntryType;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'album_display_settings',
        'logo_url',
        'is_admin',
        'is_approved',
        'approved_at',
        'api_key',
        'entry_type_permissions',
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
            'theme_settings' => 'array',
            'site_settings' => 'array',
            'album_display_settings' => 'array',
            'entry_type_permissions' => 'array',
            'is_admin' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (! $user->api_key) {
                $user->api_key = Str::random(32);
            }
        });
    }

    /**
     * Get the user's mosaics
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Mosaic>
     */
    public function mosaics(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Mosaic::class);
    }

    /**
     * Get the user's albums
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Album>
     */
    public function albums(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Album::class);
    }

    /**
     * Get the user's entries
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Entry>
     */
    public function entries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * Get allowed entry types for this user based on permissions
     *
     * @return Collection<int, EntryType>
     */
    public function allowedEntryTypes(): Collection
    {
        $permissions = $this->entry_type_permissions ?? [];

        // If no permissions set, return empty collection (no access)
        if (empty($permissions)) {
            return collect();
        }

        return EntryType::active()
            ->whereIn('slug', $permissions)
            ->get();
    }

    /**
     * Check if user has permission for entry type
     */
    public function hasEntryTypePermission(string $slug): bool
    {
        $permissions = $this->entry_type_permissions ?? [];

        // Empty permissions means no access
        if (empty($permissions)) {
            return false;
        }

        return in_array($slug, $permissions);
    }

    /**
     * Get the user's activities
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Activity>
     */
    public function activities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Generate a new API key for the user
     */
    public function generateNewApiKey(): string
    {
        $this->api_key = Str::random(32);
        $this->save();

        return $this->api_key;
    }

    /**
     * Regenerate the user's API key
     */
    public function regenerateApiKey(): string
    {
        $this->api_key = Str::random(32);
        $this->save();

        return $this->api_key;
    }
}
