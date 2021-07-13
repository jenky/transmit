<?php

namespace Jenky\Transmit\Tests;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

class ClientTest extends TestCase
{
    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application   $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('transmit.clients.postman-echo', [
            'options' => [
                'base_uri' => 'https://postman-echo.com/',
            ],
            'tap' => [
                UseLogChannel::class.':daily',
            ]
        ]);

        $app['config']->set('transmit.clients.custom', [
            'via' => function (array $config) use ($app) {
                return $app->make(CustomClientFactory::class);
            },
        ]);
    }

    public function test_tap()
    {
        Http::client('httpbin')->tap(function ($client) {
            $client->withHeaders(['X-Foo' => 'bar']);
        });

        $response = Http::client('httpbin')
            ->acceptJson()
            ->get('headers');

        $this->assertEquals('bar', $response->json('headers.X-Foo'));
        $this->assertEquals('application/json', $response->json('headers.Accept'));

        Event::fake();

        $response = Http::client('postman-echo')->post('post');

        Event::assertDispatched(MessageLogged::class);

        $this->assertTrue($response->ok());
    }

    public function test_custom_factory()
    {
        $response = Http::client('custom')->echo();

        $this->assertTrue($response->ok());
        $this->assertEquals('https://postman-echo.com/get', $response['url']);
    }
}

class CustomClientFactory extends Factory
{
    public function echo()
    {
        return $this->get('https://postman-echo.com/get');
    }
}

class UseLogChannel
{
    public function __invoke(PendingRequest $client, ?string $channel = null)
    {
        $client->withLogger(logger()->channel($channel));
    }
}
