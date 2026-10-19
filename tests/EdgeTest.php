<?php

namespace Tests;

use Grafeo\Edge;
use Grafeo\Status;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class EdgeTest extends BaseTestCase
{
    private int $sourceNodeId;
    private int $targetNodeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grafeo->openMemory();
        $this->sourceNodeId = $this->grafeo->createNode(['User'], ['name' => 'Alice']);
        $this->targetNodeId = $this->grafeo->createNode(['User'], ['name' => 'Bob']);
    }

    public function testCreateAndGetEdgeWithProperties(): void
    {
        $properties = ['since' => 2024, 'weight' => 0.8];
        $edgeId = $this->grafeo->createEdge(
            $this->sourceNodeId,
            $this->targetNodeId,
            'FOLLOWS',
            $properties
        );

        $this->assertIsInt($edgeId);
        $this->assertSame(0, $edgeId);
        $this->assertSame(1, $this->grafeo->edgeCount());

        $edge = $this->grafeo->getEdge($edgeId);
        $this->assertInstanceOf(Edge::class, $edge);
        $this->assertSame($edgeId, $edge->id);
        $this->assertSame('FOLLOWS', $edge->type);
        $this->assertSame($this->sourceNodeId, $edge->sourceId);
        $this->assertSame($this->targetNodeId, $edge->targetId);
        $this->assertEquals($properties, $edge->properties);
    }

    public function testCreateEdgeWithoutProperties(): void
    {
        $edgeId = $this->grafeo->createEdge(
            $this->sourceNodeId,
            $this->targetNodeId,
            'KNOWS'
        );

        $this->assertIsInt($edgeId);
        $this->assertSame(0, $edgeId);

        $edge = $this->grafeo->getEdge($edgeId);
        $this->assertInstanceOf(Edge::class, $edge);
        $this->assertSame($edgeId, $edge->id);
        $this->assertSame('KNOWS', $edge->type);
        $this->assertEmpty($edge->properties);
    }

    public function testCreateEdgeWithInvalidNodesFails(): void
    {
        $edgeId = $this->grafeo->createEdge(999999, 888888, 'INVALID');
        // UINT64_MAX on error casts to -1 in signed 64-bit PHP int
        $this->assertSame(-1, $edgeId);
        $this->assertNotNull($this->grafeo->getLastError());
    }

    public function testGetNonExistentEdgeReturnsNull(): void
    {
        $edge = $this->grafeo->getEdge(999999);
        $this->assertNull($edge);
    }

    public function testSetAndRemoveEdgeProperty(): void
    {
        $edgeId = $this->grafeo->createEdge(
            $this->sourceNodeId,
            $this->targetNodeId,
            'COLLABORATES'
        );

        // Add property
        $status = $this->grafeo->setEdgeProperty($edgeId, 'role', 'developer');
        $this->assertSame(Status::OK, $status);

        // Update property
        $status = $this->grafeo->setEdgeProperty($edgeId, 'role', 'lead');
        $this->assertSame(Status::OK, $status);

        $edge = $this->grafeo->getEdge($edgeId);
        $this->assertSame('lead', $edge->properties['role']);

        // Remove property
        $removed = $this->grafeo->removeEdgeProperty($edgeId, 'role');
        $this->assertSame(1, $removed);

        $edgeAfterRemove = $this->grafeo->getEdge($edgeId);
        $this->assertArrayNotHasKey('role', $edgeAfterRemove->properties);
    }

    public function testDeleteEdge(): void
    {
        $edgeId = $this->grafeo->createEdge(
            $this->sourceNodeId,
            $this->targetNodeId,
            'LIKES'
        );
        $this->assertSame(1, $this->grafeo->edgeCount());

        $deleted = $this->grafeo->deleteEdge($edgeId);
        $this->assertSame(1, $deleted);
        $this->assertSame(0, $this->grafeo->edgeCount());
        $this->assertNull($this->grafeo->getEdge($edgeId));
    }
}
