<?php

namespace Jenky\Transmit;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

trait CreatesFactory
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
     * Set the stub callbacks for the pending request.
     *
     * @param  null|\Illuminate\Support\Collection  $callbacks
     * @return $this
     */
    public function withStub(?Collection $callbacks = null)
    {
        if (! $callbacks || $callbacks->isEmpty()) {
            return;
        }

        $this->recording = true;

        $this->stubCallbacks = $callbacks;

        return $this;
    }
}
