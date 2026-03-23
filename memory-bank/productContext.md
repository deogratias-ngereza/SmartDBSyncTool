# Product Context

## Why This Project Exists
SaaS POS systems face a critical challenge: managing thousands of independent tenant databases while ensuring they all maintain consistent schemas and shared reference data. Manual database management at this scale is impossible.

## Problems It Solves

### 1. Schema Drift
**Problem**: As the POS application evolves, new features require database changes across thousands of installations.
**Solution**: Centralized schema management with a Controller DB as the single source of truth.

### 2. Data Consistency
**Problem**: Product catalogs, pricing updates, and reference data must be synchronized across all tenant databases.
**Solution**: Selective data synchronization with filtering capabilities (by ID, property, or exclusion).

### 3. Scale Management
**Problem**: Traditional synchronous approaches fail when managing 10,000+ databases.
**Solution**: Asynchronous job processing with batching, chunking, and rate limiting.

### 4. Failure Recovery
**Problem**: Database operations can fail due to network issues, offline servers, or data conflicts.
**Solution**: Transaction-wrapped operations, detailed logging, rollback support, and circuit breakers.

### 5. Multi-Database Support
**Problem**: Tenants may use different database engines (MySQL, PostgreSQL, SQL Server, etc.).
**Solution**: Dynamic connection management with driver-specific query translation.

## How It Should Work

### For Administrators
1. **Define a Controller DB**: Mark the master database containing the schema and data to synchronize
2. **Create Sync Tasks**: Define SQL operations, migrations, or data syncs to execute
3. **Apply Filters**: Select which databases to target (all, specific IDs, exclude certain ones)
4. **Execute & Monitor**: Dispatch jobs to queue and watch real-time progress
5. **Review Results**: View detailed logs, export failures for debugging

### For the System
1. **Provision New Databases**: Automatically perform full sync when new tenant databases are added
2. **Batch Processing**: Chunk database IDs (500 per batch) and dispatch to Laravel Horizon
3. **Dynamic Connections**: Create temporary database connections on-the-fly
4. **Transactional Safety**: Wrap each operation in a transaction that rolls back on failure
5. **Circuit Breaking**: Halt batch execution if failure rate exceeds 25%
6. **Detailed Logging**: Store execution status, errors, duration for every database

## User Experience Goals

### Speed & Responsiveness
- Never block the UI during sync operations
- Real-time progress updates via batch tracking
- Live log streaming for monitoring

### Transparency
- Clear visibility into what's happening with each database
- Detailed error messages when operations fail
- Searchable and filterable log tables

### Safety
- Preview operations before execution
- Rollback support for migrations
- Automatic transaction management
- Connection timeouts to prevent zombie jobs

### Actionability
- Export failed operations to CSV
- Retry specific failures
- Manual intervention capabilities for edge cases

## Core Workflows

### Workflow 1: New Feature Rollout
1. Developer creates migration with UP/DOWN queries
2. Admin tests on staging Controller DB
3. Admin creates sync task targeting all production databases
4. System batches 10,000 databases into chunks of 500
5. Workers execute migration with transaction wrapper
6. Admin monitors progress dashboard
7. System generates report: 9,985 success, 15 failures
8. Admin exports failure list and investigates

### Workflow 2: Price Update
1. Admin updates product prices in Controller DB
2. Admin creates data sync task for `products` table
3. Admin filters to only `mysql` databases in "North America" region
4. System identifies 2,400 matching databases
5. Workers push price data to targets
6. Real-time progress bar shows completion
7. All databases now have updated pricing

### Workflow 3: Emergency Rollback
1. Migration causes unexpected issues
2. Admin initiates rollback task
3. System executes DOWN queries from migration
4. Databases revert to previous schema state
5. Issue resolved while team investigates

## Expected Outcomes
- **Operational Efficiency**: Minutes to deploy changes vs. manual intervention
- **Reliability**: < 5% failure rate under normal conditions
- **Visibility**: Complete audit trail of all operations
- **Safety**: Zero unintended data corruption due to transaction management
- **Scalability**: Linear performance scaling as database count grows
