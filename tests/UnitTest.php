<?php

namespace Jenky\Transmit\Tests;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class UnitTest extends TestCase
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

        $app['config']->set('transmit.clients.localhost', [
            'tap' => [
                function (PendingRequest $request) {
                    $request->baseUrl('http://localhost/status/');
                },
            ],
        ]);

        $app['config']->set('transmit.clients.postman-echo', [
            'tap' => [
                function (PendingRequest $request) {
                    $request->baseUrl('https://postman-echo.com/status/');
                },
            ],
        ]);
    }

    public function test_client_is_instance_of_factory()
    {
        $this->assertInstanceOf(Factory::class, transmit()->client('httpbin'));
        $this->assertSame(transmit('httpbin'), Http::client('httpbin'));
        $this->assertCount(1, transmit()->getClients());

        $this->expectException(InvalidArgumentException::class);
        Http::client('foo');
    }

    public function test_fake()
    {
        Http::fake([
            'localhost/status/200' => Http::response(['status' => 200]),
            'localhost/status/400' => Http::response(['status' => 400], 400),
            'localhost/status/500' => Http::response(['status' => 500], 500),
            'localhost/*' => Http::response(null, 404),
        ]);

        $response = Http::client('localhost')->get('200');
        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->ok());

        $response = Http::client('localhost')->get('400');
        $this->assertEquals(400, $response->status());
        $this->assertTrue($response->clientError());

        $response = Http::client('localhost')->get('500');
        $this->assertEquals(500, $response->status());
        $this->assertTrue($response->serverError());

        $response = Http::client('localhost')->get('/foo');
        $this->assertEquals(404, $response->status());
    }

    public function test_request_uri()
    {
        $response = Http::client('postman-echo')->get('400');
        $this->assertEquals(400, $response->status());
        $this->assertTrue($response->clientError());

        $response = Http::client('postman-echo')->get('500');
        $this->assertEquals(500, $response->status());
        $this->assertTrue($response->serverError());

        $response = Http::client('postman-echo')->get('/time/now');
        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->ok());
        $this->assertStringContainsString('text/html', $response->header('content-type'));
    }
}
