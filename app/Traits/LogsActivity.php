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

    public function logActivity(string $type, string $description, array $properties = []): Activity
    {
        return $this->activities()->create([
            'type' => $type,
            'description' => $description,
            'user_id' => auth()->id(),
            'properties' => $properties,
        ]);
    }
} 