<?php

namespace Famdirksen\LaravelRequestLogger;

use Famdirksen\LaravelRequestLogger\Events\NewRequestEvent;
use Famdirksen\LaravelRequestLogger\Listeners\LogNewRequestListener;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class LaravelRequestLoggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->setupMigrations();
        }

        Event::listen(
            NewRequestEvent::class,
            [LogNewRequestListener::class, 'handle']
        );
    }

    public function register()
    {
        $this->setupConfig();
    }

    protected function setupConfig()
    {
        $source = __DIR__.'/../config/request-logger.php';

        $this->publishes([
            $source => config_path('request-logger.php'),
        ], 'config');

        $this->mergeConfigFrom($source, 'request-logger');
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
