<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPlan;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasPlan, HasUuids, Notifiable;

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
        'plan',
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
     * The model's default values for attributes.
     *
     * `plan` also has a database-level default, but Eloquent never learns
     * about column defaults for attributes it didn't explicitly set on
     * create/new (it doesn't re-select the row), so without this a
     * freshly-created User has no `plan` key in $attributes at all. That
     * ambiguity is exactly what makes `$this->plan` in HasPlan::plan()
     * misresolve as a relationship method call instead of an attribute read.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'plan' => 'free',
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
     * @return HasMany<Mosaic>
     */
    public function mosaics(): HasMany
    {
        return $this->hasMany(Mosaic::class);
    }

    /**
     * Get the user's albums
     *
     * @return HasMany<Album>
     */
    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    /**
     * Get the user's entries
     *
     * @return HasMany<Entry>
     */
    public function entries(): HasMany
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
            return EntryType::query()->whereRaw('1 = 0')->get();
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
     * @return HasMany<Activity>
     */
    public function activities(): HasMany
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
