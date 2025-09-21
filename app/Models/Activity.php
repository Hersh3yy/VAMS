<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
        'user_id',
        'subject_type',
        'subject_id',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'user_id' => 'string',
        'type' => ActivityType::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    // Scopes for filtering activities
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopeByUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeBySubjectType(Builder $query, string $subjectType): Builder
    {
        return $query->where('subject_type', $subjectType);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function isCreate(): bool
    {
        return $this->type === ActivityType::CREATE;
    }

    public function isUpdate(): bool
    {
        return $this->type === ActivityType::UPDATE;
    }

    public function isDelete(): bool
    {
        return $this->type === ActivityType::DELETE;
    }

    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getActivityIconAttribute(): string
    {
        return $this->type->icon();
    }

    public function getActivityColorAttribute(): string
    {
        return $this->type->color();
    }

    // Static helper methods
    public static function getActivityTypes(): array
    {
        return collect(ActivityType::cases())
            ->mapWithKeys(fn (ActivityType $type) => [$type->value => $type->label()])
            ->toArray();
    }

    public static function getActivityStats(User $user, int $days = 30): array
    {
        $activities = self::byUser($user)
            ->recent($days)
            ->get();

        return [
            'total' => $activities->count(),
            'by_type' => $activities->groupBy('type')->map->count(),
            'by_subject' => $activities->groupBy('subject_type')->map->count(),
            'recent_days' => $activities->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            })->map->count(),
        ];
    }
}
