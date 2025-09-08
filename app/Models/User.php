<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

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
            'is_admin' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($user) {
            if (!$user->api_key) {
                $user->api_key = Str::random(32);
            }
        });
    }

    public function mosaics()
    {
        return $this->hasMany(Mosaic::class);
    }

    public function albums()
    {
        return $this->hasMany(Album::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function generateNewApiKey()
    {
        $this->api_key = Str::random(32);
        $this->save();
        return $this->api_key;
    }

    public function regenerateApiKey()
    {
        $this->api_key = Str::random(32);
        $this->save();
        return $this->api_key;
    }
}
