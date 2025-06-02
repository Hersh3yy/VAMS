<?php

namespace App\Traits;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('create', 'Created ' . class_basename($model));
        });

        static::updated(function ($model) {
            $model->logActivity('update', 'Updated ' . class_basename($model));
        });

        static::deleted(function ($model) {
            $model->logActivity('delete', 'Deleted ' . class_basename($model));
        });
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function logActivity(string $type, string $description, array $properties = []): ?Activity
    {
        // Skip logging if we don't have a user context (e.g., console commands)
        $userId = auth()->id();
        if (!$userId) {
            // If running in console, try to get user from model if it has user_id
            if (isset($this->user_id) && $this->user_id) {
                $userId = $this->user_id;
            } else {
                // Skip logging if no user context available
                return null;
            }
        }

        return $this->activities()->create([
            'type' => $type,
            'description' => $description,
            'user_id' => $userId,
            'properties' => $properties,
        ]);
    }
} 