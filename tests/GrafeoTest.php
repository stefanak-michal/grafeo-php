<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Grafeo\Grafeo;
use Exception;

class GrafeoTest extends TestCase
{
    private ?Grafeo $grafeo = null;
    private string $libPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Read path from environment variable set in CI, or fallback to local path
        $envPath = getenv('GRAFEO_LIB_PATH');
        $this->libPath = $envPath ? $envPath : (__DIR__ . DIRECTORY_SEPARATOR .
        match (PHP_OS_FAMILY) {
            'Windows' => 'grafeo_c.dll',
            'Linux' => 'libgrafeo_c.so',
            'Mac' => 'libgrafeo_c.dylib'
        });

        if (!file_exists($this->libPath)) {
            $this->markTestSkipped("Grafeo shared library not found at: {$this->libPath}");
        }

        $this->grafeo = new Grafeo($this->libPath);
    }

    protected function tearDown(): void
    {
        // Ensure database handle is closed after each tests
        if ($this->grafeo) {
            try {
                $this->grafeo->close();
            } catch (Exception) {
                // Ignore cleanup errors during teardown
            }
        }
        parent::tearDown();
    }

    public function testVersionReturnsString(): void
    {
        $version = $this->grafeo->version();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }

    public function testOpenMemoryDatabase(): void
    {
        $this->assertTrue($this->grafeo->openMemory());
        $this->assertEquals(0, $this->grafeo->nodeCount());
        $this->assertEquals(0, $this->grafeo->edgeCount());
    }

    public function testFilePersistence(): void
    {
        $dbPath = __DIR__ . '/test_db.grafeo';

        // Clean up any old tests file
        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }

        try {
            // Open persistent database file
            $this->assertTrue($this->grafeo->open($dbPath));
            $this->grafeo->createNode(['Test'], ['val' => 123]);
            $this->assertEquals(1, $this->grafeo->nodeCount());
            $this->grafeo->close();

            // Reopen the same file to verify persistence
            $grafeo2 = new Grafeo($this->libPath);
            $this->assertTrue($grafeo2->open($dbPath));

            $this->assertEquals(1, $grafeo2->nodeCount());

            $grafeo2->close();
        } finally {
            // Clean up file storage
            if (file_exists($dbPath)) {
                @unlink($dbPath);
            }
        }
    }
}
