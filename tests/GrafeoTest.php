<?php

namespace Tests;

use Grafeo\Grafeo;
use Grafeo\Status;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class GrafeoTest extends BaseTestCase
{
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

        // Clean up any old test file
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
            if (file_exists($dbPath)) {
                @unlink($dbPath);
            }
        }
    }

    public function testOpenReadOnly(): void
    {
        $dbPath = __DIR__ . '/test_ro_db.grafeo';

        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }

        try {
            // Create a persistent database first
            $this->assertTrue($this->grafeo->open($dbPath));
            $this->grafeo->createNode(['Item'], ['name' => 'Widget']);
            $this->grafeo->close();

            // Reopen in read-only mode
            $roGrafeo = new Grafeo($this->libPath);
            $this->assertTrue($roGrafeo->openReadOnly($dbPath));
            $this->assertEquals(1, $roGrafeo->nodeCount());
            $roGrafeo->close();
        } finally {
            if (file_exists($dbPath)) {
                @unlink($dbPath);
            }
        }
    }

    public function testErrorHandling(): void
    {
        $this->assertTrue($this->grafeo->openMemory());

        // Initially no error
        $this->assertNull($this->grafeo->getLastError());

        // Execute an invalid query to trigger an error
        $result = $this->grafeo->execute('INVALID QUERY SYNTAX');
        $this->assertNull($result);

        $error = $this->grafeo->getLastError();
        $this->assertNotNull($error);
        $this->assertIsString($error);
        $this->assertNotEmpty($error);

        // Clear error
        $this->grafeo->clearError();
        $this->assertNull($this->grafeo->getLastError());
    }

    public function testInfo(): void
    {
        $this->assertTrue($this->grafeo->openMemory());
        $info = $this->grafeo->info();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('node_count', $info);
        $this->assertArrayHasKey('edge_count', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('features', $info);
        $this->assertIsArray($info['features']);
    }

    public function testSaveWalCheckpointAndCompact(): void
    {
        $this->assertTrue($this->grafeo->openMemory());
        $this->grafeo->createNode(['Sample'], ['key' => 'value']);

        $savePath = __DIR__ . '/test_save_admin.grafeo';
        if (file_exists($savePath)) {
            @unlink($savePath);
        }

        try {
            // Test save
            $status = $this->grafeo->save($savePath);
            $this->assertSame(Status::OK, $status);
            $this->assertFileExists($savePath);

            // Test WAL checkpoint
            $status = $this->grafeo->walCheckpoint();
            $this->assertSame(Status::OK, $status);

            // Test compact
            $status = $this->grafeo->compact();
            $this->assertSame(Status::OK, $status);
        } finally {
            if (file_exists($savePath)) {
                @unlink($savePath);
            }
        }
    }
}
