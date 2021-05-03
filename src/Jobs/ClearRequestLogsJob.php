<?php

namespace Famdirksen\LaravelRequestLogger\Jobs;

use Cache;
use Famdirksen\LaravelRequestLogger\Models\RequestLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClearRequestLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get a lock for 10 minutes, so no other job can run at the same time...s
        $lock = Cache::lock(self::class.'_lock', 10 * 60);

        // More info: https://laravel.com/docs/8.x/cache#atomic-locks

        if (! $lock->get()) {
            // Couldn't acquire lock, other job is probably running
            return;
        }

        // Delete the records
        $numberOfRecordsDeleted = RequestLog::query()
            ->where('created_at', '<', now()->subDays(
                config('request-logger.clear-logs.after-days', 7)
            ))
            ->limit($limit = config('request-logger.clear-logs.limit', 1000))
            ->delete();

        // Release the lock, so a next job can get it
        $lock->release();

        // Check if the cleanup job reached the limit
        if ($numberOfRecordsDeleted == $limit) {
            // All $limit records are deleted, so time to dispatch another job to clean the next $limit (if available)
            self::dispatch(static::class);
        }

        // Based on: https://flareapp.io/blog/7-how-to-safely-delete-records-in-massive-tables-on-aws-using-laravel
    }
}
