<?php

use Jenky\Transmit\Contracts\Transmit;

if (! function_exists('transmit')) {
    /**
     * Get a guzzle client instance.
     *
     * @param  string|null  $client
     * @return \Jenky\Transmit\Contracts\Transmit|\Jenky\Transmit\Contracts\Factory
     */
    function transmit($client = null)
    {
        return $client ? app(Transmit::class)->client($client) : app(Transmit::class);
    }
}
