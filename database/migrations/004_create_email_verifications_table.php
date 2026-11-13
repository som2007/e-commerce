<?php

use Rk\Threadwell\Core\Migration;

return function (PDO $db): Migration {

    return new class($db) extends Migration
    {
        public function up(): void
        {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS email_verifications (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                    user_id BIGINT UNSIGNED NOT NULL,

                    email VARCHAR(191) NOT NULL,

                    otp_hash VARCHAR(255) NOT NULL,

                    expires_at TIMESTAMP NOT NULL,

                    verified_at TIMESTAMP NULL DEFAULT NULL,

                    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,

                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,

                    CONSTRAINT fk_email_verifications_user
                        FOREIGN KEY (user_id)
                        REFERENCES users(id)
                        ON UPDATE CASCADE
                        ON DELETE CASCADE,

                    INDEX idx_email_verifications_user (
                        user_id
                    ),

                    INDEX idx_email_verifications_email (
                        email
                    ),

                    INDEX idx_email_verifications_expires (
                        expires_at
                    )
                ) ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
            ");
        }

        public function down(): void
        {
            $this->db->exec(
                "DROP TABLE IF EXISTS email_verifications"
            );
        }
    };
};