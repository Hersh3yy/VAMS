<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service for managing user activity tracking and logging
 * Centralizes activity-related operations
 */
final class ActivityService
{
    /**
     * Get recent activities for a user
     */
    public function getRecentActivities(User $user, int $limit = 10): Collection
    {
        return Activity::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activity statistics for a user
     */
    public function getActivityStats(User $user, int $days = 30): array
    {
        $activities = Activity::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total' => $activities->count(),
            'by_type' => $activities->groupBy('type')->map->count()->toArray(),
            'by_subject' => $activities->groupBy('subject_type')->map->count()->toArray(),
            'recent_days' => $activities->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            })->map->count()->toArray(),
        ];
    }

    /**
     * Get activities by type for a user
     */
    public function getActivitiesByType(User $user, ActivityType $type, int $limit = 50): Collection
    {
        return Activity::where('user_id', $user->id)
            ->where('type', $type->value)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities by date range for a user
     */
    public function getActivitiesByDateRange(User $user, $start, $end): Collection
    {
        return Activity::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();
    }

    /**
     * Get activities by subject type (e.g., Album, Mosaic, Entry)
     */
    public function getActivitiesBySubjectType(User $user, string $subjectType, int $limit = 50): Collection
    {
        return Activity::where('user_id', $user->id)
            ->where('subject_type', $subjectType)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get all available activity types with labels
     */
    public function getActivityTypes(): array
    {
        return collect(ActivityType::cases())
            ->mapWithKeys(fn (ActivityType $type) => [$type->value => $type->label()])
            ->toArray();
    }

    /**
     * Get recent activity count for a user
     */
    public function getRecentActivityCount(User $user, int $days = 30): int
    {
        return Activity::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    /**
     * Format activities for display
     */
    public function formatActivitiesForDisplay(Collection $activities): array
    {
        return $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->type,
                'description' => $activity->description,
                'icon' => $activity->getActivityIconAttribute(),
                'color' => $activity->getActivityColorAttribute(),
                'created_at' => $activity->created_at->diffForHumans(),
                'properties' => $activity->properties,
            ];
        })->toArray();
    }

    /**
     * Get activity summary for dashboard
     */
    public function getDashboardSummary(User $user, int $days = 30): array
    {
        $stats = $this->getActivityStats($user, $days);
        $recent = $this->getRecentActivities($user, 5);

        return [
            'stats' => $stats,
            'recent_activities' => $this->formatActivitiesForDisplay($recent),
            'total_count' => $stats['total'],
        ];
    }
}

