# Technical Context

## Technology Stack

### Backend Framework
**Laravel 11.x**
- Modern PHP framework with excellent queue and database abstraction
- Built-in support for multiple database drivers
- Horizon for queue monitoring and management
- Fortify for authentication scaffolding

### Frontend Stack
**Livewire + Flux UI**
- Livewire: Full-stack reactive framework (no separate JS framework needed)
- Flux UI: Component library for consistent, modern interfaces
- Alpine.js: Lightweight JavaScript for client-side interactions (bundled with Livewire)
- Tailwind CSS: Utility-first CSS framework

### Database
**Primary (Control Plane)**
- MySQL 8.0+ or MariaDB 10.6+
- Stores application data, entities, projects, connections, tasks, logs

**Target Databases (Support Required)**
- MySQL 5.7+
- MariaDB 10.3+
- PostgreSQL 12+
- SQL Server 2017+
- Oracle 12c+
- SQLite 3.8+

### Queue & Background Jobs
**Laravel Horizon + Redis**
- Redis: In-memory data store for queue management
- Horizon: Dashboard and tools for managing Laravel queues
- Job Batching: Track and manage related job groups
- Failed Job Management: Automatic retry and manual intervention

### PHP Version
**PHP 8.2+**
- Required for Laravel 11
- Type declarations for better code safety
- Improved performance over PHP 7.x

## Development Setup

### System Requirements
- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and NPM (for asset compilation)
- Redis 6.0+
- MySQL 8.0+ / MariaDB 10.6+

### Environment Configuration

#### Required Environment Variables
```env
APP_NAME="Multi-DB Sync Tool"
APP_ENV=local
APP_KEY=base64:... (generated via php artisan key:generate)
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gmt_db_sync
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

QUEUE_CONNECTION=redis
```

#### Optional Variables
```env
BROADCAST_DRIVER=null (or 'redis' for real-time updates)
CACHE_DRIVER=redis
SESSION_DRIVER=database
```

### Installation Steps
```bash
# Clone repository
git clone git@github.com:deogratias-ngereza/SmartDBSyncTool.git
cd apps/gmt-db-sync-backend

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (if needed)
php artisan db:seed

# Compile assets
npm run dev

# Start development server
php artisan serve

# In separate terminal: Start queue worker
php artisan horizon
```

## Dependencies

### Core PHP Packages
```json
{
    "laravel/framework": "^11.0",
    "laravel/fortify": "^1.21",
    "laravel/horizon": "^5.24",
    "laravel/sanctum": "^4.0",
    "livewire/livewire": "^3.0",
    "livewire/flux": "^1.0"
}
```

### Database Drivers
```json
{
    "doctrine/dbal": "^3.0", // Schema introspection
    "predis/predis": "^2.0", // Redis client
}
```

### Testing
```json
{
    "pestphp/pest": "^2.0",
    "pestphp/pest-plugin-laravel": "^2.0",
    "mockery/mockery": "^1.6"
}
```

### Frontend Dependencies
```json
{
    "tailwindcss": "^3.4",
    "autoprefixer": "^10.4",
    "postcss": "^8.4",
    "vite": "^5.0",
    "laravel-vite-plugin": "^1.0"
}
```

## Technical Constraints

### Performance Constraints
1. **Connection Timeout**: 5-10 seconds max for database connections
2. **Job Timeout**: 60 seconds max per sync job
3. **Memory Limit**: 512MB per worker process
4. **Batch Size**: 500 databases per job batch
5. **Concurrent Workers**: 10-20 workers per queue

### Security Constraints
1. **Credential Encryption**: All database passwords must be encrypted at rest
2. **API Authentication**: All API endpoints require Sanctum token
3. **Entity Isolation**: Users can only access their entity's data
4. **SQL Injection Prevention**: All user SQL validated and parameterized where possible

### Compatibility Constraints
1. **PHP Version**: Must support PHP 8.2+ only
2. **Database Versions**: Support listed versions (see above)
3. **Browser Support**: Modern browsers only (Chrome, Firefox, Safari, Edge)
4. **SSL/TLS**: Support encrypted database connections

## Tool Usage Patterns

### Artisan Commands (Custom)
```bash
# Test database connection
php artisan db:test-connection {connection_id}

# Provision new database (full sync from controller)
php artisan db:provision {database_id}

# Execute sync task
php artisan sync:execute {task_id} {--filter=}

# Rollback sync task
php artisan sync:rollback {task_id}

# Clean up old logs
php artisan sync:cleanup --days=30

# Generate migration from controller schema
php artisan sync:generate-migration {controller_id} {table_name}
```

### Horizon Commands
```bash
# Start Horizon
php artisan horizon

# Pause queue processing
php artisan horizon:pause

# Resume queue processing
php artisan horizon:continue

# Terminate Horizon gracefully
php artisan horizon:terminate

# Publish Horizon assets
php artisan horizon:publish
```

### Testing Commands
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=SyncTaskTest

# Run with coverage
php artisan test --coverage

# Run Pest directly
vendor/bin/pest
```

### Database Commands
```bash
# Run migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Refresh database (drop all + migrate)
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Generate new migration
php artisan make:migration create_projects_table
```

## Development Patterns

### Livewire Component Structure
```php
namespace App\Livewire\Pages\Config;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Users extends Component
{
    // Properties (reactive)
    public $users = [];
    public $selectedUserId = null;
    
    // Lifecycle hooks
    public function mount() { }
    
    // Actions
    public function selectUser($userId) { }
    
    // Render
    public function render()
    {
        return view('pages.config.⚡users');
    }
}
```

### Job Structure
```php
namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteSyncTaskJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 60;
    public $tries = 3;
    
    public function __construct(
        public int $databaseId,
        public int $taskId
    ) {}
    
    public function handle(): void
    {
        // Job logic
    }
}
```

### Service Structure
```php
namespace App\Services;

class ConnectionManagerService
{
    public function createConnection(DatabaseConnection $db): string
    {
        $connectionName = "temp_sync_{$db->id}";
        
        Config::set("database.connections.{$connectionName}", [
            'driver' => $db->driver,
            'host' => $db->host,
            'port' => $db->port,
            'database' => $db->database,
            'username' => $db->username,
            'password' => decrypt($db->password),
            'timeout' => 10,
        ]);
        
        return $connectionName;
    }
}
```

### Repository Structure
```php
namespace App\Repositories;

class DatabaseConnectionRepository
{
    public function findByProject(int $projectId): Collection
    {
        return DatabaseConnection::where('project_id', $projectId)->get();
    }
    
    public function getControllerDb(int $projectId): ?DatabaseConnection
    {
        return DatabaseConnection::where('project_id', $projectId)
            ->where('is_controller', true)
            ->first();
    }
}
```

## Configuration Files

### Queue Configuration
**config/queue.php**
- Redis connection for queues
- Horizon supervisor configuration
- Queue names and priorities
- Failed job settings

### Database Configuration
**config/database.php**
- Default connection settings
- Driver configurations
- Connection pooling settings
- Redis cache configuration

### Horizon Configuration
**config/horizon.php**
- Worker configuration
- Queue balancing
- Job timeout settings
- Metrics retention

## File Naming Conventions

### Livewire Components
- File: `app/Livewire/Pages/Config/Users.php`
- View: `resources/views/pages/config/⚡users.blade.php`
- Route: Automatic via Livewire routing or explicit in `routes/web.php`

### Blade Components
- File: `resources/views/components/app-logo.blade.php`
- Usage: `<x-app-logo />`

### Database Migrations
- Format: `YYYY_MM_DD_HHMMSS_description.php`
- Example: `2026_03_07_162354_create_entities_table.php`

### Tests
- Feature: `tests/Feature/SyncTaskTest.php`
- Unit: `tests/Unit/ConnectionManagerTest.php`

## Environment-Specific Settings

### Local Development
- Debug mode enabled
- Query logging enabled
- Horizon auto-restarts on code changes
- Asset hot-reloading via Vite

### Production
- Debug mode disabled
- HTTPS required
- Queue workers managed by Supervisor
- Compiled assets served via CDN
- Connection pooling enabled
- Redis persistence enabled
