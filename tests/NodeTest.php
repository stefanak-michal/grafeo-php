<?php

namespace Tests;

use Grafeo\Node;
use Grafeo\Status;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class NodeTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grafeo->openMemory();
    }

    public function testCreateAndGetNode(): void
    {
        $labels = ['Person', 'Developer'];
        $properties = ['name' => 'Alice', 'age' => 30];

        $nodeId = $this->grafeo->createNode($labels, $properties);
        $this->assertIsInt($nodeId);
        $this->assertSame(0, $nodeId);
        $this->assertSame(1, $this->grafeo->nodeCount());

        $node = $this->grafeo->getNode($nodeId);
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame($nodeId, $node->id);
        $this->assertEqualsCanonicalizing($labels, $node->labels);
        $this->assertEquals($properties, $node->properties);
    }

    public function testCreateNodeWithNullProperties(): void
    {
        $nodeId = $this->grafeo->createNode(['Anonymous'], null);
        $this->assertIsInt($nodeId);
        $this->assertSame(0, $nodeId);

        $node = $this->grafeo->getNode($nodeId);
        $this->assertInstanceOf(Node::class, $node);
        $this->assertSame($nodeId, $node->id);
        $this->assertContains('Anonymous', $node->labels);
        $this->assertEmpty($node->properties);
    }

    public function testGetNonExistentNodeReturnsNull(): void
    {
        $node = $this->grafeo->getNode(999999);
        $this->assertNull($node);
    }

    public function testSetAndRemoveNodeProperty(): void
    {
        $nodeId = $this->grafeo->createNode(['User'], ['city' => 'Prague']);

        // Set a new property
        $status = $this->grafeo->setNodeProperty($nodeId, 'country', 'Czechia');
        $this->assertSame(Status::OK, $status);

        // Update an existing property
        $status = $this->grafeo->setNodeProperty($nodeId, 'city', 'Brno');
        $this->assertSame(Status::OK, $status);

        $node = $this->grafeo->getNode($nodeId);
        $this->assertSame('Brno', $node->properties['city']);
        $this->assertSame('Czechia', $node->properties['country']);

        // Remove a property
        $removed = $this->grafeo->removeNodeProperty($nodeId, 'city');
        $this->assertSame(1, $removed);

        $nodeAfterRemove = $this->grafeo->getNode($nodeId);
        $this->assertArrayNotHasKey('city', $nodeAfterRemove->properties);
        $this->assertArrayHasKey('country', $nodeAfterRemove->properties);
    }

    public function testAddAndRemoveNodeLabel(): void
    {
        $nodeId = $this->grafeo->createNode(['User'], null);

        // Add label
        $added = $this->grafeo->addNodeLabel($nodeId, 'Admin');
        $this->assertSame(1, $added);

        $node = $this->grafeo->getNode($nodeId);
        $this->assertContains('User', $node->labels);
        $this->assertContains('Admin', $node->labels);

        // Remove label
        $removed = $this->grafeo->removeNodeLabel($nodeId, 'Admin');
        $this->assertSame(1, $removed);

        $nodeAfterRemove = $this->grafeo->getNode($nodeId);
        $this->assertContains('User', $nodeAfterRemove->labels);
        $this->assertNotContains('Admin', $nodeAfterRemove->labels);
    }

    public function testDeleteNode(): void
    {
        $nodeId = $this->grafeo->createNode(['Temp'], ['foo' => 'bar']);
        $this->assertSame(1, $this->grafeo->nodeCount());

        $deleted = $this->grafeo->deleteNode($nodeId);
        $this->assertSame(1, $deleted);
        $this->assertSame(0, $this->grafeo->nodeCount());
        $this->assertNull($this->grafeo->getNode($nodeId));
    }
}
