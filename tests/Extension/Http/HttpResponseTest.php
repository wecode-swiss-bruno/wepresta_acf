<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Http;

use WeprestaAcf\Extension\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{
    public function testIsSuccessFor200(): void
    {
        $response = new HttpResponse(200, 'OK', []);

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    public function testIsSuccessFor201(): void
    {
        $response = new HttpResponse(201, 'Created', []);

        $this->assertTrue($response->isSuccess());
    }

    public function testIsClientErrorFor404(): void
    {
        $response = new HttpResponse(404, 'Not Found', []);

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->isClientError());
        $this->assertFalse($response->isServerError());
    }

    public function testIsServerErrorFor500(): void
    {
        $response = new HttpResponse(500, 'Internal Server Error', []);

        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isClientError());
        $this->assertTrue($response->isServerError());
    }

    public function testGetStatusCode(): void
    {
        $response = new HttpResponse(418, "I'm a teapot", []);

        $this->assertEquals(418, $response->getStatusCode());
    }

    public function testGetBody(): void
    {
        $response = new HttpResponse(200, 'Hello World', []);

        $this->assertEquals('Hello World', $response->getBody());
    }

    public function testGetHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'X-Custom' => 'value',
        ];
        $response = new HttpResponse(200, '', $headers);

        $this->assertEquals($headers, $response->getHeaders());
    }

    public function testGetHeader(): void
    {
        $headers = ['Content-Type' => 'application/json'];
        $response = new HttpResponse(200, '', $headers);

        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
        $this->assertNull($response->getHeader('Non-Existent'));
    }

    public function testJson(): void
    {
        $data = ['name' => 'Test', 'value' => 123];
        $response = new HttpResponse(200, json_encode($data), []);

        $this->assertEquals($data, $response->json());
    }

    public function testJsonOrNullReturnsNullForInvalidJson(): void
    {
        $response = new HttpResponse(200, 'not valid json', []);

        $this->assertNull($response->jsonOrNull());
    }

    public function testJsonOrNullReturnsDataForValidJson(): void
    {
        $data = ['key' => 'value'];
        $response = new HttpResponse(200, json_encode($data), []);

        $this->assertEquals($data, $response->jsonOrNull());
    }
}

