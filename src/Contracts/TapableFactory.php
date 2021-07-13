<?php

namespace Jenky\Transmit\Contracts;

interface TapableFactory
{
    /**
     * Set the callback to tap into the HTTP client.
     *
     * @param  callable  $callback
     * @param  array  $parameters
     * @return mixed
     */
    public function tap(callable $callback, ...$parameters);
}
