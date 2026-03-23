<?php

namespace App\Services;

use App\Models\DatabaseConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Exception;

class ConnectionManager
{
    /**
     * Create a dynamic database connection for a DatabaseConnection model.
     * 
     * @param DatabaseConnection $dbConnection
     * @return string The connection name
     * @throws Exception
     */
    public function createConnection(DatabaseConnection $dbConnection): string
    {
        $connectionName = $this->getConnectionName($dbConnection->id);
        
        // Get connection configuration
        $config = $dbConnection->getConnectionConfig();
        
        // Register the connection with Laravel
        Config::set("database.connections.{$connectionName}", $config);
        
        return $connectionName;
    }

    /**
     * Test a database connection to verify it's valid.
     * 
     * @param DatabaseConnection $dbConnection
     * @return array ['success' => bool, 'message' => string, 'duration_ms' => int]
     */
    public function testConnection(DatabaseConnection $dbConnection): array
    {
        $startTime = microtime(true);
        
        try {
            $connectionName = $this->createConnection($dbConnection);
            
            // Attempt to connect and run a simple query
            $pdo = DB::connection($connectionName)->getPdo();
            
            // Test with a simple query appropriate for the driver
            $testQuery = $this->getTestQuery($dbConnection->driver);
            $result = DB::connection($connectionName)->select($testQuery);
            
            $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            
            // Clean up the connection
            $this->removeConnection($connectionName);
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'duration_ms' => round($duration, 2),
            ];
            
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
            ];
        }
    }

    /**
     * Remove a dynamic database connection and clean up resources.
     * 
     * @param string $connectionName
     * @return void
     */
    public function removeConnection(string $connectionName): void
    {
        // Purge the connection from Laravel's connection manager
        DB::purge($connectionName);
        
        // Remove from config (optional, helps with memory)
        Config::set("database.connections.{$connectionName}", null);
    }

    /**
     * Get a standardized connection name for a database connection ID.
     * 
     * @param string $dbConnectionId
     * @return string
     */
    public function getConnectionName(string $dbConnectionId): string
    {
        return "temp_sync_{$dbConnectionId}";
    }

    /**
     * Get appropriate test query for the database driver.
     * 
     * @param string $driver
     * @return string
     */
    protected function getTestQuery(string $driver): string
    {
        return match($driver) {
            'mysql', 'mariadb' => 'SELECT 1',
            'pgsql' => 'SELECT 1',
            'sqlsrv' => 'SELECT 1',
            'sqlite' => 'SELECT 1',
            default => 'SELECT 1',
        };
    }

    /**
     * Execute a raw query on a database connection with transaction support.
     * 
     * @param DatabaseConnection $dbConnection
     * @param string $query
     * @return array ['success' => bool, 'message' => string, 'duration_ms' => int, 'rows_affected' => int]
     */
    public function executeQuery(DatabaseConnection $dbConnection, string $query): array
    {
        $startTime = microtime(true);
        $connectionName = null;
        
        try {
            $connectionName = $this->createConnection($dbConnection);
            $rowsAffected = 0;
            
            // Execute within a transaction
            DB::connection($connectionName)->transaction(function () use ($connectionName, $query, &$rowsAffected) {
                $rowsAffected = DB::connection($connectionName)->statement($query);
            });
            
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Clean up
            $this->removeConnection($connectionName);
            
            return [
                'success' => true,
                'message' => 'Query executed successfully',
                'duration_ms' => round($duration, 2),
                'rows_affected' => $rowsAffected,
            ];
            
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            // Clean up connection if it was created
            if ($connectionName) {
                $this->removeConnection($connectionName);
            }
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'duration_ms' => round($duration, 2),
                'rows_affected' => 0,
            ];
        }
    }

    /**
     * Check if a connection exists and is active.
     * 
     * @param string $connectionName
     * @return bool
     */
    public function connectionExists(string $connectionName): bool
    {
        try {
            DB::connection($connectionName)->getPdo();
            return true;
        }  catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get driver-specific connection options.
     * 
     * @param string $driver
     * @return array
     */
    protected function getDriverOptions(string $driver): array
    {
        $baseOptions = [
            \PDO::ATTR_TIMEOUT => 10,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ];

        return match($driver) {
            'mysql', 'mariadb' => array_merge($baseOptions, [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
            ]),
            'pgsql' => $baseOptions,
            'sqlsrv' => $baseOptions,
            default => $baseOptions,
        };
    }

    /**
     * Validate connection configuration before creating.
     * 
     * @param DatabaseConnection $dbConnection
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateConnectionConfig(DatabaseConnection $dbConnection): array
    {
        $errors = [];

        if (empty($dbConnection->host)) {
            $errors[] = 'Host is required';
        }

        if (empty($dbConnection->port) || $dbConnection->port < 1 || $dbConnection->port > 65535) {
            $errors[] = 'Valid port number is required (1-65535)';
        }

        if (empty($dbConnection->database)) {
            $errors[] = 'Database name is required';
        }

        if (empty($dbConnection->username)) {
            $errors[] = 'Username is required';
        }

        if (empty($dbConnection->encrypted_password)) {
            $errors[] = 'Password is required';
        }

        $supportedDrivers = ['mysql', 'mariadb', 'pgsql'];
        if (!in_array($dbConnection->driver, $supportedDrivers)) {
            $errors[] = 'Driver must be one of: ' . implode(', ', $supportedDrivers);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
