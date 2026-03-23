# Multi-DB Sync Tool - API Documentation

**Base URL**: `http://your-domain.com/api`  
**Authentication**: Bearer Token (Sanctum)

## Table of Contents
1. [Authentication](#authentication)
2. [Projects](#projects)
3. [Database Connections](#database-connections)
4. [Sync Tasks](#sync-tasks)
5. [Sync Task Logs](#sync-task-logs)
6. [Error Responses](#error-responses)

---

## Authentication

### Login (Get API Token)
```http
POST /auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "your-password",
  "device_name": "My Application"
}
```

**Response**:
```json
{
  "message": "Login successful",
  "data": {
    "token": "1|abc123...",
    "token_type": "Bearer",
    "user": {
      "id": "user_id",
      "name": "User Name",
      "email": "user@example.com",
      "entity_id": "entity_id"
    }
  }
}
```

### Logout (Revoke Current Token)
```http
POST /auth/logout
Authorization: Bearer {token}
```

### Logout All (Revoke All Tokens)
```http
POST /auth/logout-all
Authorization: Bearer {token}
```

### Get All Tokens
```http
GET /auth/tokens
Authorization: Bearer {token}
```

### Revoke Specific Token
```http
DELETE /auth/tokens/{tokenId}
Authorization: Bearer {token}
```

---

## Projects

### List Projects
```http
GET /projects?per_page=15
Authorization: Bearer {token}
```

### Create Project
```http
POST /projects
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Production Databases",
  "description": "All production POS databases",
  "is_active": true
}
```

### Get Project
```http
GET /projects/{id}
Authorization: Bearer {token}
```

### Update Project
```http
PUT /projects/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Updated Name",
  "is_active": false
}
```

### Delete Project
```http
DELETE /projects/{id}
Authorization: Bearer {token}
```

### Get Project Statistics
```http
GET /projects/{id}/statistics
Authorization: Bearer {token}
```

**Response**:
```json
{
  "data": {
    "total_databases": 150,
    "active_databases": 148,
    "controller_database": "Master DB",
    "total_sync_tasks": 42,
    "pending_tasks": 0,
    "running_tasks": 2,
    "completed_tasks": 38,
    "failed_tasks": 2
  }
}
```

---

## Database Connections

### List Database Connections
```http
GET /database-connections?project_id={id}&driver=mysql&is_active=1&per_page=15
Authorization: Bearer {token}
```

### Create Database Connection
```http
POST /database-connections
Authorization: Bearer {token}
Content-Type: application/json

{
  "project_id": "project_id",
  "name": "POS Store #001",
  "driver": "mysql",
  "host": "192.168.1.100",
  "port": 3306,
  "database": "pos_store_001",
  "username": "pos_user",
  "password": "secure_password",
  "is_controller": false,
  "is_active": true,
  "ssl_enabled": false,
  "description": "Store 001 - New York"
}
```

**Supported Drivers**: `mysql`, `mariadb`, `pgsql`, `sqlsrv`, `oracle`, `sqlite`

### Get Database Connection
```http
GET /database-connections/{id}
Authorization: Bearer {token}
```

### Update Database Connection
```http
PUT /database-connections/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Updated Name",
  "is_active": false
}
```

### Delete Database Connection
```http
DELETE /database-connections/{id}
Authorization: Bearer {token}
```

### Test Database Connection
```http
POST /database-connections/{id}/test
Authorization: Bearer {token}
```

**Response**:
```json
{
  "message": "Connection successful",
  "data": {
    "status": "success",
    "duration_ms": 45
  }
}
```

---

## Sync Tasks

### List Sync Tasks
```http
GET /sync-tasks?project_id={id}&status=pending&task_type=migration&per_page=15
Authorization: Bearer {token}
```

### Create Sync Task
```http
POST /sync-tasks
Authorization: Bearer {token}
Content-Type: application/json

{
  "project_id": "project_id",
  "name": "Add inventory_count column",
  "description": "Migration to add inventory tracking",
  "task_type": "migration",
  "up_query": "ALTER TABLE products ADD COLUMN inventory_count INT DEFAULT 0",
  "down_query": "ALTER TABLE products DROP COLUMN inventory_count",
  "filters": [
    {
      "filter_type": "property",
      "filter_value": "driver:mysql"
    }
  ]
}
```

**Task Types**: `raw_query`, `migration`, `controller_sync`, `full_sync`  
**Filter Types**: `id_list`, `exclusion`, `property`, `all_targets`

### Get Sync Task
```http
GET /sync-tasks/{id}
Authorization: Bearer {token}
```

### Execute Sync Task
```http
POST /sync-tasks/{id}/execute
Authorization: Bearer {token}
```

**Response**:
```json
{
  "message": "Sync task dispatched successfully",
  "data": {
    "batch_id": "batch_uuid",
    "total_jobs": 150,
    "task": { /* task details */ }
  }
}
```

### Get Task Progress
```http
GET /sync-tasks/{id}/progress
Authorization: Bearer {token}
```

**Response**:
```json
{
  "data": {
    "batch_id": "batch_uuid",
    "name": "Sync Task: Add inventory_count column",
    "total_jobs": 150,
    "pending_jobs": 45,
    "processed_jobs": 105,
    "failed_jobs": 2,
    "progress": 70.0,
    "finished": false,
    "cancelled": false,
    "task_status": "running",
    "success_count": 103,
    "failure_count": 2,
    "total_targets": 150
  }
}
```

### Cancel Sync Task
```http
POST /sync-tasks/{id}/cancel
Authorization: Bearer {token}
```

### Rollback Sync Task
```http
POST /sync-tasks/{id}/rollback
Authorization: Bearer {token}
```

### Delete Sync Task
```http
DELETE /sync-tasks/{id}
Authorization: Bearer {token}
```

---

## Sync Task Logs

### List Logs
```http
GET /sync-task-logs?sync_task_id={id}&status=failed&search=error&per_page=50
Authorization: Bearer {token}
```

**Query Parameters**:
- `sync_task_id`: Filter by sync task
- `status`: Filter by status (`success` or `failed`)
- `db_connection_id`: Filter by database connection
- `search`: Search in error messages
- `order_by`: Order by field (default: `executed_at`)
- `order_direction`: `asc` or `desc` (default: `desc`)

### Get Failed Logs Only
```http
GET /sync-task-logs/failed?sync_task_id={id}&per_page=50
Authorization: Bearer {token}
```

### Export Logs to CSV
```http
GET /sync-task-logs/export?sync_task_id={id}&status=failed
Authorization: Bearer {token}
```

**Response**: CSV file download

### Get Log Statistics
```http
GET /sync-task-logs/statistics?sync_task_id={id}&from_date=2026-03-01&to_date=2026-03-31
Authorization: Bearer {token}
```

**Response**:
```json
{
  "data": {
    "total_executions": 500,
    "successful_executions": 485,
    "failed_executions": 15,
    "average_duration_ms": 234,
    "total_duration_seconds": 117.0,
    "success_rate": 97.0
  }
}
```

---

## Error Responses

### 400 Bad Request
```json
{
  "message": "Validation error message",
  "errors": {
    "field_name": ["Error description"]
  }
}
```

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "Resource not found."
}
```

### 422 Unprocessable Entity
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 429 Too Many Requests
```json
{
  "message": "Too many requests. Please try again later."
}
```

### 500 Internal Server Error
```json
{
  "message": "Server error occurred",
  "error": "Detailed error message"
}
```

---

## Rate Limiting

- **Standard endpoints**: 60 requests per minute
- **Resource-intensive endpoints** (test connection, execute task): 30 requests per minute

---

## Examples

### Complete Workflow Example

#### 1. Login and Get Token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password","device_name":"MyApp"}'
```

#### 2. Create a Project
```bash
curl -X POST http://localhost:8000/api/projects \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Production Stores","description":"All production databases"}'
```

#### 3. Add Database Connections
```bash
curl -X POST http://localhost:8000/api/database-connections \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id":"PROJECT_ID",
    "name":"Store 001",
    "driver":"mysql",
    "host":"192.168.1.100",
    "port":3306,
    "database":"pos_001",
    "username":"user",
    "password":"pass"
  }'
```

#### 4. Create and Execute Sync Task
```bash
curl -X POST http://localhost:8000/api/sync-tasks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "project_id":"PROJECT_ID",
    "name":"Add Column",
    "task_type":"migration",
    "up_query":"ALTER TABLE products ADD COLUMN test VARCHAR(255)",
    "filters":[{"filter_type":"all_targets","filter_value":""}]
  }'

# Execute the task
curl -X POST http://localhost:8000/api/sync-tasks/TASK_ID/execute \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 5. Monitor Progress
```bash
curl -X GET http://localhost:8000/api/sync-tasks/TASK_ID/progress \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 6. View Logs
```bash
curl -X GET "http://localhost:8000/api/sync-task-logs?sync_task_id=TASK_ID" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Postman Collection

Import these endpoints into Postman for easy testing. Set the following variables:
- `base_url`: `http://localhost:8000/api`
- `token`: Your Bearer token from login

---

## Support

For issues or questions, please contact the development team or open an issue in the project repository.
