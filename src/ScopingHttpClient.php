<?php

namespace Jenky\Transmit;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;
use Jenky\Transmit\Contracts\HttpClient;
use Jenky\Transmit\Contracts\TapableFactory;

class ScopingHttpClient implements HttpClient
{
    use ForwardsCalls;

    public static $scopedOptions = [];

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of scoped clients.
     *
     * @var array
     */
    protected $clients = [];

    /**
     * Create a new client manager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        static::$scopedOptions = $app->make('config')->get('transmit.clients', []);
    }

    /**
     * Get all the clients.
     *
     * @return array
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    /**
     * Attempt to get the client from the local cache.
     *
     * @param  string  $name
     * @return \Jenky\Transmit\Factory
     */
    public function scope($name): Factory
    {
        return $this->clients[$name] ?? tap($this->resolve($name), function ($client) use ($name) {
            return $this->clients[$name] = $this->tap($name, $client);
        });
    }

    /**
     * Get the client configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function configurationFor($name): array
    {
        return $this->app['config']["transmit.clients.{$name}"] ?? [];
    }

    /**
     * Resolve the given scoped client instance by name.
     *
     * @param  string  $name
     * @throws \InvalidArgumentException
     * @return \Illuminate\Http\Client\Factory
     */
    protected function resolve($name): Http
    {
        $config = $this->configurationFor($name);

        if (empty($config)) {
            throw new InvalidArgumentException("HTTP Client [{$name}] is not defined.");
        }

        if (! empty($config['via'])) {
            return $this->createCustomFactory($config);
        }

        return $this->createFactory($config);
    }

    /**
     * Create a custom factory instance.
     *
     * @param  array  $config
     * @return \Illuminate\Http\Client\Factory
     */
    protected function createCustomFactory(array $config)
    {
        $factory = is_callable($via = $config['via']) ? $via : $this->app->make($via);

        return $factory($config);
    }

    /**
     * Create a default HTTP client factory instance.
     *
     * @param  array  $config
     * @return \Jenky\Transmit\Factory
     */
    public function createFactory(array $config): Factory
    {
        return new Factory($this->app, $config['options'] ?? []);
    }

    /**
     * Apply the configured taps for the handle stack.
     *
     * @param  string  $name
     * @param  \Illuminate\Http\Client\Factory  $client
     * @return \Illuminate\Http\Client\Factory
     */
    protected function tap(string $name, Http $client): Http
    {
        if (! $client instanceof TapableFactory) {
            return $client;
        }

        foreach ($this->configurationFor($name)['tap'] ?? []  as $tap) {
            if (is_callable($tap)) {
                $client->tap($tap);
            } else {
                [$class, $arguments] = $this->parseTap($tap);

                $client->tap($this->app->make($class), ...explode(',', $arguments));
            }
        }

        return $client;
    }

    /**
     * Parse the given tap class string into a class name and arguments string.
     *
     * @param  string  $tap
     * @return array
     */
    protected function parseTap($tap)
    {
        return Str::contains($tap, ':') ? explode(':', $tap, 2) : [$tap, ''];
    }

    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->createFactory([]), $method, $parameters
        );
    }
}
