<?php

namespace Jenky\Transmit;

use Illuminate\Http\Client\PendingRequest as BasePendingRequest;

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
        $this->baseUrl = '';

        $this->options['base_uri'] = $url;

        return $this;
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
        foreach (ScopingHttpClient::$scopedOptions as $name => $opts) {
            $regex = $opts['scope'] ?? null;

            if (! is_string($regex)) {
                continue;
            }

            if (preg_match("{{$regex}}A", $url)) {
                $this->options = $this->mergeOptions($opts['options'] ?? []);
                break;
            }
        }

        return parent::send($method, $url, $options);
    }
}
