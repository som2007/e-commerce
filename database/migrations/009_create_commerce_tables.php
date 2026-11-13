<?php

use Rk\Threadwell\Core\Migration;

return function (PDO $db): Migration {

    return new class($db) extends Migration
    {
        public function up(): void
        {
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS addresses (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NOT NULL,
                    name VARCHAR(150) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    line1 VARCHAR(255) NOT NULL,
                    line2 VARCHAR(255) NULL,
                    city VARCHAR(100) NOT NULL,
                    state VARCHAR(100) NOT NULL,
                    pincode VARCHAR(10) NOT NULL,
                    is_default TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_addr_user FOREIGN KEY (user_id)
                        REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_addr_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS coupons (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(40) NOT NULL UNIQUE,
                    type ENUM('percent','flat') NOT NULL DEFAULT 'percent',
                    value DECIMAL(10,2) NOT NULL,
                    min_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
                    max_discount DECIMAL(10,2) NULL,
                    prepaid_only TINYINT(1) NOT NULL DEFAULT 0,
                    usage_limit INT UNSIGNED NULL,
                    used_count INT UNSIGNED NOT NULL DEFAULT 0,
                    expires_at DATETIME NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS orders (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    order_number VARCHAR(30) NOT NULL UNIQUE,
                    user_id BIGINT UNSIGNED NULL,
                    email VARCHAR(191) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    ship_name VARCHAR(150) NOT NULL,
                    ship_line1 VARCHAR(255) NOT NULL,
                    ship_line2 VARCHAR(255) NULL,
                    ship_city VARCHAR(100) NOT NULL,
                    ship_state VARCHAR(100) NOT NULL,
                    ship_pincode VARCHAR(10) NOT NULL,
                    subtotal DECIMAL(10,2) NOT NULL,
                    discount DECIMAL(10,2) NOT NULL DEFAULT 0,
                    shipping DECIMAL(10,2) NOT NULL DEFAULT 0,
                    cod_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
                    total DECIMAL(10,2) NOT NULL,
                    coupon_code VARCHAR(40) NULL,
                    payment_method ENUM('cod','online') NOT NULL,
                    payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
                    payment_ref VARCHAR(120) NULL,
                    status ENUM('placed','confirmed','packed','shipped','delivered','cancelled') NOT NULL DEFAULT 'placed',
                    courier VARCHAR(80) NULL,
                    tracking_no VARCHAR(80) NULL,
                    notes TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_orders_user (user_id),
                    INDEX idx_orders_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS order_items (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    order_id BIGINT UNSIGNED NOT NULL,
                    product_id BIGINT UNSIGNED NULL,
                    variant_id BIGINT UNSIGNED NULL,
                    name VARCHAR(255) NOT NULL,
                    color VARCHAR(80) NULL,
                    size VARCHAR(20) NULL,
                    image VARCHAR(255) NULL,
                    price DECIMAL(10,2) NOT NULL,
                    qty INT UNSIGNED NOT NULL,
                    CONSTRAINT fk_oi_order FOREIGN KEY (order_id)
                        REFERENCES orders(id) ON DELETE CASCADE,
                    INDEX idx_oi_order (order_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS order_status_history (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    order_id BIGINT UNSIGNED NOT NULL,
                    status VARCHAR(30) NOT NULL,
                    note VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_osh_order FOREIGN KEY (order_id)
                        REFERENCES orders(id) ON DELETE CASCADE,
                    INDEX idx_osh_order (order_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS exchange_requests (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    order_number VARCHAR(30) NOT NULL,
                    email VARCHAR(191) NOT NULL,
                    item_note VARCHAR(255) NULL,
                    reason VARCHAR(120) NOT NULL,
                    new_size VARCHAR(20) NULL,
                    details TEXT NULL,
                    status ENUM('pending','approved','rejected','completed') NOT NULL DEFAULT 'pending',
                    admin_note VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS pages (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    slug VARCHAR(120) NOT NULL UNIQUE,
                    title VARCHAR(200) NOT NULL,
                    body LONGTEXT NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS blog_posts (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    slug VARCHAR(180) NOT NULL UNIQUE,
                    title VARCHAR(220) NOT NULL,
                    excerpt VARCHAR(400) NULL,
                    body LONGTEXT NOT NULL,
                    cover VARCHAR(255) NULL,
                    is_published TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS contact_messages (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    email VARCHAR(191) NOT NULL,
                    phone VARCHAR(20) NULL,
                    message TEXT NOT NULL,
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $this->db->exec("
                CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(191) NOT NULL UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        public function down(): void
        {
            foreach ([
                'newsletter_subscribers', 'contact_messages', 'blog_posts', 'pages',
                'exchange_requests', 'order_status_history', 'order_items', 'orders',
                'coupons', 'addresses',
            ] as $t) {
                $this->db->exec("DROP TABLE IF EXISTS {$t}");
            }
        }
    };
};
