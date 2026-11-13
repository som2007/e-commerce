<?php

namespace Rk\Threadwell\Core;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;

    private static ?PDO $shared = null;

    public function __construct()
    {
        $driver = Config::get('database.driver', 'mysql');

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            if ($driver === 'sqlite') {
                $path = Config::get('database.sqlite_path');

                $this->connection = new CompatPdo(
                    'sqlite:' . $path,
                    null,
                    null,
                    $options
                );

                $this->connection->exec('PRAGMA foreign_keys = ON');

                return;
            }

            $host = Config::get('database.host');
            $port = Config::get('database.port');
            $database = Config::get('database.database');

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

            $options[PDO::ATTR_EMULATE_PREPARES] = false;

            $this->connection = new PDO(
                $dsn,
                Config::get('database.username'),
                Config::get('database.password'),
                $options
            );
        } catch (PDOException $e) {
            throw new PDOException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    public function connection(): PDO
    {
        return $this->connection;
    }

    /** One shared connection per request. */
    public static function shared(): PDO
    {
        return self::$shared ??= (new self())->connection();
    }
}
