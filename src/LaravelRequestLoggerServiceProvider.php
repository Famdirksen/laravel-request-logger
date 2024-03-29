<?php

namespace Famdirksen\LaravelRequestLogger;

use Famdirksen\LaravelRequestLogger\Commands\ClearRequestLogsCommand;
use Famdirksen\LaravelRequestLogger\Events\NewRequestEvent;
use Famdirksen\LaravelRequestLogger\Listeners\LogNewRequestListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LaravelRequestLoggerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->setupMigrations();
        }

        $this->commands([
            ClearRequestLogsCommand::class,
        ]);

        Event::listen(NewRequestEvent::class, LogNewRequestListener::class);
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
        foreach ([
            '2021_05_03_100733_create_request_logs_table',
            '2021_05_03_133802_update_request_logs_table_for_request_duration_attribute',
            '2021_05_03_133802_update_request_logs_table_for_route_name_attribute',
            '2022_10_13_133802_update_request_logs_table_for_user_type_attribute',
        ] as $file) {
            $migrationsSource = __DIR__."/../database/migrations/{$file}.php";
            $migrationsTarget = database_path("/migrations/{$file}.php");

            $this->publishes([
                $migrationsSource => $migrationsTarget,
            ], 'migrations');
        }
    }
}
