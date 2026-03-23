# Multi-DB Sync Orchestrator (SaaS POS Edition)
1. System Vision
A Control Plane to manage 10,000+ heterogeneous databases (MySQL, MariaDB, Postgres, SQL Server, Oracle, SQLite). Each tenant (POS) has a dedicated database. The system must synchronize schemas and data from a Controller DB to "Target" databases based on flexible filters.
2. Core Entities & Multi-Tenancy

    Entity: The top-level account (SaaS Owner/Org).
    Project: A grouping of databases (e.g., "North America Retail").
    Database Connection:
        Credentials (Host, Port, DB Name, Username, Encrypted Password).
        Driver type (e.g., pgsql, mysql).
        is_controller flag: Designates the source of truth.
        current_version_id: Tracks the last successful migration/task applied.

3. The "Controller DB" Logic

    Provisioning: If a new database is added, it must perform a FULL_SYNC (Schema + Essential Data) from the Project's Controller DB.
    Schema Baseline: The Controller DB provides the "Golden Schema." The tool must be able to introspect the Controller to generate UP and DOWN queries for targets.
    Data Push: Ability to select specific tables from the Controller to "broadcast" data (e.g., new product prices) to all filtered targets.

4. Scaling to 10k Databases (Performance Rules)

    Never Synchronous: All executions must be dispatched to Laravel Horizon (Redis) using Job Batching.
    Chunking: Fetch database IDs in chunks of 500 to avoid memory exhaustion during dispatch.
    Connection Manager: A dynamic service that creates temporary Laravel database connections on-the-fly using Config::set("database.connections.temp_sync", $credentials).
    Rate Limiting: Workers must throttle outbound connections to prevent saturating the Control Plane's network interface.

5. Task & Filter Engine
Filter Types:

    ID List: Explicitly run on [1, 5, 22, 104].
    Exclusion List: Run on all EXCEPT [10, 15].
    Property Filter: Run on all Postgres databases or all databases in Project X.

Task Types:

    Raw Query: Execute a specific SQL string.
    Migration Task: Apply UP (update) and provide a DOWN (rollback) path.
    Controller Sync: Force a target to match the Controller's current state.

6. Logging & Telemetry (The "Manifest")
Each Sync Task must generate a parent record with many Task Logs:

    sync_tasks: total_targets, success_count, failure_count, status (Pending/Processing/Partial-Failure/Completed).
    sync_task_logs: One per database. Stores:
        Execution Status (Success/Fail).
        Exact SQL Error message if failed.
        Duration (ms) and Timestamp.
        Batch ID (for Laravel Bus Batching).

7. Frontend Requirements (Inertia.js)

    Live Progress: Use a progress bar connected to the Batch ID.
    Real-time Logs: A searchable table of 10,000 results with filters for "Failed Only."
    Export: Capability to export the "Failed" list to CSV for manual debugging.

8. Safety & Robustness

    Transaction Wrapper: Every job must run inside a DB::beginTransaction() on the target. If the query fails, it rolls back locally.
    Connection Timeout: Set a strict 5-10 second connection timeout to prevent "Zombie" jobs if a customer's POS server is offline.
    Circuit Breaker: Stop the batch if the failure rate exceeds 25% to prevent global data corruption.

AI Implementation Order:

    Migrations: Create entities, projects, db_connections, sync_tasks, and sync_task_logs.
    Connection Service: Build a class that takes a db_connection model and returns a functional Laravel DB instance.
    Job Dispatcher: Build the logic to filter 10k IDs and push them into a Bus::batch.
    Worker Logic: The job that handles the try-catch execution, cross-driver translation, and log updating.
    UI: Build the Inertia dashboard for triggering tasks and viewing live logs.

Would you like to start by generating the Database Migrations or the Connection Manager service first?


# expect
user-role
access-control
project registration base on user entity
database registration on given entity
database registration api (since some apps will be direct connected to this tool)
query/schema task or migration task update its up and down for update and roll back
track updates for each database if query succecced or not
query to run on specific databases ids only skip some
task status how many successed and failed , current operation
task put it in background queue for execution
view and export updates result
robust and insure effect multi database sync example structure and some data
mark one database as controller db:
sync from controller db


# aim 
- equal database structure across databases and
- maintain common data between server 

