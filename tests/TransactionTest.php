<?php

namespace Tests;

use Grafeo\Isolation;
use Grafeo\Status;
use Grafeo\Transaction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class TransactionTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grafeo->openMemory();
    }

    public function testBeginTransactionDefault(): void
    {
        $tx = $this->grafeo->beginTransaction();
        $this->assertInstanceOf(Transaction::class, $tx);
        $status = $tx->commit();
        $this->assertSame(Status::OK, $status);
    }

    public function testBeginTransactionWithIsolation(): void
    {
        $tx = $this->grafeo->beginTransaction(Isolation::GRAFEO_ISOLATION_READ_COMMITTED);
        $this->assertInstanceOf(Transaction::class, $tx);
        $status = $tx->commit();
        $this->assertSame(Status::OK, $status);

        $tx2 = $this->grafeo->beginTransaction(Isolation::GRAFEO_ISOLATION_SNAPSHOT);
        $this->assertInstanceOf(Transaction::class, $tx2);
        $status2 = $tx2->commit();
        $this->assertSame(Status::OK, $status2);
    }

    public function testTransactionCommit(): void
    {
        $tx = $this->grafeo->beginTransaction();
        $this->assertInstanceOf(Transaction::class, $tx);

        $result = $tx->execute("INSERT (:User {name: 'Alice', age: 25})");
        $this->assertNotNull($result);

        $status = $tx->commit();
        $this->assertSame(Status::OK, $status);

        $this->assertSame(1, $this->grafeo->nodeCount());
    }

    public function testTransactionRollback(): void
    {
        $tx = $this->grafeo->beginTransaction();
        $this->assertInstanceOf(Transaction::class, $tx);

        $result = $tx->execute("INSERT (:User {name: 'Temporary'})");
        $this->assertNotNull($result);

        $status = $tx->rollback();
        $this->assertSame(Status::OK, $status);

        $this->assertSame(0, $this->grafeo->nodeCount());
    }

    public function testTransactionExecuteWithParams(): void
    {
        $tx = $this->grafeo->beginTransaction();
        $this->assertInstanceOf(Transaction::class, $tx);

        $result = $tx->execute(
            "INSERT (:Product {name: \$name, price: \$price})",
            ['name' => 'Book', 'price' => 19.99]
        );
        $this->assertNotNull($result);

        $status = $tx->commit();
        $this->assertSame(Status::OK, $status);
        $this->assertSame(1, $this->grafeo->nodeCount());
    }
}
