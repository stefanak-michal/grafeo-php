<?php

namespace Grafeo;

use FFI;
use JsonException;

class Result
{
    public function __construct(
        private readonly FFI       $ffi,
        private readonly FFI\Cdata $pointer
    )
    {
    }

    /**
     * Fetch result as array of rows
     * @throws JsonException
     */
    public function fetch(): array
    {
        $json = $this->ffi->grafeo_result_json($this->pointer);
        return json_decode($json, true, JSON_THROW_ON_ERROR);
    }

    public function rowCount(): int
    {
        return (int)$this->ffi->grafeo_result_row_count($this->pointer);

    }

    public function executionTimeMS(): float
    {
        return (float)$this->ffi->grafeo_result_execution_time_ms($this->pointer);
    }

    /**
     * Estimated rows scanned
     */
    public function rowsScanned(): int
    {
        return (int)$this->ffi->grafeo_result_rows_scanned($this->pointer);
    }

    /**
     * @return Node[] deduplicated, extracted from the result
     * @throws JsonException
     */
    public function nodes(): array
    {
        $json = $this->ffi->grafeo_result_nodes_json($this->pointer);
        $decoded = json_decode($json, true, JSON_THROW_ON_ERROR);
        return array_map(fn(array $entity) => new Node($entity), $decoded);
    }

    /**
     * @return Edge[] deduplicated, extracted from the result
     * @throws JsonException
     */
    public function edges(): array
    {
        $json = $this->ffi->grafeo_result_edges_json($this->pointer);
        $decoded = json_decode($json, true, JSON_THROW_ON_ERROR);
        return array_map(fn(array $entity) => new Edge($entity), $decoded);
    }

    public function __destruct()
    {
        try {
            $this->ffi->grafeo_free_result($this->pointer);
        } catch (\Throwable) {

        }
    }
}