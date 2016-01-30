# BORICA API

This is a simple implementation of the BORICA API for adding payments with their service.

# Instalation

Pull from [Composer](https://getcomposer.org/):

```
composer require mirovit/borica-api
```

# How to use

One way would be to new up a version of `Mirovit\Borica\Factory`, which accepts `Mirovit\Borica\Request` and `Mirovit\Borica\Response` as constructor arguments. Or just use the the Request and Response classes individually.

The Request as its name suggests is responsible for generating the URLs for request to the Borica API - possible requests are: register transaction, check status of transaction, register delayed transaction request, complete a delayed transaction request, reverse delayed transaction request and reverse a partially or the full sum from a registered transaction.

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$factory = new Factory(
    new Request('<terminal id from BORICA>', '<private key signed from BORICA>', '<private key password (optional)>', '<language (optional - BG or EN)>', '<debug (optional, whether you're testing or accepting payments)>'),
    new Response('<public certificate from BORICA>')
);
```

## Registering of a transaction
```php
$factory->request()
        ->amount('1') // 1 EUR
        ->orderID(1) // Unique identifier in your system
        ->description('testing the process') // Short description of the purchase (up to 125 chars)
        ->currency('EUR') // The currency of the payment
        ->register(); // Type of the request
```

## Check status of a transaction
```php
$factory->request()
        ->amount('1') // 1 EUR
        ->orderID(1) // Unique identifier in your system
        ->description('testing the process') // Short description of the purchase (up to 125 chars)
        ->currency('EUR') // The currency of the payment
        ->status(); // Type of the request
```

## Reverse a transaction
```php
$factory->request()
        ->amount('1') // 1 EUR - partial reversal (amount less than the original), full reversal the original amount
        ->orderID(1) // Unique identifier in your system
        ->reverse(); // Type of the request
```

# Using with Laravel 5

Create a service provider as the one below and register it with the app.
You can either add it to the `config/app.php` or register it in `Providers/AppServiceProvider.php`.

```
<?php

namespace App\Providers;

use Mirovit\Borica\Factory;
use Mirovit\Borica\Request;
use Mirovit\Borica\Response;
use Illuminate\Support\ServiceProvider;

class BoricaServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(Request::class, function(){
            return new Request(env('BORICA_TERMINAL_ID'), 'path/to/private.key', '',  app()->getLocale());
        });

        $this->app->bind(Response::class, function(){
            return new Response('path/to/public.cer');
        });

        $this->app->bind(Factory::class, function(){
            return new Factory(app(Request::class), app(Response::class));
        }, true);
    }
}
```

# Contributing

If you'd like to contribute, feel free to send a pull request!
