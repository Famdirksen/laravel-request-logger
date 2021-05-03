# A package to log requests to the database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/famdirksen/laravel-request-logger.svg?style=flat-square)](https://packagist.org/packages/famdirksen/laravel-request-logger)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/famdirksen/laravel-request-logger/Tests?label=tests)](https://github.com/famdirksen/laravel-request-logger/actions?query=workflow%3ATests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/famdirksen/laravel-request-logger.svg?style=flat-square)](https://packagist.org/packages/famdirksen/laravel-request-logger)


With this package you can log incoming requests to the database.

![Package info](https://banners.beyondco.de/Laravel%20Request%20Logger.png?theme=light&packageManager=composer+require&packageName=famdirksen%2Flaravel-request-logger&pattern=architect&style=style_1&description=Register+referrals+in+your+application+with+ease.&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg)

## Installation

You can install the package via composer:

```bash
composer require famdirksen/laravel-request-logger
```

## Usage

This packages uses a middleware to log requests to the (at this moment the only support driver) database.

### Installation
Add the `UriLoggerMiddleware` middleware to the route (or groups) you want to log.

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareGroups = [
        'web' => [
            'request_logger',
            // ...
        ],

        'api' => [
            'request_logger',
            // ...
        ],
    ];
    
    protected $routeMiddleware = [
        'request_logger' => \Famdirksen\LaravelRequestLogger\Http\Middleware\UriLoggerMiddleware::class,
    ];
}
```

Publish the migrations using:
`php artisan vendor:publish --provider="Famdirksen\LaravelRequestLogger\LaravelRequestLoggerServiceProvider"`

And migrate the database for storing the events.
`php artisan migrate`

### Event handling
The events are dispatched to the queue after the response is sent to the user. For the best performance of this job, use a queue worker to process the jobs.

### Disable logging
When you want to disable logging, you can set the `REQUEST_LOGGER_ENABLED` variable to `false` in your `.env` file (ps, don't forget to clear your config cache `php artisan config:clear`).

### Specify the queue
It's possible to set a specific queue to run the request logger on. This can be done by setting the `REQUEST_LOGGER_QUEUE` variable in your `.env`. If no value is provided, it will use the default queue. 

All new requests will be dispatched on the defined queue, but some may already be dispatched to the previous defined queue. To prevent loss of this data, you need to keep the old queue running until all jobs are processed. 

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Robin Dirksen](https://github.com/robindirksen1)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
