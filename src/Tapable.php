<?php

namespace Jenky\Transmit;

use Illuminate\Http\Client\PendingRequest;

trait Tapable
{
    /**
     * The client callbacks.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * Set the callback to tap into the HTTP client.
     *
     * @param  callable  $callback
     * @param  array  $parameters
     * @return $this
     */
    public function tap(callable $callback, ...$parameters)
    {
        $this->callbacks[] = [$callback, $parameters];

        return $this;
    }

    /**
     * Run the tapable callbacks through given pending request.
     *
     * @param  \Illuminate\Http\Client\PendingRequest  $request
     * @return void
     */
    protected function runCallbacks(PendingRequest $request)
    {
        collect($this->callbacks)->each(function ($item) use ($request) {
            [$callback, $parameters] = $item;

            $callback($request, ...$parameters);
        });
    }
}
