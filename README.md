# Hermes

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Test Status][ico-gh-tests]][link-gh-tests]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Codecov][ico-codecov]][link-codecov]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)

The package provides a nice and easy wrapper around Guzzle for use in your Laravel applications. If you don't know what Guzzle does, [take a peek at their intro](http://docs.guzzlephp.org/en/stable/index.html). Shortly said, Guzzle is a PHP HTTP client that makes it easy to send HTTP requests and trivial to integrate with web service.

- [Hermes](#hermes)
  - [Install](#install)
  - [Configuration](#configuration)
    - [Channel configuration](#channel-configuration)
    - [Configure the guzzle option](#configure-the-guzzle-option)
    - [Configure the guzzle handler](#configure-the-guzzle-handler)
    - [Configure the guzzle middleware](#configure-the-guzzle-middleware)
    - [Customizing the guzzle handler stack](#customizing-the-guzzle-handler-stack)
      - ["Tap" class parameters](#tap-class-parameters)
  - [Middleware](#middleware)
    - [`RequestEvent`](#requestevent)
    - [`ResponseHandler`](#responsehandler)
  - [Usage](#usage)
  - [Change log](#change-log)
  - [Testing](#testing)
  - [Contributing](#contributing)
  - [Security](#security)
  - [Credits](#credits)
  - [License](#license)

## Install

You may use Composer to install Hermes into your Laravel project:

``` bash
$ composer require jenky/hermes
```

After installing Hermes, publish its assets using the `vendor:publish` Artisan command.

``` bash
php artisan vendor:publish
```

or

``` bash
php artisan vendor:publish --provider="Jenky\Hermes\HermesServiceProvider"
```

## Configuration

After publishing Hermes's assets, its primary configuration file will be located at `config/hermes.php`. This configuration file allows you to configure your guzzle client options and each configuration option includes a description of its purpose, so be sure to thoroughly explore this file.

### Channel configuration

A channel is simply a guzzle http client instance with its own configuration. This allows you to create a http client on the fly and reuse anytime, anywhere you want.

### Configure the guzzle option

Set guzzle request options within the channel. Please visit [Request Options](http://docs.guzzlephp.org/en/stable/request-options.html) for more information.

``` php
'default' => [
    'options' => [
        'base_uri' => 'https://api.github.com/v3/',
        'time_out' => 20,
    ],
],
```

### Configure the guzzle handler
Configure guzzle [Handler](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#handlers) within the channel.

By default, guzzle will choose the most appropriate handler based on the extensions available on your system. However you can override this behavior with `handler` option. Optionally, any constructor parameters the handler needs may be specified using the `with` configuration option:

``` php
'default' => [
    'handler' => App\Http\CustomCurlHandler::class,
    'with' => [
        'delay' => 5,
    ],
],
```

An alternative way is set the handler in the [`options`](#configure-the-guzzle-option) configuration:

``` php
'default' => [
    'options' => [
        'handler' => App\Http\CustomCurlHandler::create(['delay' => 5]),
    ],
],
```

### Configure the guzzle middleware

Configure guzzle [Middleware](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#middleware) within the channel.

``` php
'default' => [
    'middleware' => [
        Jenky\Hermes\Middleware\RequestEvent::class,
    ],
],
```

You can read about the middleware in the [middleware](#middleware) section.

> Do no attempt to resolve container binding implementations such as config, session driver, logger inside the `hermes` config file. This is because those implementations are not yet bound to the container when the `hermes` config is loaded.

``` php
'middleware' => [
    // This won't work properly
    GuzzleHttp\Middleware::log(logs(), new GuzzleHttp\MessageFormatter),
],
```

> Instead of using middleware in config, consider [customizing the guzzle handler stack](#customizing-the-guzzle-handler-stack) if you needs container binding implementations.

### Customizing the guzzle handler stack

Sometimes you may need complete control over how guzzle's [HandleStack](http://docs.guzzlephp.org/en/stable/handlers-and-middleware.html#handlerstack) is configured for an existing channel. For example, you may want to add, remove or unshift a middleware for a given channel's handler stack.

To get started, define a `tap` array on the channel's configuration. The `tap` array should contain a list of classes that should have an opportunity to customize (or "tap" into) the handle stack instance after it is created:

``` php
'default' => [
    'tap' => [
        App\Http\Client\CustomizeHandlerStack::class,
    ],
],
```

Once you have configured the `tap` option on your channel, you're ready to define the class that will customize your `HandlerStack` instance. This class only needs a single method: `__invoke`, which receives an `GuzzleHttp\HandlerStack` instance.

``` php
<?php

namespace App\Http\Client;

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

class CustomizeHandlerStack
{
    /**
     * Customize the given handler stack instance.
     *
     * @param  \GuzzleHttp\HandlerStack  $stack
     * @return void
     */
    public function __invoke(HandlerStack $stack)
    {
        $stack->before('add_foo', Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withHeader('X-Baz', 'Qux');
        }, 'add_baz');
    }
}
```

> All of your "tap" classes are resolved by the service container, so any constructor dependencies they require will automatically be injected.

#### "Tap" class parameters

"Tap" class can also receive additional parameters. For example, if your handler needs to log the Guzzle request and response by using a specific Laravel logger channel, you could create a `LogMiddleware` class that receives a channel name as an additional argument.

Additional parameters will be passed to the class after the `$stack` argument:

``` php
<?php

namespace App\Support;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Log\LogManager;

class LogMiddleware
{
    /**
     * The logger manager instance.
     *
     * @var \Illuminate\Log\LogManager
     */
    protected $logger;

    /**
     * Create new log middleware instance.
     *
     * @param  \Illuminate\Log\LogManager $logger
     * @return void
     */
    public function __construct(LogManager $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Customize the given handle stack instance.
     *
     * @param  \GuzzleHttp\HandlerStack $stack
     * @return void
     */
    public function __invoke(HandlerStack $stack, ?string $channel = null, string $level = 'debug')
    {
        $stack->push(Middleware::log(
            $this->logger->channel($channel), new MessageFormatter, $level
        ));
    }
}
```

"Tap" class parameters may be specified in `hermes` config by separating the class name and parameters with a `:`. Multiple parameters should be delimited by commas:

``` php
'default' => [
    'tap' => [
        App\Http\Client\LogMiddleware::class.':slack',
    ],
],
```

## Middleware

### `RequestEvent`

This middleware will fire `Jenky\Hermes\Events\RequestHandled` event when a request had been fulfilled. It has these properties:

``` php
/**
 * The request instance.
 *
 * @var \Psr\Http\Message\RequestInterface
 */
public $request;

/**
 * The response instance.
 *
 * @var \Psr\Http\Message\ResponseInterface|null
 */
public $response;

/**
 * The request options.
 *
 * @var array
 */
public $options;
```

### `ResponseHandler`

When sending the request, `GuzzleHttp\Psr7\Response` will be used as the default response handler. However you can configure the request options to use your own response handler. Please note that response handler must be an instance of `Psr\Http\Message\ResponseInterface`

``` php
'default' => [
    'driver' => 'guzzle',
    'options' => [
        'base_uri' => 'https://httpbin.org/',
        // ...
        'response_handler' => Jenky\Hermes\JsonResponse::class,
    ],
    'middleware' => [
        Jenky\Hermes\Middleware\ResponseHandler::class,
        // ...
    ],
],
```

> `json` driver will automatically use `Jenky\Hermes\Middleware\ResponseHandler` middleware and set the default `response_handler` to `Jenky\Hermes\JsonResponse`

Now your HTTP request will returns an instance of `Jenky\Hermes\JsonResponse` instead of `GuzzleHttp\Psr7\Response` which provides a variety of methods that may be used to inspect the response:

``` php
$response->isSuccessful(): bool;
$response->isError(): bool;
$response->isInformational(): bool;
$response->isRedirect(): bool;
$response->isClientError(): bool;
$response->isServerError(): bool;
$response->ok(): bool;
$response->created(): bool;
$response->badRequest(): bool;
$response->unauthorized(): bool;
$response->forbidden(): bool;
$response->notFound(): bool;
$response->unprocessable(): bool;
$response->serverError(): bool;
$response->header(?string $header = null, $default = null): mixed;
$response->status($code = null);
$response->body(): string;

$response->toArray(): array;
$response->toJson(): string;
$response->exists($key): bool;
$response->get($key, $default = null): mixed;
```

The `Jenky\Hermes\JsonResponse` object also implements the PHP `ArrayAccess` interface and support magic `__get` method, allowing you to access JSON response data directly on the response:

``` php
$response['name'];
// or
$response->name;
```

## Usage

``` php
use Jenky\Hermes\Facades\Guzzle;

Guzzle::get('https://jsonplaceholder.typicode.com/users');
// or using helper
guzzle()->get('https://jsonplaceholder.typicode.com/users');
```

Sometimes you may wish to send a request to a channel other than your application's default channel. You may use the `channel` method on the `Guzzle` facade to retrieve and send to any channel defined in your configuration file:

``` php
use Jenky\Hermes\Facades\Guzzle;

Guzzle::channel('my_channel')->get('https://jsonplaceholder.typicode.com/users');
// or using helper
guzzle('my_channel')->get('https://jsonplaceholder.typicode.com/users');
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email contact@lynh.me instead of using the issue tracker.

## Credits

- [Lynh][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/jenky/hermes.svg
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-travis]: https://img.shields.io/travis/com/jenky/hermes/master.svg
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/jenky/hermes.svg
[ico-code-quality]: https://img.shields.io/scrutinizer/g/jenky/hermes.svg
[ico-downloads]: https://img.shields.io/packagist/dt/jenky/hermes.svg
[ico-gh-tests]: https://github.com/jenky/hermes/workflows/Tests/badge.svg
[ico-codecov]: https://codecov.io/gh/jenky/hermes/branch/master/graph/badge.svg

[link-packagist]: https://packagist.org/packages/jenky/hermes
[link-travis]: https://travis-ci.com/jenky/hermes
[link-scrutinizer]: https://scrutinizer-ci.com/g/jenky/hermes/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/jenky/hermes
[link-downloads]: https://packagist.org/packages/jenky/hermes
[link-author]: https://github.com/jenky
[link-contributors]: ../../contributors
[link-gh-tests]: https://github.com/jenky/hermes/actions
[link-codecov]: https://codecov.io/gh/jenky/hermes
