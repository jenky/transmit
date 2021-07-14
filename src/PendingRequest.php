<?php

namespace Jenky\Transmit;

use GuzzleHttp\Middleware;
use Illuminate\Http\Client\PendingRequest as BasePendingRequest;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;

class PendingRequest extends BasePendingRequest
{
    /**
     * Set the base URL for the pending request.
     *
     * @param  string  $url
     * @return $this
     */
    public function baseUrl(string $url)
    {
        return tap(parent::baseUrl($url), function ($request) use ($url) {
            return $this->options['base_uri'] = $url;
        });
    }

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
    public function send(string $method, string $url, array $options = [])
    {
        if (Str::startsWith($url, '/')) {
            // Restore the original path since the parent remove the leading /
            // from the url
            $this->restoreOriginalPath($url);
        }

        return parent::send($method, $url, $options);
    }

    /**
     * Tap into the request to restore the uri path.
     *
     * @param  string  $path
     * @return $this
     */
    public function restoreOriginalPath(string $path)
    {
        return $this->withMiddleware(Middleware::mapRequest(function (RequestInterface $request) use ($path) {
            return $request->withUri($request->getUri()->withPath($path));
        }));
    }
}
