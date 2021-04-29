<?php

namespace Famdirksen\LaravelRequestLogger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RequestLog extends Model
{
    protected $table = 'request_logs';

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $requestLog) {
            $requestLog->{$requestLog->getKeyName()} = (string) Str::uuid();
        });
    }
}
