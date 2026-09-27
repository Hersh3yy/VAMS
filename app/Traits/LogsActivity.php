<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Boot the activity logging trait
     */
    public static function bootLogsActivity(): void
    {
        static::created(function ($model): void {
            $model->logActivity(ActivityType::CREATE, 'Created '.class_basename($model));
        });

        static::updated(function ($model): void {
            $changes = $model->getChanges();
            $properties = [];

            if (! empty($changes)) {
                $properties['changes'] = $changes;
                $properties['old_values'] = array_intersect_key($model->getOriginal(), $changes);
            }

            $model->logActivity(ActivityType::UPDATE, 'Updated '.class_basename($model), $properties);
        });

        static::deleted(function ($model): void {
            $model->logActivity(ActivityType::DELETE, 'Deleted '.class_basename($model));
        });
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function logActivity(ActivityType $activityType, string $description, array $properties = []): ?Activity
    {
        // Skip logging if we don't have a user context (e.g., console commands)
        $userId = auth()->id();
        if (! $userId) {
            // If running in console, try to get user from model if it has user_id
            if (isset($this->user_id) && $this->user_id) {
                $userId = $this->user_id;
            } else {
                // Skip logging if no user context available
                return null;
            }
        }

        // Add request context if available
        if (Request::hasSession()) {
            $properties = array_merge($properties, [
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
            ]);
        }

        return $this->activities()->create([
            'type' => $activityType->value,
            'description' => $description,
            'user_id' => $userId,
            'properties' => $properties,
        ]);
    }

    // Convenience methods for common activity types
    public function logLogin(array $properties = []): ?Activity
    {
        return $this->logActivity(ActivityType::LOGIN, 'User logged in', $properties);
    }

    public function logLogout(array $properties = []): ?Activity
    {
        return $this->logActivity(ActivityType::LOGOUT, 'User logged out', $properties);
    }

    public function logUpload(string $filename, array $properties = []): ?Activity
    {
        $properties['filename'] = $filename;

        return $this->logActivity(ActivityType::UPLOAD, 'Uploaded file: '.$filename, $properties);
    }

    public function logDownload(string $filename, array $properties = []): ?Activity
    {
        $properties['filename'] = $filename;

        return $this->logActivity(ActivityType::DOWNLOAD, 'Downloaded file: '.$filename, $properties);
    }

    public function logShare(string $target, array $properties = []): ?Activity
    {
        $properties['target'] = $target;

        return $this->logActivity(ActivityType::SHARE, 'Shared with: '.$target, $properties);
    }

    public function logExport(string $format, array $properties = []): ?Activity
    {
        $properties['format'] = $format;

        return $this->logActivity(ActivityType::EXPORT, 'Exported as: '.$format, $properties);
    }

    public function logImport(string $source, array $properties = []): ?Activity
    {
        $properties['source'] = $source;

        return $this->logActivity(ActivityType::IMPORT, 'Imported from: '.$source, $properties);
    }

    public function logRestore(array $properties = []): ?Activity
    {
        return $this->logActivity(ActivityType::RESTORE, 'Restored '.class_basename($this), $properties);
    }

    /**
     * Get recent activities for this model
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    public function getRecentActivities(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activities()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by type
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Activity>
     */
    public function getActivitiesByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activities()
            ->where('type', $type)
            ->latest()
            ->get();
    }
}
