<?php

namespace Famdirksen\LaravelRequestLogger\Listeners;

use Carbon\Carbon;
use Famdirksen\LaravelRequestLogger\Events\NewRequestEvent;
use Famdirksen\LaravelRequestLogger\Models\RequestLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogNewRequestListener implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  \Famdirksen\LaravelRequestLogger\Events\NewRequestEvent  $event
     * @return void
     */
    public function handle(NewRequestEvent $event)
    {
        try {
            $requestLog = new RequestLog();

            if (isset($this->requestData['user_id'])) {
                $requestLog->user_id = $event->requestData['user_id'];
            }

            $requestLog->ip = $event->requestData['ip'];
            $requestLog->url = $event->requestData['url'];
            $requestLog->method = $event->requestData['method'];
            $requestLog->input = $event->requestData['input'];
            $requestLog->headers = $event->requestData['headers'];
            $requestLog->created_at = Carbon::parse($event->requestData['logged_at']);

            $requestLog->save();
        } catch (\Exception $exception) {
            report($exception);
        }
    }

    /**
     * Get the name of the listener's queue.
     *
     * @return string
     */
    public function viaQueue()
    {
        return config('request-logger.queue', null);
    }
}
