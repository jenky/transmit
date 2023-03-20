<?php

namespace Jenky\Transmit;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory as BaseFactory;
use Jenky\Transmit\Contracts\TapableFactory;

class Factory extends BaseFactory implements TapableFactory
{
    use Tapable;

    /**
     * The client options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Crate new factory instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher|null  $dispatcher
     * @param  array  $options
     * @return void
     */
    public function __construct(Dispatcher $dispatcher = null, array $options = [])
    {
        $this->options = $options;

        parent::__construct($dispatcher);
    }

    /**
     * Create a new pending request instance for this factory.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function newPendingRequest()
    {
        $request = (new PendingRequest($this))->withOptions($this->options);

        return tap($request, function ($request) {
            $this->runCallbacks($request);
        });
    }

    /**
     * Execute a method against a new pending request instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (version_compare(Application::VERSION, '8.0.0', '>=')) {
            return parent::__call($method, $parameters);
        }

        // Laravel 7 backward compat.
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return tap($this->newPendingRequest(), function ($request) {
            $request->stub($this->stubCallbacks);
        })->{$method}(...$parameters);
    }
}
