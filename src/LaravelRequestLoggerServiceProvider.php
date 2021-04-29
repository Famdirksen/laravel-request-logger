<?php

namespace Famdirksen\LaravelRequestLogger;

use Illuminate\Support\ServiceProvider;

class LaravelRequestLoggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->setupMigrations();
        }
    }

    public function register()
    {
        //
    }

    protected function setupMigrations()
    {
        $timestamp = date('Y_m_d_His');

        foreach ([
            'create_request_logs_table',
        ] as $file) {
            $migrationsSource = __DIR__."/../database/migrations/{$file}.php";
            $migrationsTarget = database_path("/migrations/{$timestamp}_{$file}.php");

            $this->publishes([
                $migrationsSource => $migrationsTarget,
            ], 'migrations');
        }
    }
}
