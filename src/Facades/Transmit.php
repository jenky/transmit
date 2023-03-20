<?php

namespace Jenky\Transmit\Facades;

use Illuminate\Support\Facades\Facade;
use Jenky\Transmit\Contracts\HttpClient;

class Transmit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return HttpClient::class;
    }
}
