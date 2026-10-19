<?php

namespace Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class CdcTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->grafeo->openMemory();
    }

    public function testCdcToggle(): void
    {
        // By default, CDC should be disabled
        $this->assertFalse($this->grafeo->isCdcEnabled());

        // Enable CDC
        $this->grafeo->setCdcEnabled(true);
        $this->assertTrue($this->grafeo->isCdcEnabled());

        // Disable CDC
        $this->grafeo->setCdcEnabled(false);
        $this->assertFalse($this->grafeo->isCdcEnabled());
    }
}
