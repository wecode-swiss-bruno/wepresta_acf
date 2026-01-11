<?php

declare(strict_types=1);

namespace WeprestaAcf\Tests\Extension\Import;

use PHPUnit\Framework\TestCase;
use WeprestaAcf\Extension\Import\Parser\CsvParser;

class CsvParserTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/wedev_csv_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Nettoyer
        $files = glob($this->tempDir . '/*');

        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }

    public function testParseSimpleCsv(): void
    {
        $csvContent = "id;name;price\n1;Product A;19.99\n2;Product B;29.99";
        $filePath = $this->tempDir . '/test.csv';
        file_put_contents($filePath, $csvContent);

        $parser = new CsvParser();
        $rows = $parser->parse($filePath);

        $this->assertCount(2, $rows);
        $this->assertEquals('1', $rows[0]['id']);
        $this->assertEquals('Product A', $rows[0]['name']);
        $this->assertEquals('19.99', $rows[0]['price']);
    }

    public function testParseWithDifferentDelimiter(): void
    {
        $csvContent = "id,name,price\n1,Product A,19.99";
        $filePath = $this->tempDir . '/test.csv';
        file_put_contents($filePath, $csvContent);

        $parser = new CsvParser(delimiter: ',');
        $rows = $parser->parse($filePath);

        $this->assertCount(1, $rows);
        $this->assertEquals('Product A', $rows[0]['name']);
    }

    public function testParseWithQuotedValues(): void
    {
        $csvContent = "id;name;description\n1;\"Product, A\";\"Description with \"\"quotes\"\"\"";
        $filePath = $this->tempDir . '/test.csv';
        file_put_contents($filePath, $csvContent);

        $parser = new CsvParser();
        $rows = $parser->parse($filePath);

        $this->assertEquals('Product, A', $rows[0]['name']);
    }

    public function testParseSkipsEmptyLines(): void
    {
        $csvContent = "id;name\n1;A\n\n2;B\n\n";
        $filePath = $this->tempDir . '/test.csv';
        file_put_contents($filePath, $csvContent);

        $parser = new CsvParser();
        $rows = $parser->parse($filePath);

        $this->assertCount(2, $rows);
    }

    public function testWriteCsv(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Product A', 'price' => 19.99],
            ['id' => 2, 'name' => 'Product B', 'price' => 29.99],
        ];
        $columns = ['id', 'name', 'price'];
        $filePath = $this->tempDir . '/output.csv';

        $parser = new CsvParser();
        $parser->write($filePath, $data, $columns);

        $this->assertFileExists($filePath);

        // Re-lire le fichier
        $rows = $parser->parse($filePath);

        $this->assertCount(2, $rows);
        $this->assertEquals('1', $rows[0]['id']);
    }

    public function testGetContentType(): void
    {
        $parser = new CsvParser();

        $this->assertEquals('text/csv; charset=utf-8', $parser->getContentType());
    }

    public function testGetFileExtension(): void
    {
        $parser = new CsvParser();

        $this->assertEquals('csv', $parser->getFileExtension());
    }
}
