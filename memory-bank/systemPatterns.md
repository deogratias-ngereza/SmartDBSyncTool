# System Patterns

## System Architecture

### High-Level Overview
```
┌─────────────────────────────────────────────────────────────┐
│                     Control Plane (Laravel)                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Web UI     │  │  REST API    │  │   Horizon    │      │
│  │  (Livewire)  │  │  (External)  │  │   (Queue)    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│         │                  │                  │              │
│         └──────────────────┴──────────────────┘              │
│                           │                                  │
│         ┌─────────────────┴─────────────────┐               │
│         │      Sync Task Orchestrator       │               │
│         │   (Filtering, Batching, Dispatch) │               │
│         └─────────────────┬─────────────────┘               │
│                           │                                  │
│         ┌─────────────────┴─────────────────┐               │
│         │     Connection Manager Service    │               │
│         │    (Dynamic DB Connections)       │               │
│         └─────────────────┬─────────────────┘               │
└───────────────────────────┼─────────────────────────────────┘
                            │
          ┌─────────────────┴─────────────────┐
          │                                   │
    ┌─────▼──────┐                    ┌──────▼─────┐
    │ Controller │                    │   Target   │
    │     DB     │                    │  DB 1-10k  │
    │ (Source)   │                    │ (Targets)  │
    └────────────┘                    └────────────┘
```

### Component Relationships

#### Entity Hierarchy
```
Entity (SaaS Owner)
    └── Users (belong to entity)
    └── Projects (grouping of databases)
            └── Database Connections
                    ├── Controller DB (is_controller = true)
                    └── Target DBs (is_controller = false)
```

#### Task Flow
```
Sync Task
    ├── Filter Definition (which DBs to target)
    ├── Task Type (Raw Query, Migration, Controller Sync)
    ├── UP Query (operation to perform)
    ├── DOWN Query (rollback operation)
    └── Sync Task Logs (one per database)
            ├── Execution Status
            ├── Error Messages
            ├── Duration
            └── Timestamp
```

## Key Technical Decisions

### 1. Asynchronous Everything
**Decision**: Never execute sync operations synchronously.
**Rationale**: With 10,000+ databases, synchronous operations would timeout and block the application.
**Implementation**: Laravel Horizon + Redis queue with job batching.

### 2. Dynamic Connection Management
**Decision**: Create database connections at runtime, not in config files.
**Rationale**: Cannot predefine 10,000+ connections in Laravel's database config.
**Implementation**: 
```php
Config::set("database.connections.temp_sync_{$id}", [
    'driver' => $dbConnection->driver,
    'host' => $dbConnection->host,
    'database' => $dbConnection->database,
    'username' => $dbConnection->username,
    'password' => decrypt($dbConnection->password),
]);
DB::connection("temp_sync_{$id}");
```

### 3. Controller DB Pattern
**Decision**: Each project has exactly one Controller DB marked as source of truth.
**Rationale**: Need a golden schema to sync from; prevents ambiguity about correct structure.
**Implementation**: `is_controller` boolean flag on `db_connections` table.

### 4. Transaction-Wrapped Execution
**Decision**: Every database operation runs inside a transaction.
**Rationale**: Allows automatic rollback on failure, prevents partial updates.
**Implementation**:
```php
DB::connection($target)->transaction(function() use ($sql) {
    DB::connection($target)->statement($sql);
});
```

### 5. Circuit Breaker Pattern
**Decision**: Halt batch execution if failure rate exceeds 25%.
**Rationale**: Prevent mass data corruption if there's a systemic issue with the sync task.
**Implementation**: Monitor `success_count` vs `failure_count` in batch callbacks.

## Design Patterns In Use

### 1. Repository Pattern
**Where**: Database Connection management
**Why**: Abstract database credential storage and retrieval
**Example**: `DatabaseConnectionRepository` handles encryption/decryption

### 2. Service Layer Pattern
**Where**: Connection Manager, Sync Orchestrator
**Why**: Separate business logic from controllers
**Example**: `ConnectionManagerService::createConnection($dbConnection)`

### 3. Job Pattern
**Where**: All sync operations
**Why**: Leverage Laravel's queue system for background processing
**Example**: `ExecuteSyncTaskJob` dispatched via `Bus::batch()`

### 4. Observer Pattern
**Where**: Real-time progress updates
**Why**: Frontend needs to react to backend state changes
**Example**: Livewire components listening to batch progress

### 5. Strategy Pattern
**Where**: Filter implementations
**Why**: Different filter types (ID list, exclusion, property) need different logic
**Example**: `IDListFilter`, `ExclusionFilter`, `PropertyFilter` implementing `FilterStrategy`

### 6. Factory Pattern
**Where**: Creating database connections, sync tasks
**Why**: Complex object creation with multiple configuration options
**Example**: `DatabaseConnectionFactory::createFromCredentials()`

## Critical Implementation Paths

### Path 1: New Database Provisioning
1. External app registers new tenant database via API
2. System stores credentials in `db_connections` table (encrypted)
3. System identifies Controller DB for the project
4. System creates `FULL_SYNC` task automatically
5. Job dispatched to queue for schema + data sync
6. New database ready for use

### Path 2: Schema Migration
1. Admin creates migration task with UP/DOWN queries
2. Admin selects filter (e.g., "all PostgreSQL databases")
3. System queries `db_connections` table with filter
4. System chunks IDs into batches of 500
5. System dispatches batch to Horizon
6. Workers execute UP query on each database
7. Success/failure logged to `sync_task_logs`
8. Admin views real-time progress and final report

### Path 3: Data Sync from Controller
1. Admin updates data in Controller DB
2. Admin creates Controller Sync task for specific table
3. System introspects Controller table structure
4. System generates INSERT/UPDATE queries
5. System applies same filter → batch → execute pattern
6. Target databases receive updated data

### Path 4: Rollback Operation
1. Migration causes issues (detected by admin or monitoring)
2. Admin initiates rollback for the sync task
3. System retrieves DOWN query from task
4. System filters to only databases where UP succeeded
5. System executes DOWN query with same batch pattern
6. Databases reverted to pre-migration state

## Component Interactions

### Connection Manager Service
**Responsibilities**:
- Decrypt database credentials
- Create temporary Laravel DB connection
- Test connection validity
- Handle connection timeouts (5-10 seconds)
- Clean up temporary connections

**Interface**:
```php
ConnectionManagerService::createConnection(DatabaseConnection $db): string
ConnectionManagerService::testConnection(DatabaseConnection $db): bool
ConnectionManagerService::removeConnection(string $connectionName): void
```

### Sync Task Orchestrator
**Responsibilities**:
- Apply filters to get target database IDs
- Chunk IDs (500 per batch)
- Create parent `sync_tasks` record
- Dispatch batch jobs to Horizon
- Monitor batch progress
- Update aggregate statistics

**Interface**:
```php
SyncOrchestrator::dispatch(SyncTask $task, Filter $filter): Batch
SyncOrchestrator::getTargetDatabases(Filter $filter): Collection
SyncOrchestrator::chunkAndDispatch(Collection $databases, SyncTask $task): void
```

### Execute Sync Job
**Responsibilities**:
- Receive database ID and sync task
- Create connection to target database
- Begin transaction
- Execute SQL operation
- Commit or rollback based on result
- Log outcome to `sync_task_logs`
- Handle timeouts and errors gracefully

**Structure**:
```php
class ExecuteSyncTaskJob implements ShouldQueue
{
    public function handle()
    {
        $connection = ConnectionManager::createConnection($this->database);
        
        try {
            DB::connection($connection)->transaction(function() {
                DB::connection($connection)->statement($this->task->up_query);
            });
            
            $this->logSuccess();
        } catch (Exception $e) {
            $this->logFailure($e->getMessage());
        } finally {
            ConnectionManager::removeConnection($connection);
        }
    }
}
```

## Data Flow

### Write Path (Admin → Databases)
```
Admin Action → Controller → Service Layer → Queue Dispatcher → 
    Horizon Queue → Worker Job → Target Database → Log Result
```

### Read Path (Database Status → Admin)
```
Database → Worker Job → sync_task_logs Table → 
    API/Livewire Component → Frontend → Admin Dashboard
```

### Real-Time Updates
```
Batch Progress → Laravel Broadcast → WebSocket → 
    Livewire Component → Progress Bar Update
```

## Scalability Considerations

### Horizontal Scaling
- **Worker Scaling**: Add more Horizon workers to process batches faster
- **Database Scaling**: Separate read replicas for log queries
- **Queue Scaling**: Redis Cluster for distributed queue management

### Vertical Optimization
- **Chunking**: Prevents memory exhaustion (500 databases per chunk)
- **Connection Pooling**: Reuse connections where possible
- **Lazy Loading**: Load relationships only when needed

### Performance Targets
- **Dispatch Time**: < 30 seconds to dispatch 10,000 jobs
- **Execution Time**: < 5 seconds per database operation
- **Query Time**: < 2 seconds to load log table with pagination
- **UI Response**: < 200ms for real-time progress updates

## Security Patterns

### Credential Storage
- Database passwords encrypted using Laravel's `encrypt()` helper
- Encryption key stored in `.env` (production: KMS or Vault)
- Credentials never logged or exposed via API

### Multi-Tenancy
- Entity-based isolation (users can't access other entities' data)
- Middleware enforces entity scope on all queries
- API authentication via Sanctum tokens with entity binding

### Query Safety
- All user-provided SQL sanitized and validated
- Transaction-wrapped execution prevents partial updates
- Circuit breaker prevents mass corruption
