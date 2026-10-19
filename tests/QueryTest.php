<?php

namespace Tests;

use Grafeo\Edge;
use Grafeo\Node;
use Grafeo\Result;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class QueryTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grafeo->openMemory();
    }

    public function testExecuteInsertAndFetch(): void
    {
        $result = $this->grafeo->execute("INSERT (:User {name: 'Alice', age: 30})");
        $this->assertInstanceOf(Result::class, $result);

        $rows = $result->fetch();
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);
        $this->assertSame(1, $result->rowCount());
        $this->assertGreaterThanOrEqual(0, $result->executionTimeMS());
        $this->assertGreaterThanOrEqual(0, $result->rowsScanned());
    }

    public function testExecuteWithParameters(): void
    {
        $this->grafeo->execute("INSERT (:User {name: 'Alice', score: 100})");
        $this->grafeo->execute("INSERT (:User {name: 'Bob', score: 200})");

        $result = $this->grafeo->execute(
            "MATCH (u:User {name: \$name}) RETURN u.score",
            ['name' => 'Bob']
        );

        $this->assertInstanceOf(Result::class, $result);
        $rows = $result->fetch();
        $this->assertCount(1, $rows);
        $this->assertSame(200, $rows[0]['u.score']);
    }

    public function testExtractNodesAndEdgesFromResult(): void
    {
        $this->grafeo->execute(
            "INSERT (a:Person {name: 'Alice'})-[r:KNOWS {since: 2020}]->(b:Person {name: 'Bob'})"
        );

        $result = $this->grafeo->execute("MATCH (a)-[r]->(b) RETURN a, r, b");
        $this->assertInstanceOf(Result::class, $result);

        $nodes = $result->nodes();
        $this->assertIsArray($nodes);
        $this->assertCount(2, $nodes);
        $this->assertContainsOnlyInstancesOf(Node::class, $nodes);

        $edges = $result->edges();
        $this->assertIsArray($edges);
        $this->assertCount(1, $edges);
        $this->assertContainsOnlyInstancesOf(Edge::class, $edges);
        $this->assertSame('KNOWS', $edges[0]->type);
        $this->assertSame(2020, $edges[0]->properties['since']);
    }
}
