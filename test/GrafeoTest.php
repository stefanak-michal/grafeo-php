<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Grafeo\Grafeo;

class GrafeoTest extends TestCase {
    private ?Grafeo $grafeo = null;
    private string $libPath;

    protected function setUp(): void {
        parent::setUp();
        
        // Read path from environment variable set in CI, or fallback to local path
        $envPath = getenv('GRAFEO_LIB_PATH');
        $this->libPath = $envPath ? $envPath : (__DIR__ . '/libgrafeo_c.so');
        
        if (!file_exists($this->libPath)) {
            $this->markTestSkipped("Grafeo shared library not found at: {$this->libPath}");
        }

        $this->grafeo = new Grafeo($this->libPath);
    }

    protected function tearDown(): void {
        // Ensure database handle is closed after each test
        if ($this->grafeo) {
            try {
                $this->grafeo->close();
            } catch (Exception $e) {
                // Ignore cleanup errors during teardown
            }
        }
        parent::tearDown();
    }

    public function testVersionReturnsString(): void {
        $version = $this->grafeo->version();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }

    public function testOpenMemoryDatabase(): void {
        $this->grafeo->openMemory();
        // If it didn't throw an exception, it successfully opened in memory
        $this->assertTrue(true);
        
        $this->assertEquals(0, $this->grafeo->nodeCount());
        $this->assertEquals(0, $this->grafeo->edgeCount());
    }

    public function testNodeAndEdgeCreation(): void {
        $this->grafeo->openMemory();

        // Create nodes with labels (JSON array) and properties (JSON object)[cite: 2]
        $alixId = $this->grafeo->createNode('["Person"]', '{"name":"Alix","age":30}');
        $gusId  = $this->grafeo->createNode('["Person"]', '{"name":"Gus","age":25}');

        $this->assertEquals(0, $alixId);
        $this->assertEquals(1, $gusId);
        $this->assertEquals(2, $this->grafeo->nodeCount());

        // Create an edge connecting them[cite: 2]
        $edgeId = $this->grafeo->createEdge($alixId, $gusId, 'KNOWS', '{"since":2020}');
        
        $this->assertEquals(0, $edgeId);
        $this->assertEquals(1, $this->grafeo->edgeCount());
    }

    public function testExecuteQuery(): void {
        $this->grafeo->openMemory();

        // Seed some data[cite: 2]
        $this->grafeo->createNode('["Person"]', '{"name":"Alix","age":30}');
        $this->grafeo->createNode('["Person"]', '{"name":"Gus","age":25}');

        // Execute query[cite: 2]
        $jsonResult = $this->grafeo->execute('MATCH (p:Person) RETURN p.name, p.age');
        
        $this->assertNotNull($jsonResult);
        
        $data = json_decode($jsonResult, true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
    }

    public function testFilePersistence(): void {
        $dbPath = __DIR__ . '/test_db.grafeo';
        
        // Clean up any old test file
        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }

        try {
            // Open persistent database file
            $this->grafeo->open($dbPath);
            $this->grafeo->createNode('["Test"]', '{"val":123}');
            $this->assertEquals(1, $this->grafeo->nodeCount());
            $this->grafeo->close();

            // Reopen the same file to verify persistence
            $grafeo2 = new Grafeo($this->libPath);
            $grafeo2->open($dbPath);
            
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
