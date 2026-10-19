<?php

namespace Grafeo;

use FFI;
use JsonException;

class Transaction
{
    public function __construct(
        private readonly FFI       $ffi,
        private readonly FFI\Cdata $tx
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function execute(string $query, ?array $params = null, Language $language = Language::GQL): ?Result
    {
        $pointer = $this->ffi->grafeo_transaction_execute_language(
            $this->tx,
            $language->value,
            $query,
            $params ? json_encode($params, JSON_THROW_ON_ERROR) : null
        );
        return $pointer ? new Result($this->ffi, $pointer) : null;
    }

    public function commit(): Status
    {
        $result = Status::from((int)$this->ffi->grafeo_commit($this->tx));
        $this->ffi->grafeo_free_transaction($this->tx);
        return $result;
    }

    public function rollback(): Status
    {
        $result = Status::from((int)$this->ffi->grafeo_rollback($this->tx));
        $this->ffi->grafeo_free_transaction($this->tx);
        return $result;
    }
}
