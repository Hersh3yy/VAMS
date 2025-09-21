<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Activity;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanupActivitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'activities:cleanup 
                            {--days=90 : Number of days to keep activities}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old activity logs to maintain database performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($days < 1) {
            $this->error('Days must be a positive integer.');

            return 1;
        }

        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning up activities older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})");

        // Count activities to be deleted
        $activitiesToDelete = Activity::where('created_at', '<', $cutoffDate)->count();

        if ($activitiesToDelete === 0) {
            $this->info('No activities found to clean up.');

            return 0;
        }

        $this->info("Found {$activitiesToDelete} activities to delete.");

        if ($dryRun) {
            $this->warn('DRY RUN: No activities were actually deleted.');
            $this->table(
                ['Type', 'Count'],
                Activity::where('created_at', '<', $cutoffDate)
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->map(fn ($item) => [$item->type, $item->count])
                    ->toArray()
            );

            return 0;
        }

        if (! $force) {
            if (! $this->confirm("Are you sure you want to delete {$activitiesToDelete} activities?")) {
                $this->info('Cleanup cancelled.');

                return 0;
            }
        }

        // Show progress bar
        $this->info('Deleting activities...');
        $bar = $this->output->createProgressBar($activitiesToDelete);
        $bar->start();

        // Delete in chunks to avoid memory issues
        $chunkSize = 1000;
        $deleted = 0;

        Activity::where('created_at', '<', $cutoffDate)
            ->chunk($chunkSize, function ($activities) use ($bar, &$deleted) {
                foreach ($activities as $activity) {
                    $activity->delete();
                    $deleted++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        $this->info("Successfully deleted {$deleted} activities.");

        // Show remaining activity count
        $remainingCount = Activity::count();
        $this->info("Remaining activities: {$remainingCount}");

        return 0;
    }
}
