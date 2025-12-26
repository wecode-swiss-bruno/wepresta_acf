<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Import;

use WeprestaAcf\Extension\Import\ImportResult;
use PHPUnit\Framework\TestCase;

class ImportResultTest extends TestCase
{
    public function testInitialValues(): void
    {
        $result = new ImportResult();

        $this->assertEquals(0, $result->getProcessed());
        $this->assertEquals(0, $result->getCreated());
        $this->assertEquals(0, $result->getUpdated());
        $this->assertEquals(0, $result->getSkipped());
        $this->assertEquals(0, $result->getErrorCount());
        $this->assertEquals(0, $result->getWarningCount());
    }

    public function testIncrementProcessed(): void
    {
        $result = new ImportResult();
        $result->incrementProcessed();
        $result->incrementProcessed();

        $this->assertEquals(2, $result->getProcessed());
    }

    public function testIncrementCreated(): void
    {
        $result = new ImportResult();
        $result->incrementCreated();

        $this->assertEquals(1, $result->getCreated());
    }

    public function testIncrementUpdated(): void
    {
        $result = new ImportResult();
        $result->incrementUpdated();

        $this->assertEquals(1, $result->getUpdated());
    }

    public function testIncrementSkipped(): void
    {
        $result = new ImportResult();
        $result->incrementSkipped();

        $this->assertEquals(1, $result->getSkipped());
    }

    public function testAddError(): void
    {
        $result = new ImportResult();
        $result->addError(5, 'Missing required field');
        $result->addError(10, 'Invalid format');

        $errors = $result->getErrors();

        $this->assertCount(2, $errors);
        $this->assertEquals(5, $errors[0]['line']);
        $this->assertEquals('Missing required field', $errors[0]['message']);
    }

    public function testAddWarning(): void
    {
        $result = new ImportResult();
        $result->addWarning(3, 'Duplicate detected');

        $warnings = $result->getWarnings();

        $this->assertCount(1, $warnings);
        $this->assertEquals(3, $warnings[0]['line']);
    }

    public function testHasErrors(): void
    {
        $result = new ImportResult();

        $this->assertFalse($result->hasErrors());

        $result->addError(1, 'Error');

        $this->assertTrue($result->hasErrors());
    }

    public function testIsSuccess(): void
    {
        $result = new ImportResult();

        $this->assertTrue($result->isSuccess());

        $result->addError(1, 'Error');

        $this->assertFalse($result->isSuccess());
    }

    public function testToArray(): void
    {
        $result = new ImportResult();
        $result->incrementProcessed();
        $result->incrementCreated();
        $result->addError(1, 'Test error');

        $array = $result->toArray();

        $this->assertArrayHasKey('processed', $array);
        $this->assertArrayHasKey('created', $array);
        $this->assertArrayHasKey('updated', $array);
        $this->assertArrayHasKey('skipped', $array);
        $this->assertArrayHasKey('errors', $array);
        $this->assertArrayHasKey('warnings', $array);

        $this->assertEquals(1, $array['processed']);
        $this->assertEquals(1, $array['created']);
        $this->assertEquals(1, $array['errors']);
    }

    public function testGetSummary(): void
    {
        $result = new ImportResult();
        $result->incrementProcessed();
        $result->incrementProcessed();
        $result->incrementCreated();
        $result->incrementUpdated();

        $summary = $result->getSummary();

        $this->assertStringContainsString('Processed: 2', $summary);
        $this->assertStringContainsString('Created: 1', $summary);
        $this->assertStringContainsString('Updated: 1', $summary);
    }
}

