<?php

use Rk\Threadwell\Core\Migration;

return function (PDO $db): Migration {

    return new class($db) extends Migration
    {
        public function up(): void
        {
            // Many-to-many: a product can sit in several collections.
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS category_product (
                    category_id BIGINT UNSIGNED NOT NULL,
                    product_id BIGINT UNSIGNED NOT NULL,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    PRIMARY KEY (category_id, product_id),
                    CONSTRAINT fk_cp_category FOREIGN KEY (category_id)
                        REFERENCES categories(id) ON DELETE CASCADE,
                    CONSTRAINT fk_cp_product FOREIGN KEY (product_id)
                        REFERENCES products(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Extra product attributes (colour, fabric, rating cache ...)
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS product_meta (
                    product_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                    group_key VARCHAR(150) NOT NULL,
                    color VARCHAR(80) NULL,
                    color_hex VARCHAR(7) NULL,
                    fabric VARCHAR(150) NULL,
                    fit VARCHAR(80) NULL,
                    sleeve VARCHAR(40) NULL,
                    collar VARCHAR(80) NULL,
                    occasion VARCHAR(255) NULL,
                    care TEXT NULL,
                    badge VARCHAR(60) NULL,
                    rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0,
                    rating_count INT UNSIGNED NOT NULL DEFAULT 0,
                    sold_count INT UNSIGNED NOT NULL DEFAULT 0,
                    CONSTRAINT fk_pm_product FOREIGN KEY (product_id)
                        REFERENCES products(id) ON DELETE CASCADE,
                    INDEX idx_pm_group (group_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Sizes per product (price can differ: plus sizes cost more).
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS product_variants (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    product_id BIGINT UNSIGNED NOT NULL,
                    size VARCHAR(20) NOT NULL,
                    sku VARCHAR(120) NOT NULL UNIQUE,
                    price DECIMAL(10,2) NOT NULL,
                    sale_price DECIMAL(10,2) NULL,
                    stock INT NOT NULL DEFAULT 0,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
                    CONSTRAINT fk_pv_product FOREIGN KEY (product_id)
                        REFERENCES products(id) ON DELETE CASCADE,
                    INDEX idx_pv_product (product_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS reviews (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    product_id BIGINT UNSIGNED NOT NULL,
                    user_id BIGINT UNSIGNED NULL,
                    name VARCHAR(120) NOT NULL,
                    rating TINYINT NOT NULL,
                    title VARCHAR(180) NULL,
                    body TEXT NULL,
                    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id)
                        REFERENCES products(id) ON DELETE CASCADE,
                    INDEX idx_reviews_product (product_id, status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        public function down(): void
        {
            foreach (['reviews', 'product_variants', 'product_meta', 'category_product'] as $t) {
                $this->db->exec("DROP TABLE IF EXISTS {$t}");
            }
        }
    };
};
