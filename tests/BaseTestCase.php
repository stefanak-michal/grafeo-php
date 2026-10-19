<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Grafeo\Grafeo;
use Exception;

abstract class BaseTestCase extends TestCase
{
    protected ?Grafeo $grafeo = null;
    protected string $libPath;

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
        // Ensure database handle is closed after each test
        if ($this->grafeo) {
            try {
                $this->grafeo->close();
            } catch (Exception) {
                // Ignore cleanup errors during teardown
            }
        }
        parent::tearDown();
    }
}
