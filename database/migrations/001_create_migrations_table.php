<?php

use Rk\Threadwell\Core\Migration;

return function (PDO $db): Migration {

    return new class($db) extends Migration
    {
        public function up(): void
        {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS migrations (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    batch INT UNSIGNED NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
            ");
        }

        public function down(): void
        {
            $this->db->exec(
                "DROP TABLE IF EXISTS migrations"
            );
        }
    };
};