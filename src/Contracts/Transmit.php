<?php

namespace Jenky\Transmit\Contracts;

use Illuminate\Http\Client\Factory;

interface Transmit
{
    /**
     * Get a client instance.
     *
     * @param  string  $name
     * @throws \InvalidArgumentException
     * @return \Illuminate\Http\Client\Factory
     */
    public function client($name): Factory;
}
