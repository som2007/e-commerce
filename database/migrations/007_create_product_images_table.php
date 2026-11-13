<?php

use Rk\Threadwell\Core\Migration;

return function (PDO $db): Migration {

    return new class($db) extends Migration
    {
        public function up(): void
        {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS product_images (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

                    product_id BIGINT UNSIGNED NOT NULL,

                    image_path VARCHAR(255) NOT NULL,
                    alt_text VARCHAR(255) NULL,

                    is_primary TINYINT(1) NOT NULL DEFAULT 0,
                    sort_order INT UNSIGNED NOT NULL DEFAULT 0,

                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                    CONSTRAINT fk_product_images_product
                        FOREIGN KEY (product_id)
                        REFERENCES products(id)
                        ON UPDATE CASCADE
                        ON DELETE CASCADE,

                    INDEX idx_product_images_product (product_id),
                    INDEX idx_product_images_primary (
                        product_id,
                        is_primary
                    )

                ) ENGINE=InnoDB
                DEFAULT CHARSET=utf8mb4
                COLLATE=utf8mb4_unicode_ci
            ");
        }

        public function down(): void
        {
            $this->db->exec("
                DROP TABLE IF EXISTS product_images
            ");
        }
    };
};