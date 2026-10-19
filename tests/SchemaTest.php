<?php

namespace Tests;

use Grafeo\Status;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SchemaTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grafeo->openMemory();
    }

    public function testSchemaLifecycle(): void
    {
        // Initially, no schema context is set
        $this->assertNull($this->grafeo->currentSchema());

        // Create a new schema via DDL query
        $result = $this->grafeo->execute('CREATE SCHEMA my_schema');
        $this->assertNotNull($result);

        // Switch to the schema
        $status = $this->grafeo->setSchema('my_schema');
        $this->assertSame(Status::OK, $status);
        $this->assertSame('my_schema', $this->grafeo->currentSchema());

        // Reset schema to default
        $resetStatus = $this->grafeo->resetSchema();
        $this->assertSame(Status::OK, $resetStatus);
        $this->assertNull($this->grafeo->currentSchema());
    }
}
