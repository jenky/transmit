<?php

namespace Jenky\Transmit\Tests;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class UnitTest extends TestCase
{
    public function test_client_is_instance_of_factory()
    {
        $this->assertInstanceOf(Factory::class, transmit()->client('httpbin'));
        $this->assertSame(transmit('httpbin'), Http::client('httpbin'));

        $this->expectException(InvalidArgumentException::class);
        Http::client('foo');
    }

    public function test_send_request_from_client()
    {
        $response = Http::client('httpbin')->get('status/200');

        $this->assertTrue($response->ok());
        $this->assertEquals(200, $response->status());

        $response = Http::client('httpbin')->get('status/500');

        $this->assertTrue($response->failed());
        $this->assertEquals(500, $response->status());

        $response = Http::client('httpbin')
            ->asJson()
            ->get('headers');

        $this->assertEquals('application/json', $response->header('content-type'));
        $this->assertTrue($response->hasHeader('content-type', 'application/json'));
    }
}
