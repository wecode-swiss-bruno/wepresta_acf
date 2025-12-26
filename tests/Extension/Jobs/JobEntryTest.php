<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Jobs;

use WeprestaAcf\Extension\Jobs\JobEntry;
use PHPUnit\Framework\TestCase;

class JobEntryTest extends TestCase
{
    public function testCreatePending(): void
    {
        $entry = JobEntry::createPending(
            'TestJob',
            ['key' => 'value']
        );

        $this->assertEquals(JobEntry::STATUS_PENDING, $entry->getStatus());
        $this->assertEquals('TestJob', $entry->getJobClass());
        $this->assertEquals(['key' => 'value'], $entry->getPayload());
        $this->assertEquals(0, $entry->getAttempts());
    }

    public function testMarkAsProcessing(): void
    {
        $entry = JobEntry::createPending('TestJob', []);
        $entry->markAsProcessing();

        $this->assertEquals(JobEntry::STATUS_PROCESSING, $entry->getStatus());
        $this->assertEquals(1, $entry->getAttempts());
        $this->assertNotNull($entry->getStartedAt());
    }

    public function testMarkAsCompleted(): void
    {
        $entry = JobEntry::createPending('TestJob', []);
        $entry->markAsProcessing();
        $entry->markAsCompleted();

        $this->assertEquals(JobEntry::STATUS_COMPLETED, $entry->getStatus());
        $this->assertNotNull($entry->getCompletedAt());
    }

    public function testMarkAsFailed(): void
    {
        $entry = JobEntry::createPending('TestJob', []);
        $entry->markAsProcessing();
        $entry->markAsFailed('Error message');

        $this->assertEquals(JobEntry::STATUS_FAILED, $entry->getStatus());
        $this->assertEquals('Error message', $entry->getError());
    }

    public function testIsPending(): void
    {
        $entry = JobEntry::createPending('TestJob', []);

        $this->assertTrue($entry->isPending());
        $this->assertFalse($entry->isProcessing());
        $this->assertFalse($entry->isCompleted());
        $this->assertFalse($entry->isFailed());
    }

    public function testScheduledFor(): void
    {
        $scheduledFor = new \DateTimeImmutable('+1 hour');
        $entry = JobEntry::createPending('TestJob', [], $scheduledFor);

        $this->assertEquals($scheduledFor, $entry->getScheduledFor());
        $this->assertFalse($entry->isReady());
    }

    public function testIsReadyWhenScheduledForPast(): void
    {
        $scheduledFor = new \DateTimeImmutable('-1 minute');
        $entry = JobEntry::createPending('TestJob', [], $scheduledFor);

        $this->assertTrue($entry->isReady());
    }

    public function testToArray(): void
    {
        $entry = JobEntry::createPending('TestJob', ['key' => 'value']);
        $array = $entry->toArray();

        $this->assertArrayHasKey('job_class', $array);
        $this->assertArrayHasKey('payload', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('attempts', $array);
        $this->assertEquals('TestJob', $array['job_class']);
    }
}

