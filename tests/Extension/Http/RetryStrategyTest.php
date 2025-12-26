<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Http;

use WeprestaAcf\Extension\Http\RetryStrategy;
use PHPUnit\Framework\TestCase;

class RetryStrategyTest extends TestCase
{
    public function testGetMaxAttempts(): void
    {
        $strategy = new RetryStrategy(maxAttempts: 5);

        $this->assertEquals(5, $strategy->getMaxAttempts());
    }

    public function testShouldRetryFor500(): void
    {
        $strategy = new RetryStrategy();

        $this->assertTrue($strategy->shouldRetry(500));
        $this->assertTrue($strategy->shouldRetry(502));
        $this->assertTrue($strategy->shouldRetry(503));
        $this->assertTrue($strategy->shouldRetry(504));
    }

    public function testShouldRetryFor429(): void
    {
        $strategy = new RetryStrategy();

        $this->assertTrue($strategy->shouldRetry(429));
    }

    public function testShouldNotRetryFor200(): void
    {
        $strategy = new RetryStrategy();

        $this->assertFalse($strategy->shouldRetry(200));
    }

    public function testShouldNotRetryFor400(): void
    {
        $strategy = new RetryStrategy();

        $this->assertFalse($strategy->shouldRetry(400));
        $this->assertFalse($strategy->shouldRetry(401));
        $this->assertFalse($strategy->shouldRetry(404));
    }

    public function testGetDelayWithoutExponentialBackoff(): void
    {
        $strategy = new RetryStrategy(maxAttempts: 3, exponentialBackoff: false);

        $delay1 = $strategy->getDelay(1);
        $delay2 = $strategy->getDelay(2);
        $delay3 = $strategy->getDelay(3);

        // Sans backoff, délai constant
        $this->assertEquals($delay1, $delay2);
        $this->assertEquals($delay2, $delay3);
    }

    public function testGetDelayWithExponentialBackoff(): void
    {
        $strategy = new RetryStrategy(maxAttempts: 3, exponentialBackoff: true);

        $delay1 = $strategy->getDelay(1);
        $delay2 = $strategy->getDelay(2);
        $delay3 = $strategy->getDelay(3);

        // Avec backoff, délai croissant
        $this->assertLessThan($delay2, $delay1);
        $this->assertLessThan($delay3, $delay2);
    }

    public function testGetDelayHasMaxLimit(): void
    {
        $strategy = new RetryStrategy(maxAttempts: 10, exponentialBackoff: true);

        $delay10 = $strategy->getDelay(10);

        // Le délai max devrait être limité (par exemple 60 secondes)
        $this->assertLessThanOrEqual(60000, $delay10);
    }
}

