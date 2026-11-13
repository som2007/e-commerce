<?php

use Rk\Threadwell\Core\Migration;

return function (PDO $db): Migration {

    return new class($db) extends Migration
    {
        public function up(): void
        {
            // Categories table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS categories (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                    parent_id BIGINT UNSIGNED NULL,

                    name VARCHAR(150) NOT NULL,
                    slug VARCHAR(180) NOT NULL UNIQUE,
                    description TEXT NULL,
                    image VARCHAR(255) NULL,

                    status ENUM(
                        'active',
                        'inactive'
                    ) NOT NULL DEFAULT 'active',

                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,

                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,

                    CONSTRAINT fk_categories_parent
                        FOREIGN KEY (parent_id)
                        REFERENCES categories(id)
                        ON UPDATE CASCADE
                        ON DELETE SET NULL,

                    INDEX idx_categories_parent (parent_id),
                    INDEX idx_categories_status (status)

                ) ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
            ");

            // Products table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS products (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                    category_id BIGINT UNSIGNED NULL,

                    name VARCHAR(200) NOT NULL,
                    slug VARCHAR(220) NOT NULL UNIQUE,
                    sku VARCHAR(100) NOT NULL UNIQUE,

                    short_description VARCHAR(500) NULL,
                    description TEXT NULL,

                    price DECIMAL(10,2) NOT NULL,
                    sale_price DECIMAL(10,2) NULL,

                    status ENUM(
                        'draft',
                        'active',
                        'inactive'
                    ) NOT NULL DEFAULT 'draft',

                    is_featured TINYINT(1) NOT NULL DEFAULT 0,

                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,

                    CONSTRAINT fk_products_category
                        FOREIGN KEY (category_id)
                        REFERENCES categories(id)
                        ON UPDATE CASCADE
                        ON DELETE SET NULL,

                    INDEX idx_products_category (category_id),
                    INDEX idx_products_status (status),
                    INDEX idx_products_featured (is_featured)

                ) ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
            ");
        }

        public function down(): void
        {
            $this->db->exec("
                DROP TABLE IF EXISTS products
            ");

            $this->db->exec("
                DROP TABLE IF EXISTS categories
            ");
        }
    };
};