# Project Brief: Multi-DB Sync Orchestrator (SaaS POS Edition)

## Project Overview
A Control Plane system designed to manage and synchronize 10,000+ heterogeneous databases (MySQL, MariaDB, PostgreSQL, SQL Server, Oracle, SQLite) for a SaaS POS application. Each tenant (Point of Sale) operates with a dedicated database.

## Core Objectives
1. **Equal Database Structure**: Maintain consistent schema across all databases
2. **Common Data Maintenance**: Synchronize shared data between servers
3. **Controller DB Pattern**: Designate one database as the source of truth

## Primary Goals
- Synchronize schemas from a Controller DB to Target databases
- Push selective data updates (e.g., product prices) to filtered database sets
- Track synchronization status for each database
- Handle rollbacks when updates fail
- Support heterogeneous database drivers

## Scale Requirements
- Support 10,000+ concurrent database connections
- Non-blocking, asynchronous job processing
- Chunked operations (500 databases per batch)
- Connection pooling and rate limiting

## Key Architectural Decisions
- Laravel framework with Horizon for queue management
- Redis-based job batching
- Dynamic database connection management
- Transaction-wrapped operations with rollback support
- Circuit breaker pattern for failure prevention

## Success Criteria
- Successfully synchronize schema across multiple database types
- Track and report on sync status per database
- Provide rollback capability for failed operations
- Export failure logs for debugging
- Real-time progress monitoring via frontend

## Target Users
- SaaS Platform Administrators
- Database Operations Teams
- System Integrators managing multi-tenant POS systems
