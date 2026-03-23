# Active Context

## Current Focus
Initial project setup phase - establishing foundational authentication, entity management, and project structure.

## Recent Changes

### Completed
1. **Authentication System**
   - Laravel Fortify integration for user authentication
   - Two-factor authentication support
   - Password reset and email verification
   - Session management

2. **Entity Management**
   - Created `entities` table (represents SaaS Owner/Org)
   - `Entity` model with relationships to users
   - Users belong to entities (multi-tenancy at user level)

3. **UI Foundation**
   - Livewire components for reactive interfaces
   - Flux UI components library
   - Settings pages (profile, password, company, appearance)
   - Authentication views (login, register, password reset)

4. **Event Logging**
   - `LogSuccessfulLogin` listener for login tracking
   - `LoggedOutListener` for logout tracking
   - Event system configured for audit trails

## Next Steps

### Immediate Priority
1. **Database Tables Creation**
   - Create `projects` table (grouping of databases)
   - Create `db_connections` table (store connection credentials)
   - Create `sync_tasks` table (track sync operations)
   - Create `sync_task_logs` table (per-database execution logs)
   - Create `task_filters` table (store filter criteria)

2. **Connection Manager Service**
   - Build service to create dynamic Laravel DB connections
   - Implement credential encryption/decryption
   - Add connection testing functionality
   - Support multiple database drivers

3. **Models & Relationships**
   - Create `Project` model with Entity relationship
   - Create `DatabaseConnection` model with Project relationship
   - Create `SyncTask` model with tracking capabilities
   - Create `SyncTaskLog` model for detailed logging

### Next Phase
4. **Job Dispatcher System**
   - Implement filtering logic (ID list, exclusion, property-based)
   - Build chunking mechanism (500 databases per batch)
   - Integrate Laravel Horizon batching
   - Add circuit breaker pattern

5. **Worker Job Implementation**
   - Create job class for executing sync operations
   - Add transaction wrapper logic
   - Implement cross-driver query translation
   - Build error handling and logging

6. **API Endpoints**
   - Database registration API (for external apps)
   - Task execution API
   - Status checking API
   - Log retrieval API

7. **Frontend Dashboard**
   - Project management interface
   - Database connection manager
   - Sync task creator
   - Live progress monitoring
   - Log viewer with search/filter
   - CSV export functionality

## Active Decisions & Considerations

### Architecture Decisions
- **Queue System**: Using Laravel Horizon with Redis for reliability and monitoring
- **Database Encryption**: Storing encrypted credentials for database connections
- **Multi-Tenancy**: Entity-based isolation for SaaS model
- **Transaction Safety**: All operations wrapped in DB transactions

### Patterns Being Applied
- **Controller DB Pattern**: One database marked as source of truth per project
- **Filter Pattern**: Flexible targeting of databases for sync operations
- **Batch Pattern**: Chunked processing to handle large-scale operations
- **Circuit Breaker**: Automatic halt when failure rate exceeds threshold

### Important Constraints
- Connection timeout: 5-10 seconds to prevent zombie jobs
- Chunk size: 500 databases per batch for memory management
- Circuit breaker threshold: 25% failure rate
- Rate limiting: Required on outbound connections

## Learnings & Insights

### Technical
1. **Dynamic Connections**: Laravel allows runtime database connection configuration via `Config::set()`
2. **Job Batching**: Laravel Bus batching provides built-in progress tracking and callbacks
3. **Livewire Integration**: Flux UI components work seamlessly with Livewire for reactive interfaces

### Project Organization
1. **Entity Hierarchy**: Entity → Project → Database Connection (clear multi-tenant structure)
2. **Task Manifest**: Comprehensive logging critical for debugging at scale
3. **Version Tracking**: `current_version_id` on connections enables incremental sync

## Current Blockers
None identified - proceeding with database schema creation phase.

## Questions to Resolve
1. Encryption method for database credentials (Laravel's default or custom?)
2. Horizon worker configuration (how many workers per queue?)
3. Retry strategy for failed jobs (exponential backoff?)
4. Cross-driver SQL translation approach (abstraction layer or manual mapping?)

## Working Environment
- **Framework**: Laravel 11.x
- **PHP Version**: 8.2+
- **Database**: MySQL/MariaDB for control plane
- **Queue**: Redis with Horizon
- **Frontend**: Livewire + Flux UI
- **Testing**: Pest PHP
