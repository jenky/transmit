<?php

namespace Jenky\Transmit\Contracts;

use Illuminate\Http\Client\Factory;

interface HttpClient
{
    /**
     * Get a client instance.
     *
     * @param  string  $name
     * @throws \InvalidArgumentException
     * @return \Illuminate\Http\Client\Factory
     */
    public function scope(string $name): Factory;
}
