<?php

namespace Grafeo;

use FFI;
use Exception;

class Grafeo {
    private FFI $ffi;
    private ?FFI\CData $db = null;

    public function __construct(string $libraryPath) {
        $cdefs = "
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
            GrafeoDatabase* grafeo_open_single_file(const char* path);
            GrafeoStatus    grafeo_close(GrafeoDatabase* db);
            void            grafeo_free_database(GrafeoDatabase* db);
            const char*     grafeo_version(void);

            void  grafeo_set_cdc_enabled(GrafeoDatabase* db, bool enabled);
            bool  grafeo_is_cdc_enabled(GrafeoDatabase* db);

            GrafeoResult* grafeo_execute(GrafeoDatabase* db, const char* query);
            GrafeoResult* grafeo_execute_with_params(GrafeoDatabase* db, const char* query, const char* params_json);
            GrafeoResult* grafeo_execute_cypher(GrafeoDatabase* db, const char* query);
            GrafeoResult* grafeo_execute_cypher_with_params(GrafeoDatabase* db, const char* query, const char* params_json);
            GrafeoResult* grafeo_execute_gremlin(GrafeoDatabase* db, const char* query);
            GrafeoResult* grafeo_execute_gremlin_with_params(GrafeoDatabase* db, const char* query, const char* params_json);
            GrafeoResult* grafeo_execute_graphql(GrafeoDatabase* db, const char* query);
            GrafeoResult* grafeo_execute_graphql_with_params(GrafeoDatabase* db, const char* query, const char* params_json);
            GrafeoResult* grafeo_execute_sparql(GrafeoDatabase* db, const char* query);
            GrafeoResult* grafeo_execute_sparql_with_params(GrafeoDatabase* db, const char* query, const char* params_json);
            GrafeoResult* grafeo_execute_sql(GrafeoDatabase* db, const char* query);
            GrafeoResult* grafeo_execute_sql_with_params(GrafeoDatabase* db, const char* query, const char* params_json);
            GrafeoResult* grafeo_execute_language(GrafeoDatabase* db, const char* language, const char* query, const char* params_json);

            const char* grafeo_result_json(const GrafeoResult* result);
            size_t      grafeo_result_row_count(const GrafeoResult* result);
            double      grafeo_result_execution_time_ms(const GrafeoResult* result);
            uint64_t    grafeo_result_rows_scanned(const GrafeoResult* result);
            const char* grafeo_result_nodes_json(const GrafeoResult* result);
            const char* grafeo_result_edges_json(const GrafeoResult* result);
            void        grafeo_free_result(GrafeoResult* result);

            GrafeoStream* grafeo_stream_open(GrafeoDatabase* db, const char* query);
            char*         grafeo_stream_columns_json(const GrafeoStream* stream);
            GrafeoStatus  grafeo_stream_next_row_json(GrafeoStream* stream, char** out_json);
            void          grafeo_stream_free(GrafeoStream* stream);

            GrafeoStatus  grafeo_set_schema(GrafeoDatabase* db, const char* name);
            GrafeoStatus  grafeo_reset_schema(GrafeoDatabase* db);
            const char*   grafeo_current_schema(const GrafeoDatabase* db);

            uint64_t     grafeo_create_node(GrafeoDatabase* db, const char* labels_json, const char* properties_json);
            GrafeoStatus grafeo_get_node(GrafeoDatabase* db, uint64_t id, GrafeoNode** out);
            int32_t      grafeo_delete_node(GrafeoDatabase* db, uint64_t id);
            GrafeoStatus grafeo_set_node_property(GrafeoDatabase* db, uint64_t id, const char* key, const char* value_json);
            int32_t      grafeo_remove_node_property(GrafeoDatabase* db, uint64_t id, const char* key);
            int32_t      grafeo_add_node_label(GrafeoDatabase* db, uint64_t id, const char* label);
            int32_t      grafeo_remove_node_label(GrafeoDatabase* db, uint64_t id, const char* label);
            char*        grafeo_get_node_labels(GrafeoDatabase* db, uint64_t id);

            uint64_t    grafeo_node_id(const GrafeoNode* node);
            const char* grafeo_node_labels_json(const GrafeoNode* node);
            const char* grafeo_node_properties_json(const GrafeoNode* node);
            void        grafeo_free_node(GrafeoNode* node);

            uint64_t     grafeo_create_edge(GrafeoDatabase* db, uint64_t source_id, uint64_t target_id, const char* edge_type, const char* properties_json);
            GrafeoStatus grafeo_get_edge(GrafeoDatabase* db, uint64_t id, GrafeoEdge** out);
            int32_t      grafeo_delete_edge(GrafeoDatabase* db, uint64_t id);
            GrafeoStatus grafeo_set_edge_property(GrafeoDatabase* db, uint64_t id, const char* key, const char* value_json);
            int32_t      grafeo_remove_edge_property(GrafeoDatabase* db, uint64_t id, const char* key);

            uint64_t    grafeo_edge_id(const GrafeoEdge* edge);
            uint64_t    grafeo_edge_source_id(const GrafeoEdge* edge);
            uint64_t    grafeo_edge_target_id(const GrafeoEdge* edge);
            const char* grafeo_edge_type(const GrafeoEdge* edge);
            const char* grafeo_edge_properties_json(const GrafeoEdge* edge);
            void        grafeo_free_edge(GrafeoEdge* edge);

            GrafeoStatus grafeo_create_property_index(GrafeoDatabase* db, const char* property);
            int32_t      grafeo_drop_property_index(GrafeoDatabase* db, const char* property);
            int32_t      grafeo_has_property_index(GrafeoDatabase* db, const char* property);

            size_t grafeo_node_count(GrafeoDatabase* db);
            size_t grafeo_edge_count(GrafeoDatabase* db);

            GrafeoTransaction* grafeo_begin_transaction(GrafeoDatabase* db);
            GrafeoTransaction* grafeo_begin_transaction_with_isolation(GrafeoDatabase* db, GrafeoIsolationLevel isolation);
            GrafeoResult*      grafeo_transaction_execute(GrafeoTransaction* tx, const char* query);
            GrafeoResult*      grafeo_transaction_execute_with_params(GrafeoTransaction* tx, const char* query, const char* params_json);
            GrafeoResult*      grafeo_transaction_execute_language(GrafeoTransaction* tx, const char* language, const char* query, const char* params_json);
            GrafeoStatus       grafeo_commit(GrafeoTransaction* tx);
            GrafeoStatus       grafeo_rollback(GrafeoTransaction* tx);
            void               grafeo_free_transaction(GrafeoTransaction* tx);

            char*        grafeo_info(GrafeoDatabase* db);
            GrafeoStatus grafeo_save(GrafeoDatabase* db, const char* path);
            GrafeoStatus grafeo_wal_checkpoint(GrafeoDatabase* db);
            GrafeoStatus grafeo_compact(GrafeoDatabase* db);

            void grafeo_free_string(char* s);
        ";

        $this->ffi = FFI::cdef($cdefs, $libraryPath);
    }

    public function getLastError(): string {
        $err = $this->ffi->grafeo_last_error();
        if (!$err) return "Unknown error";
        return is_string($err) ? $err : FFI::string($err);
    }
    
    public function clearError(): void {
        $this->ffi->grafeo_clear_error();
    }

    public function version(): string {
        $ver = $this->ffi->grafeo_version();
        return is_string($ver) ? $ver : FFI::string($ver);
    }

    // --- Lifecycle ---

    public function openMemory(): void {
        $this->db = $this->ffi->grafeo_open_memory();
        if (!$this->db) {
            throw new Exception("Failed to open in-memory database: " . $this->getLastError());
        }
    }

    public function open(string $path): void {
        $this->db = $this->ffi->grafeo_open($path);
        if (!$this->db) {
            throw new Exception("Failed to open database at {$path}: " . $this->getLastError());
        }
    }

    public function openReadOnly(string $path): void {
        $this->db = $this->ffi->grafeo_open_read_only($path);
        if (!$this->db) {
            throw new Exception("Failed to open read-only database at {$path}: " . $this->getLastError());
        }
    }

    public function openSingleFile(string $path): void {
        $this->db = $this->ffi->grafeo_open_single_file($path);
        if (!$this->db) {
            throw new Exception("Failed to open single file database at {$path}: " . $this->getLastError());
        }
    }

    public function close(): void {
        if ($this->db) {
            $status = $this->ffi->grafeo_close($this->db);
            if ($status !== 0) {
                throw new Exception("Error closing database: " . $this->getLastError());
            }
            $this->ffi->grafeo_free_database($this->db);
            $this->db = null;
        }
    }

    // --- CDC ---

    public function setCdcEnabled(bool $enabled): void {
        $this->ffi->grafeo_set_cdc_enabled($this->db, $enabled);
    }

    public function isCdcEnabled(): bool {
        return (bool)$this->ffi->grafeo_is_cdc_enabled($this->db);
    }

    // --- Query Execution ---

    public function execute(string $query): ?string {
        $result = $this->ffi->grafeo_execute($this->db, $query);
        return $this->processResult($result);
    }

    public function executeWithParams(string $query, string $paramsJson): ?string {
        $result = $this->ffi->grafeo_execute_with_params($this->db, $query, $paramsJson);
        return $this->processResult($result);
    }

    public function executeCypher(string $query): ?string {
        $result = $this->ffi->grafeo_execute_cypher($this->db, $query);
        return $this->processResult($result);
    }

    public function executeCypherWithParams(string $query, string $paramsJson): ?string {
        $result = $this->ffi->grafeo_execute_cypher_with_params($this->db, $query, $paramsJson);
        return $this->processResult($result);
    }

    public function executeGremlin(string $query): ?string {
        $result = $this->ffi->grafeo_execute_gremlin($this->db, $query);
        return $this->processResult($result);
    }

    public function executeGremlinWithParams(string $query, string $paramsJson): ?string {
        $result = $this->ffi->grafeo_execute_gremlin_with_params($this->db, $query, $paramsJson);
        return $this->processResult($result);
    }

    public function executeGraphql(string $query): ?string {
        $result = $this->ffi->grafeo_execute_graphql($this->db, $query);
        return $this->processResult($result);
    }

    public function executeGraphqlWithParams(string $query, string $paramsJson): ?string {
        $result = $this->ffi->grafeo_execute_graphql_with_params($this->db, $query, $paramsJson);
        return $this->processResult($result);
    }

    public function executeSparql(string $query): ?string {
        $result = $this->ffi->grafeo_execute_sparql($this->db, $query);
        return $this->processResult($result);
    }

    public function executeSparqlWithParams(string $query, string $paramsJson): ?string {
        $result = $this->ffi->grafeo_execute_sparql_with_params($this->db, $query, $paramsJson);
        return $this->processResult($result);
    }

    public function executeSql(string $query): ?string {
        $result = $this->ffi->grafeo_execute_sql($this->db, $query);
        return $this->processResult($result);
    }

    public function executeSqlWithParams(string $query, string $paramsJson): ?string {
        $result = $this->ffi->grafeo_execute_sql_with_params($this->db, $query, $paramsJson);
        return $this->processResult($result);
    }

    public function executeLanguage(string $language, string $query, ?string $paramsJson = null): ?string {
        $result = $this->ffi->grafeo_execute_language($this->db, $language, $query, $paramsJson);
        return $this->processResult($result);
    }

    private function processResult(?FFI\CData $result): ?string {
        if (!$result) {
            throw new Exception("Query execution error: " . $this->getLastError());
        }
        $jsonPtr = $this->ffi->grafeo_result_json($result);
        $json = null;
        if ($jsonPtr) {
            $json = is_string($jsonPtr) ? $jsonPtr : FFI::string($jsonPtr);
        }
        $this->ffi->grafeo_free_result($result);
        return $json;
    }

    // --- Node & Edge CRUD ---

    public function createNode(string $labelsJson, string $propertiesJson): int {
        return (int)$this->ffi->grafeo_create_node($this->db, $labelsJson, $propertiesJson);
    }

    public function createEdge(int $sourceId, int $targetId, string $edgeType, string $propertiesJson): int {
        return (int)$this->ffi->grafeo_create_edge($this->db, $sourceId, $targetId, $edgeType, $propertiesJson);
    }

    public function deleteNode(int $id): int {
        return (int)$this->ffi->grafeo_delete_node($this->db, $id);
    }

    public function deleteEdge(int $id): int {
        return (int)$this->ffi->grafeo_delete_edge($this->db, $id);
    }

    public function setNodeProperty(int $id, string $key, string $valueJson): int {
        return (int)$this->ffi->grafeo_set_node_property($this->db, $id, $key, $valueJson);
    }

    public function setEdgeProperty(int $id, string $key, string $valueJson): int {
        return (int)$this->ffi->grafeo_set_edge_property($this->db, $id, $key, $valueJson);
    }

    public function getNodeLabels(int $id): ?string {
        $ptr = $this->ffi->grafeo_get_node_labels($this->db, $id);
        if (!$ptr) return null;
        $labels = FFI::string($ptr);
        $this->ffi->grafeo_free_string($ptr);
        return $labels;
    }

    // --- Schema Context ---

    public function setSchema(string $name): int {
        return (int)$this->ffi->grafeo_set_schema($this->db, $name);
    }

    public function resetSchema(): int {
        return (int)$this->ffi->grafeo_reset_schema($this->db);
    }

    public function currentSchema(): ?string {
        $ptr = $this->ffi->grafeo_current_schema($this->db);
        return $ptr ? FFI::string($ptr) : null;
    }

    // --- Statistics & Admin ---

    public function nodeCount(): int {
        return (int)$this->ffi->grafeo_node_count($this->db);
    }

    public function edgeCount(): int {
        return (int)$this->ffi->grafeo_edge_count($this->db);
    }

    public function info(): ?string {
        $ptr = $this->ffi->grafeo_info($this->db);
        if (!$ptr) return null;
        $info = FFI::string($ptr);
        $this->ffi->grafeo_free_string($ptr);
        return $info;
    }

    public function save(string $path): int {
        return (int)$this->ffi->grafeo_save($this->db, $path);
    }

    public function walCheckpoint(): int {
        return (int)$this->ffi->grafeo_wal_checkpoint($this->db);
    }

    public function compact(): int {
        return (int)$this->ffi->grafeo_compact($this->db);
    }
}
