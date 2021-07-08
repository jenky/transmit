<?php

namespace Jenky\Transmit\Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;

class FeatureTest extends TestCase
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

        $app['config']->set('transmit.clients.jsonplaceholder', [
            'options' => [
                'base_uri' => 'https://jsonplaceholder.typicode.com',
                'http_errors' => true,
            ],
        ]);

        $app['config']->set('transmit.clients.reqres', [
            'options' => [
                'base_uri' => 'https://reqres.in',
            ],
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

// class AddHeaderToRequest
// {
//     public function __invoke(HandlerStack $handler, $header, $value)
//     {
//         $handler->push(Middleware::mapRequest(function (RequestInterface $request) use ($header, $value) {
//             return $request->withHeader($header, $value);
//         }));
//     }
// }
