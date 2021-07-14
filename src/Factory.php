<?php

namespace Jenky\Transmit;

use Illuminate\Http\Client\Factory as BaseFactory;
use Jenky\Transmit\Contracts\TapableFactory;

class Factory extends BaseFactory implements TapableFactory
{
    use CreatesFactory;
    use Tapable;

    /**
     * Create a new pending request instance for this factory.
     *
     * @return \Illuminate\Http\Client\PendingRequest
     */
    protected function newPendingRequest()
    {
        $request = (new PendingRequest($this))->withOptions($this->options);

        return tap($request, function ($request) {
            $this->runCallbacks($request);
        });
    }
}
