<?php

use Rk\Threadwell\Core\Migration;

return function (PDO $db): Migration {

    return new class($db) extends Migration
    {
        public function up(): void
        {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                    role_id BIGINT UNSIGNED NOT NULL,

                    first_name VARCHAR(100) NOT NULL,
                    last_name VARCHAR(100) NULL,

                    email VARCHAR(191) NOT NULL UNIQUE,
                    phone VARCHAR(20) NULL UNIQUE,

                    password VARCHAR(255) NOT NULL,

                    status ENUM(
                        'active',
                        'inactive',
                        'blocked'
                    ) NOT NULL DEFAULT 'active',

                    email_verified_at TIMESTAMP NULL DEFAULT NULL,

                    last_login_at TIMESTAMP NULL DEFAULT NULL,

                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,

                    CONSTRAINT fk_users_role
                        FOREIGN KEY (role_id)
                        REFERENCES roles(id)
                        ON UPDATE CASCADE
                        ON DELETE RESTRICT

                ) ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
            ");
        }

        public function down(): void
        {
            $this->db->exec(
                "DROP TABLE IF EXISTS users"
            );
        }
    };
};