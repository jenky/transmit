<?php

use Jenky\Transmit\Contracts\HttpClient;

if (! function_exists('transmit')) {
    /**
     * Get a guzzle client instance.
     *
     * @param  string|null  $client
     * @return \Jenky\Transmit\Contracts\HttpClient|\Jenky\Transmit\Contracts\Factory
     */
    function transmit($client = null)
    {
        return $client ? app(HttpClient::class)->client($client) : app(HttpClient::class);
    }
}
