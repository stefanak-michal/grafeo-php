<?php

namespace Grafeo;

use FFI;
use JsonException;

/**
 * @internal Experimental streaming not implemented.
 */
class Grafeo
{
    private FFI $ffi;
    private ?FFI\CData $db = null;

    /**
     * @param string $libraryPath Path to the Grafeo shared library (dll, so, etc.).
     */
    public function __construct(string $libraryPath)
    {
        $this->ffi = FFI::cdef("
            typedef int GrafeoStatus;
            typedef int GrafeoIsolationLevel;

            typedef struct GrafeoDatabase GrafeoDatabase;
            typedef struct GrafeoTransaction GrafeoTransaction;
            typedef struct GrafeoResult GrafeoResult;
            typedef struct GrafeoNode GrafeoNode;
            typedef struct GrafeoEdge GrafeoEdge;
            typedef struct GrafeoStream GrafeoStream;

            const char* grafeo_last_error(void);
            void        grafeo_clear_error(void);

            GrafeoDatabase* grafeo_open_memory(void);
            GrafeoDatabase* grafeo_open(const char* path);
            GrafeoDatabase* grafeo_open_read_only(const char* path);
            GrafeoStatus    grafeo_close(GrafeoDatabase* db);
            void            grafeo_free_database(GrafeoDatabase* db);
            const char*     grafeo_version(void);

            void  grafeo_set_cdc_enabled(GrafeoDatabase* db, bool enabled);
            bool  grafeo_is_cdc_enabled(GrafeoDatabase* db);

            GrafeoResult* grafeo_execute_language(GrafeoDatabase* db, const char* language, const char* query, const char* params_json);

            const char* grafeo_result_json(const GrafeoResult* result);
            size_t      grafeo_result_row_count(const GrafeoResult* result);
            double      grafeo_result_execution_time_ms(const GrafeoResult* result);
            unsigned long long grafeo_result_rows_scanned(const GrafeoResult* result);
            const char* grafeo_result_nodes_json(const GrafeoResult* result);
            const char* grafeo_result_edges_json(const GrafeoResult* result);
            void        grafeo_free_result(GrafeoResult* result);

            GrafeoStatus  grafeo_set_schema(GrafeoDatabase* db, const char* name);
            GrafeoStatus  grafeo_reset_schema(GrafeoDatabase* db);
            const char*   grafeo_current_schema(const GrafeoDatabase* db);

            unsigned long long grafeo_create_node(GrafeoDatabase* db, const char* labels_json, const char* properties_json);
            GrafeoStatus       grafeo_get_node(GrafeoDatabase* db, unsigned long long id, GrafeoNode** out);
            int                grafeo_delete_node(GrafeoDatabase* db, unsigned long long id);
            GrafeoStatus       grafeo_set_node_property(GrafeoDatabase* db, unsigned long long id, const char* key, const char* value_json);
            int                grafeo_remove_node_property(GrafeoDatabase* db, unsigned long long id, const char* key);
            int                grafeo_add_node_label(GrafeoDatabase* db, unsigned long long id, const char* label);
            int                grafeo_remove_node_label(GrafeoDatabase* db, unsigned long long id, const char* label);
            char*              grafeo_get_node_labels(GrafeoDatabase* db, unsigned long long id);

            unsigned long long grafeo_node_id(const GrafeoNode* node);
            const char*        grafeo_node_labels_json(const GrafeoNode* node);
            const char*        grafeo_node_properties_json(const GrafeoNode* node);
            void               grafeo_free_node(GrafeoNode* node);

            unsigned long long grafeo_create_edge(GrafeoDatabase* db, unsigned long long source_id, unsigned long long target_id, const char* edge_type, const char* properties_json);
            GrafeoStatus       grafeo_get_edge(GrafeoDatabase* db, unsigned long long id, GrafeoEdge** out);
            int                grafeo_delete_edge(GrafeoDatabase* db, unsigned long long id);
            GrafeoStatus       grafeo_set_edge_property(GrafeoDatabase* db, unsigned long long id, const char* key, const char* value_json);
            int                grafeo_remove_edge_property(GrafeoDatabase* db, unsigned long long id, const char* key);

            unsigned long long grafeo_edge_id(const GrafeoEdge* edge);
            unsigned long long grafeo_edge_source_id(const GrafeoEdge* edge);
            unsigned long long grafeo_edge_target_id(const GrafeoEdge* edge);
            const char*        grafeo_edge_type(const GrafeoEdge* edge);
            const char*        grafeo_edge_properties_json(const GrafeoEdge* edge);
            void               grafeo_free_edge(GrafeoEdge* edge);

            GrafeoStatus       grafeo_create_property_index(GrafeoDatabase* db, const char* property);
            int                grafeo_drop_property_index(GrafeoDatabase* db, const char* property);
            int                grafeo_has_property_index(GrafeoDatabase* db, const char* property);
            GrafeoStatus       grafeo_find_nodes_by_property(GrafeoDatabase* db, const char* property, const char* value_json, uint64_t** out_ids, size_t* out_count);

            GrafeoStatus       grafeo_create_vector_index(GrafeoDatabase* db, const char* label, const char* property, int32_t dimensions, const char* metric, int32_t m, int32_t ef_construction);
            int32_t            grafeo_drop_vector_index(GrafeoDatabase* db, const char* label, const char* property);
            GrafeoStatus       grafeo_rebuild_vector_index(GrafeoDatabase* db, const char* label, const char* property);
            GrafeoStatus       grafeo_vector_search(GrafeoDatabase* db, const char* label, const char* property, const float* query, size_t query_len, size_t k, int32_t ef, uint64_t** out_ids, float** out_distances, size_t* out_count);
            GrafeoStatus       grafeo_mmr_search(GrafeoDatabase* db, const char* label, const char* property, const float* query, size_t query_len, size_t k, int32_t fetch_k, float lambda, int32_t ef, uint64_t** out_ids, float** out_distances, size_t* out_count);
            GrafeoStatus       grafeo_batch_create_nodes(GrafeoDatabase* db, const char* label, const char* property, const float* vectors, size_t vector_count, size_t dimensions, uint64_t** out_ids, size_t* out_count);
            void               grafeo_free_vector_results(uint64_t* ids, float* distances, size_t count);

            size_t             grafeo_node_count(GrafeoDatabase* db);
            size_t             grafeo_edge_count(GrafeoDatabase* db);

            GrafeoTransaction* grafeo_begin_transaction(GrafeoDatabase* db);
            GrafeoTransaction* grafeo_begin_transaction_with_isolation(GrafeoDatabase* db, GrafeoIsolationLevel isolation);
            GrafeoResult*      grafeo_transaction_execute_language(GrafeoTransaction* tx, const char* language, const char* query, const char* params_json);
            GrafeoStatus       grafeo_commit(GrafeoTransaction* tx);
            GrafeoStatus       grafeo_rollback(GrafeoTransaction* tx);
            void               grafeo_free_transaction(GrafeoTransaction* tx);

            char*              grafeo_info(GrafeoDatabase* db);
            GrafeoStatus       grafeo_save(GrafeoDatabase* db, const char* path);
            GrafeoStatus       grafeo_wal_checkpoint(GrafeoDatabase* db);
            GrafeoStatus       grafeo_compact(GrafeoDatabase* db);

            void               grafeo_free_string(char* s);
        ", $libraryPath);
    }

    // todo property index group
    // todo vector operations

    /**
     * Get the last error message from the Grafeo library.
     *
     * @return ?string The last error message.
     */
    public function getLastError(): ?string
    {
        return $this->ffi->grafeo_last_error();
    }

    /**
     * Clear the last error message in the Grafeo library.
     */
    public function clearError(): void
    {
        $this->ffi->grafeo_clear_error();
    }

    /**
     * Get the version of the Grafeo library.
     *
     * @return string The version string.
     */
    public function version(): string
    {
        $ver = $this->ffi->grafeo_version();
        return is_string($ver) ? $ver : FFI::string($ver);
    }

    // --- Lifecycle ---

    /**
     * Open an in-memory graph database.
     *
     * @link https://grafeo.dev/user-guide/persistence/in-memory/
     * @internal Data will not persist after the script ends.
     */
    public function openMemory(): bool
    {
        $this->db = $this->ffi->grafeo_open_memory();
        return $this->db != null;
    }

    /**
     * Open a graph database at the specified path.
     *
     * @link https://grafeo.dev/user-guide/persistence/persistent/
     * @internal File access uses exclusive lock.
     * @param string $path Path to the database file.
     */
    public function open(string $path): bool
    {
        $this->db = $this->ffi->grafeo_open($path);
        return $this->db !== null;
    }

    /**
     * Open a graph database in read-only mode at the specified path.
     *
     * @link https://grafeo.dev/user-guide/persistence/persistent/
     * @internal File access uses exclusive lock.
     * @param string $path Path to the database file.
     */
    public function openReadOnly(string $path): bool
    {
        $this->db = $this->ffi->grafeo_open_read_only($path);
        return $this->db !== null;
    }

    #[\Deprecated('same as open(), removed in 0.7.0')]
    public function openSingleFile(string $path): bool
    {
        $this->db = $this->ffi->grafeo_open_single_file($path);
        return $this->db !== null;
    }

    /**
     * Close the graph database.
     */
    public function close(): void
    {
        if ($this->db) {
            $status = $this->ffi->grafeo_close($this->db);
            if (Status::tryFrom((int)$status) === Status::OK) {
                $this->ffi->grafeo_free_database($this->db);
            }
            $this->db = null;
        }
    }

    // --- CDC ---

    /**
     * Enable or disable Change Data Capture (CDC) in the database.
     *
     * @link https://grafeo.dev/user-guide/cdc/
     * @param bool $enabled True to enable CDC, false to disable.
     */
    public function setCdcEnabled(bool $enabled): void
    {
        $this->ffi->grafeo_set_cdc_enabled($this->db, $enabled);
    }

    /**
     * Check if Change Data Capture (CDC) is enabled in the database.
     *
     * @link https://grafeo.dev/user-guide/cdc/
     */
    public function isCdcEnabled(): bool
    {
        return (bool)$this->ffi->grafeo_is_cdc_enabled($this->db);
    }

    // --- Query Execution ---

    /**
     * Execute a query in a specific language.
     *
     * @param string $query The query to execute.
     * @param ?array $params The parameters for the query.
     * @param Language $language Query language, default is GQL.
     * @return ?Result Result instance or null on error.
     * @throws JsonException
     */
    public function execute(string $query, ?array $params = null, Language $language = Language::GQL): ?Result
    {
        $pointer = $this->ffi->grafeo_execute_language(
            $this->db,
            $language->value,
            $query,
            $params ? json_encode($params, JSON_THROW_ON_ERROR) : null
        );
        return $pointer ? new Result($this->ffi, $pointer) : null;
    }

    // --- Node CRUD ---

    /**
     * @return int Returns new node ID, or UINT64_MAX on error.
     * @throws JsonException
     */
    public function createNode(array $labels, ?array $properties): int
    {
        return (int)$this->ffi->grafeo_create_node(
            $this->db,
            json_encode($labels, JSON_THROW_ON_ERROR),
            json_encode($properties, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @throws JsonException
     */
    public function getNode(int $id): ?Node
    {
        $nodePtr = $this->ffi->new("GrafeoNode*");
        $status = Status::from((int)$this->ffi->grafeo_get_node($this->db, $id, FFI::addr($nodePtr)));
        if ($status !== Status::OK) {
            return null;
        }

        $data = [
            'id' => $id,
            'labels' => json_decode($this->ffi->grafeo_node_labels_json($nodePtr), true, JSON_THROW_ON_ERROR),
            'properties' => json_decode($this->ffi->grafeo_node_properties_json($nodePtr), true, JSON_THROW_ON_ERROR),
        ];
        $this->ffi->grafeo_free_node($nodePtr);
        return new Node($data);
    }

    /**
     * @throws JsonException
     */
    public function setNodeProperty(int $id, string $key, mixed $value): Status
    {
        return Status::from((int)$this->ffi->grafeo_set_node_property(
            $this->db,
            $id,
            $key,
            json_encode($value, JSON_THROW_ON_ERROR)
        ));
    }

    /**
     * * @return int Returns 1 if deleted, 0 if not found, -1 on null pointer.
     */
    public function removeNodeProperty(int $id, string $key): int
    {
        return (int)$this->ffi->grafeo_remove_node_property($this->db, $id, $key);
    }

    /**
     * * @return int Returns 1 if deleted, 0 if not found, -1 on null pointer.
     */
    public function addNodeLabel(int $id, string $label): int
    {
        return (int)$this->ffi->grafeo_add_node_label($this->db, $id, $label);
    }

    /**
     * * @return int Returns 1 if deleted, 0 if not found, -1 on null pointer.
     */
    public function removeNodeLabel(int $id, string $label): int
    {
        return (int)$this->ffi->grafeo_remove_node_label($this->db, $id, $label);
    }

    /**
     * @return int Returns 1 if deleted, 0 if not found, -1 on null pointer.
     */
    public function deleteNode(int $id): int
    {
        return (int)$this->ffi->grafeo_delete_node($this->db, $id);
    }

    // --- Edge CRUD ---

    /**
     * @param int $sourceId The ID of the source node.
     * @param int $targetId The ID of the target node.
     * @return int Returns new edge ID, or UINT64_MAX on error.
     * @throws JsonException
     */
    public function createEdge(int $sourceId, int $targetId, string $edgeType, ?string $properties): int
    {
        return (int)$this->ffi->grafeo_create_edge(
            $this->db,
            $sourceId,
            $targetId,
            $edgeType,
            json_encode($properties, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @throws JsonException
     */
    public function getEdge(int $id): ?Edge
    {
        $edgePtr = $this->ffi->new("GrafeoEdge*");
        $status = Status::from((int)$this->ffi->grafeo_get_edge($this->db, $id, FFI::addr($edgePtr)));
        if ($status !== Status::OK) {
            return null;
        }

        $data = [
            'id' => $id,
            'source_id' => $this->ffi->grafeo_edge_source_id($edgePtr),
            'target_id' => $this->ffi->grafeo_edge_target_id($edgePtr),
            'type' => $this->ffi->grafeo_edge_type($edgePtr),
            'properties' => json_decode($this->ffi->grafeo_edge_properties_json($edgePtr), true, JSON_THROW_ON_ERROR),
        ];
        $this->ffi->grafeo_free_edge($edgePtr);
        return new Edge($data);
    }

    /**
     * @throws JsonException
     */
    public function setEdgeProperty(int $id, string $key, mixed $value): Status
    {
        return Status::from((int)$this->ffi->grafeo_set_edge_property(
            $this->db,
            $id,
            $key,
            json_encode($value, JSON_THROW_ON_ERROR)
        ));
    }

    /**
     * * @return int Returns 1 if deleted, 0 if not found, -1 on null pointer.
     */
    public function removeEdgeProperty(int $id, string $key): int
    {
        return (int)$this->ffi->grafeo_remove_edge_property($this->db, $id, $key);
    }

    /**
     * @return int Returns 1 if deleted, 0 if not found, -1 on null pointer.
     */
    public function deleteEdge(int $id): int
    {
        return (int)$this->ffi->grafeo_delete_edge($this->db, $id);
    }

    // --- Schema Context ---

    public function setSchema(string $name): Status
    {
        return Status::from((int)$this->ffi->grafeo_set_schema($this->db, $name));
    }

    public function resetSchema(): Status
    {
        return Status::from((int)$this->ffi->grafeo_reset_schema($this->db));
    }

    /**
     * @return ?string The current schema, or null if not found.
     */
    public function currentSchema(): ?string
    {
        $ptr = $this->ffi->grafeo_current_schema($this->db);
        return $ptr ? FFI::string($ptr) : null;
    }

    // --- Statistics & Admin ---

    /**
     * Get total number of nodes in the database.
     */
    public function nodeCount(): int
    {
        return (int)$this->ffi->grafeo_node_count($this->db);
    }

    /**
     * Get total number of edges in the database.
     */
    public function edgeCount(): int
    {
        return (int)$this->ffi->grafeo_edge_count($this->db);
    }

    /**
     * Get information about the database.
     *
     * @throws JsonException
     */
    public function info(): array
    {
        $ptr = $this->ffi->grafeo_info($this->db);
        $output = json_decode($ptr, true, JSON_THROW_ON_ERROR);
        $this->ffi->grafeo_free_string($ptr);
        return $output;
    }

    public function save(string $path): Status
    {
        return Status::from((int)$this->ffi->grafeo_save($this->db, $path));
    }

    public function walCheckpoint(): Status
    {
        return Status::from((int)$this->ffi->grafeo_wal_checkpoint($this->db));
    }

    /**
     * Compacts the database: writes a checkpoint of a persistent database (an in-memory or read-only one writes none)
     * and drops the old versions no open transaction can see any more.
     *
     * @link https://grafeo.dev/api/c/#compact-store
     * @return Status The result of the operation.
     */
    public function compact(): Status
    {
        return Status::from((int)$this->ffi->grafeo_compact($this->db));
    }

    // --- Transaction ---

    public function beginTransaction(?Isolation $isolation = null): ?Transaction
    {
        $tx = $isolation
            ? $this->ffi->grafeo_begin_transaction_with_isolation($this->db, $isolation->value)
            : $this->ffi->grafeo_begin_transaction($this->db);
        if (!$tx) {
            return null;
        }
        return new Transaction($this->ffi, $tx);
    }
}
