# Transmit

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Test Status][ico-gh-tests]][link-gh-tests]
[![Codecov][ico-codecov]][link-codecov]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)

WIP

- [Transmit](#transmit)
  - [Install](#install)
  - [Configuration](#configuration)
    - [Client configuration](#client-configuration)
    - [Configure the options](#configure-the-options)
    - [Customizing the client Pending Request](#customizing-the-client-pending-request)
      - ["Tap" class parameters](#tap-class-parameters)
  - [Change log](#change-log)
  - [Testing](#testing)
  - [Contributing](#contributing)
  - [Security](#security)
  - [Credits](#credits)
  - [License](#license)

## Install

You may use Composer to install Transmit into your Laravel project:

``` bash
$ composer require jenky/transmit
```

After installing Transmit, publish its assets using the `vendor:publish` Artisan command.

``` bash
php artisan vendor:publish
```

or

``` bash
php artisan vendor:publish --provider="Jenky\Transmit\TransmitServiceProvider"
```

## Configuration

After publishing Transmit's assets, its primary configuration file will be located at `config/transmit.php`. This configuration file allows you to configure your guzzle client options and each configuration option includes a description of its purpose, so be sure to thoroughly explore this file.

### Client configuration

A client is simply a HTTP client instance with its own configuration. This allows you to create a HTTP client on the fly and reuse anytime, anywhere you want.

### Configure the options

Set guzzle request options within the channel. Please visit [Request Options](http://docs.guzzlephp.org/en/stable/request-options.html) for more information.

``` php
'clients' => [

    'github' => [
        'options' => [
            'base_uri' => 'https://api.github.com/v3/',
            'time_out' => 20,
        ],
    ],

]
```

Then uses it in your code:

``` php
use Illuminate\Support\Facades\Http;

Http::client('github')->get('....');
```

### Customizing the client Pending Request

To get started, define a `tap` array on the channel's configuration. The `tap` array should contain a list of classes that should have an opportunity to customize (or "tap" into) the pending request instance after it is created:

``` php
'default' => [
    'tap' => [
        App\Http\Client\CustomizeRequest::class,
    ],
],
```

Once you have configured the `tap` option on your client, you're ready to define the class that will customize your client factory instance. This class only needs a single method: `__invoke`, which receives an `Illuminate\Http\Client\PendingRequest` instance.

``` php
<?php

namespace App\Http\Client;

use Illuminate\Http\Client\PendingRequest;

class CustomizeRequest
{
    /**
     * Customize the given client pending request instance.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $client
     * @return void
     */
    public function __invoke(PendingRequest $request)
    {
        $request->withToken('my_access_token');
    }
}
```

> All of your "tap" classes are resolved by the service container, so any constructor dependencies they require will automatically be injected.

#### "Tap" class parameters

"Tap" class can also receive additional parameters. For example, if your handler needs to log the Guzzle request and response by using a specific Laravel logger channel, you could create a `UseLogger` class that receives a channel name as an additional argument.

Additional parameters will be passed to the class after the `$request` argument:

``` php
<?php

namespace App\Http\Client;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\LogManager;

class UseLogger
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
     * Customize the given client pending request instance.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $client
     * @param  string|null  $channel
     * @param  string  $level
     * @return void
     */
    public function __invoke(PendingRequest $request, ?string $channel = null, string $level = 'debug')
    {
        $request->withMiddleware(Middleware::log(
            $this->logger->channel($channel), new MessageFormatter, $level
        ));
    }
}
```

"Tap" class parameters may be specified in `transmit` config by separating the class name and parameters with a `:`. Multiple parameters should be delimited by commas:

``` php
'my_client' => [
    'tap' => [
        App\Http\Client\UseLogger::class.':slack,info',
    ],
],
```

You can also use `closure` if you don't want to use class base method:

``` php
'my_client' => [
    'tap' => [
        function (PendingRequest $request) {
            $request->asForm();
        },
    ],
],
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

[ico-version]: https://img.shields.io/packagist/v/jenky/transmit.svg
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg
[ico-travis]: https://img.shields.io/travis/com/jenky/transmit/master.svg
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/jenky/transmit.svg
[ico-code-quality]: https://img.shields.io/scrutinizer/g/jenky/transmit.svg
[ico-downloads]: https://img.shields.io/packagist/dt/jenky/transmit.svg
[ico-gh-tests]: https://github.com/jenky/transmit/workflows/Tests/badge.svg
[ico-codecov]: https://codecov.io/gh/jenky/transmit/branch/master/graph/badge.svg

[link-packagist]: https://packagist.org/packages/jenky/transmit
[link-travis]: https://travis-ci.com/jenky/transmit
[link-scrutinizer]: https://scrutinizer-ci.com/g/jenky/transmit/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/jenky/transmit
[link-downloads]: https://packagist.org/packages/jenky/transmit
[link-author]: https://github.com/jenky
[link-contributors]: ../../contributors
[link-gh-tests]: https://github.com/jenky/transmit/actions
[link-codecov]: https://codecov.io/gh/jenky/transmit
