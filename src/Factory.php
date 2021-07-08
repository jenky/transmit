<?php

namespace Jenky\Transmit;

use Illuminate\Http\Client\Factory as BaseFactory;

class Factory extends BaseFactory
{
    use CreatesHttpClient;
}
