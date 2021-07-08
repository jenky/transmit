<?php

namespace Jenky\Transmit;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\ServiceProvider;
use Jenky\Transmit\Contracts\Transmit;
use Psr\Log\LoggerInterface;

class TransmitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();

        $this->registerHttpClientMacros();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/transmit.php', 'transmit'
        );

        $this->app->singleton(Transmit::class, function ($app) {
            return new ClientManager($app);
        });

        $this->app->alias(Transmit::class, 'transmit');
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/transmit.php' => config_path('transmit.php'),
            ], 'transmit-config');
        }
    }

    /**
     * Register HTTP client macros.
     *
     * @return void
     */
    protected function registerHttpClientMacros()
    {
        $app = $this->app;

        Factory::macro('client', function ($client) use ($app) {
            return $app[Transmit::class]->client($client);
        });

        PendingRequest::macro('withLogger', function ($logger, $formatter = null) use ($app) {
            if (! $logger) {
                return $this;
            }

            $logger = $logger instanceof LoggerInterface
                ? $logger
                : $app['log']->channel($logger);

            return $this->withMiddleware(Middleware::log(
                $logger, $formatter ?: new MessageFormatter(MessageFormatter::DEBUG)
            ));
        });
    }
}
