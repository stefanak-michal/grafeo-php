<?php

namespace Tests;

use Grafeo\Status;

class VectorTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grafeo->openMemory();
    }

    public function testVectorIndexLifecycleAndSearch(): void
    {
        // Create vector index
        $status = $this->grafeo->createVectorIndex(
            label: 'Document',
            property: 'embedding',
            dimensions: 3,
            metric: 'cosine',
            m: 16,
            efConstruction: 200
        );
        $this->assertSame(Status::OK, $status);

        // Batch create nodes with 3D embeddings
        $vectors = [
            [1.0, 0.0, 0.0],
            [0.0, 1.0, 0.0],
            [0.0, 0.0, 1.0],
            [0.707, 0.707, 0.0],
        ];

        $nodeIds = $this->grafeo->batchCreateNodes('Document', 'embedding', $vectors, 3);
        $this->assertCount(4, $nodeIds);
        $this->assertSame(4, $this->grafeo->nodeCount());

        // Vector nearest-neighbor search
        $query = [1.0, 0.1, 0.0];
        $results = $this->grafeo->vectorSearch('Document', 'embedding', $query, k: 2);
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertArrayHasKey('distance', $results[0]);
        // The closest node should be node 0 ([1.0, 0.0, 0.0])
        $this->assertSame($nodeIds[0], $results[0]['id']);

        // MMR (Maximal Marginal Relevance) search
        $mmrResults = $this->grafeo->mmrSearch(
            label: 'Document',
            property: 'embedding',
            query: $query,
            k: 2,
            fetchK: 4,
            lambda: 0.5
        );
        $this->assertIsArray($mmrResults);
        $this->assertCount(2, $mmrResults);
        $this->assertArrayHasKey('id', $mmrResults[0]);
        $this->assertArrayHasKey('distance', $mmrResults[0]);

        // Rebuild vector index
        $rebuildStatus = $this->grafeo->rebuildVectorIndex('Document', 'embedding');
        $this->assertSame(Status::OK, $rebuildStatus);

        // Drop vector index
        $dropped = $this->grafeo->dropVectorIndex('Document', 'embedding');
        $this->assertSame(1, $dropped);

        // Dropping non-existent vector index returns 0
        $droppedAgain = $this->grafeo->dropVectorIndex('Document', 'embedding');
        $this->assertSame(0, $droppedAgain);
    }

    public function testBatchCreateNodesWithFlatArray(): void
    {
        $flatVectors = [
            1.0, 2.0,
            3.0, 4.0,
        ];

        $nodeIds = $this->grafeo->batchCreateNodes('Point', 'coords', $flatVectors, 2);
        $this->assertCount(2, $nodeIds);
        $this->assertSame(2, $this->grafeo->nodeCount());
    }
}
