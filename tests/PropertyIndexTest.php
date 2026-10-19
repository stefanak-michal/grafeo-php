<?php

namespace Tests;

use Grafeo\Status;

class PropertyIndexTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grafeo->openMemory();
    }

    public function testPropertyIndexLifecycle(): void
    {
        // Initially no index exists on 'email'
        $this->assertFalse($this->grafeo->hasPropertyIndex('email'));

        // Create index
        $status = $this->grafeo->createPropertyIndex('email');
        $this->assertSame(Status::OK, $status);
        $this->assertTrue($this->grafeo->hasPropertyIndex('email'));

        // Drop index
        $dropped = $this->grafeo->dropPropertyIndex('email');
        $this->assertSame(1, $dropped);
        $this->assertFalse($this->grafeo->hasPropertyIndex('email'));

        // Dropping non-existent index returns 0
        $droppedAgain = $this->grafeo->dropPropertyIndex('email');
        $this->assertSame(0, $droppedAgain);
    }

    public function testFindNodesByProperty(): void
    {
        $this->grafeo->createPropertyIndex('email');

        $id1 = $this->grafeo->createNode(['User'], ['email' => 'alice@example.com']);
        $id2 = $this->grafeo->createNode(['User'], ['email' => 'bob@example.com']);
        $id3 = $this->grafeo->createNode(['User'], ['email' => 'alice@example.com']);

        $results = $this->grafeo->findNodesByProperty('email', 'alice@example.com');
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertContains($id1, $results);
        $this->assertContains($id3, $results);
        $this->assertNotContains($id2, $results);

        // Search for non-existent property value
        $empty = $this->grafeo->findNodesByProperty('email', 'charlie@example.com');
        $this->assertIsArray($empty);
        $this->assertEmpty($empty);
    }
}
