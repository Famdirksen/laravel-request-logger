<?php

namespace Famdirksen\LaravelRequestLogger\Commands;

use Illuminate\Console\Command;

class ClearRequestLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'request-logger:clear-request-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a job for clearing the request logs.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        ClearRequestLogsJob::dispatch();

        $this->info('Job dispatched.');
    }
}
