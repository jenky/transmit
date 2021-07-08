<?php

namespace Jenky\Transmit;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;

trait CreatesHttpClient
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The client options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * The client callbacks.
     *
     * @var array
     */
    protected static $callbacks = [];

    /**
     * Crate new factory instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  array  $options
     * @return void
     */
    public function __construct(Application $app, array $options = [])
    {
        $this->app = $app;

        $this->options = $options;

        parent::__construct($app[Dispatcher::class]);
    }

    /**
     * Set the callback to tap into the HTTP client.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function tap(callable $callback)
    {
        static::$callbacks[] = $callback;

        return $this;
    }

    /**
     * Create a new pending request instance for this factory.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function newPendingRequest()
    {
        $request = parent::newPendingRequest()
            ->withOptions($this->options);

        collect(static::$callbacks)->each->__invoke($request);

        return $request;
    }
}
