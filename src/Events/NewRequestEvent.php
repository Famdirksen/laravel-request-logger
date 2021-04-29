<?php

namespace Famdirksen\LaravelRequestLogger\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewRequestEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $requestData;

    public function __construct(array $data)
    {
        $this->requestData = $data;
    }
}
