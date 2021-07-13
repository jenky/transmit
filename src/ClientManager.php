<?php

namespace Jenky\Transmit;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Jenky\Transmit\Contracts\TapableFactory;
use Jenky\Transmit\Contracts\Transmit;

class ClientManager implements Transmit
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of resolved channels.
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
    }

    /**
     * Get all the clients.
     *
     * @return array
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Attempt to get the client from the local cache.
     *
     * @param  string  $name
     * @return \Illuminate\Http\Client\Factory
     */
    public function client($name): Http
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
     * Resolve the given log instance by name.
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
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @return \Jenky\Transmit\Factory
     */
    public function createFactory(array $config)
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
    protected function tap(string $name, Http $client)
    {
        if (! $client instanceof TapableFactory) {
            return $client;
        }

        foreach ($this->configurationFor($name)['tap'] ?? []  as $tap) {
            if (is_callable($tap)) {
                $client->tap($tap);
            } else {
                [$class, $arguments] = $this->parseTap($tap);

                // $this->app->make($class)->__invoke($client, ...explode(',', $arguments));
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
}
