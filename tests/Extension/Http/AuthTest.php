<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Http;

use PHPUnit\Framework\TestCase;
use WeprestaAcf\Extension\Http\Auth\ApiKeyAuth;
use WeprestaAcf\Extension\Http\Auth\BasicAuth;
use WeprestaAcf\Extension\Http\Auth\BearerAuth;

class AuthTest extends TestCase
{
    public function testBearerAuthHeaders(): void
    {
        $auth = new BearerAuth('my-secret-token');
        $headers = $auth->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertEquals('Bearer my-secret-token', $headers['Authorization']);
    }

    public function testApiKeyAuthInHeader(): void
    {
        $auth = new ApiKeyAuth('my-api-key', 'X-API-Key');
        $headers = $auth->getHeaders();

        $this->assertArrayHasKey('X-API-Key', $headers);
        $this->assertEquals('my-api-key', $headers['X-API-Key']);
    }

    public function testApiKeyAuthDefaultHeader(): void
    {
        $auth = new ApiKeyAuth('my-api-key');
        $headers = $auth->getHeaders();

        $this->assertArrayHasKey('X-Api-Key', $headers);
        $this->assertEquals('my-api-key', $headers['X-Api-Key']);
    }

    public function testBasicAuthHeaders(): void
    {
        $auth = new BasicAuth('username', 'password');
        $headers = $auth->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);

        $expected = 'Basic ' . base64_encode('username:password');
        $this->assertEquals($expected, $headers['Authorization']);
    }

    public function testBasicAuthWithSpecialCharacters(): void
    {
        $auth = new BasicAuth('user@example.com', 'p@ss:w0rd!');
        $headers = $auth->getHeaders();

        $expected = 'Basic ' . base64_encode('user@example.com:p@ss:w0rd!');
        $this->assertEquals($expected, $headers['Authorization']);
    }
}
