# Grafeo PHP FFI Wrapper

A lightweight PHP wrapper using FFI (Foreign Function Interface) to integrate the [Grafeo C API](https://grafeo.dev/api/c/?h=ffi) graph database into PHP applications.

## Building the Shared Library via Docker

Attached files to official Grafeo releases are not build with all features, which is required by this library. Therefore you have to build your own library file(s).

Since Grafeo has a Rust core, you can compile the required shared library (`.so`, `.dylib`, or `.dll`) using Docker without installing Rust natively.

First you need to clone [Grafeo repository](https://github.com/GrafeoDB/grafeo) and then switch to correct tag (based on release version).

Second, from within Grafeo local copy directory run one of the following commands based on your environment. 

### For Windows (PowerShell):
```powershell
docker run --rm -v "$(Get-Location):/usr/src/myapp" -w /usr/src/myapp rust:latest bash -c "apt-get update && apt-get install -y gcc-mingw-w64-x86-64 && rustup target add x86_64-pc-windows-gnu && cargo build --release --target x86_64-pc-windows-gnu -p grafeo-c --features full"
```

*Output DLL location:* `target/x86_64-pc-windows-gnu/release/grafeo_c.dll`

### For Linux / macOS:

```bash
docker run --rm -v "$PWD":/usr/src/myapp -w /usr/src/myapp rust:latest cargo build --release -p grafeo-c --features full
```

*Output location:* `target/release/`

## Important Usage Notes

### 1. In-Memory Databases (`openMemory()`)

* **Lifespan:** An in-memory database exists entirely within the scope of a single PHP process execution.
* Once the script finishes or the process exits, **all data is completely lost**.

### 2. File-Based Persistence & Concurrency Warnings

* Grafeo is an embedded database. When opening a persistent file (`grafeo_open('/path/to/db')`), the underlying storage engine enforces an exclusive file lock to protect data integrity.
* **Concurrency Issue:** Parallel PHP requests (such as multi-process web servers like PHP-FPM) trying to read or write to the *same* database file simultaneously will trigger a file-lock conflict / exception (`database file is locked by another process`).

## Quick Start

```php
<?php
require_once 'Grafeo.php';

$grafeo = new Grafeo(__DIR__ . '/grafeo_c.dll');
echo "Version: " . $grafeo->version() . "\n";

// Open in-memory DB
$grafeo->openMemory();

// Create nodes & edges
$id1 = $grafeo->createNode('["Person"]', '{"name":"Alice"}');
$id2 = $grafeo->createNode('["Person"]', '{"name":"Bob"}');
$grafeo->createEdge($id1,$id2, 'KNOWS', '{}');

// Query
$result = $grafeo->execute('MATCH (p:Person) RETURN p.name');
echo $result;

$grafeo->close();
```

You can also install this library via composer with following command:

```
composer require stefanak-michal/grafeo-php
```
