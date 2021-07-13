<?php

namespace Jenky\Transmit;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;

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
}
