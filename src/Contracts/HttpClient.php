<?php

namespace Jenky\Transmit\Contracts;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;

interface HttpClient
{
    /**
     * Get a client instance.
     *
     * @param  string  $name
     * @throws \InvalidArgumentException
     * @return \Illuminate\Http\Client\Factory
     */
    public function scope($name): Factory;

    /**
     * Send the request to the given URL.
     *
     * @param  string  $method
     * @param  string  $url
     * @param  array  $options
     * @return \Illuminate\Http\Client\Response
     *
     * @throws \Exception
     */
    // public function send(string $method, string $url, array $options = []): Response;
}
