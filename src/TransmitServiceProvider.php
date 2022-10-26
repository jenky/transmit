<?php

namespace Jenky\Transmit;

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\ServiceProvider;
use Jenky\Transmit\Contracts\HttpClient;
use Psr\Log\LoggerInterface;

class TransmitServiceProvider extends ServiceProvider implements DeferrableProvider
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

        $this->app->singleton(HttpClient::class, function ($app) {
            return new ScopingHttpClient($app);
        });

        $this->app->alias(HttpClient::class, 'transmit');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [HttpClient::class];
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing(): void
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
    protected function registerHttpClientMacros(): void
    {
        $app = $this->app;

        PendingRequest::macro('withLogger', function ($logger, $formatter = null, string $logLevel = 'info') use ($app) {
            if (! $logger) {
                return $this;
            }

            $logger = $logger instanceof LoggerInterface
                ? $logger
                : $app['log']->channel($logger);

            return $this->withMiddleware(Middleware::log(
                $logger, $formatter ?: new MessageFormatter(MessageFormatter::DEBUG), $logLevel
            ));
        });
    }
}
