<?php

namespace Rk\Threadwell\Models;

use PDO;

class Product
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function getFeaturedProducts(
        int $limit = 8
    ): array {
        $limit = max(1, min($limit, 50));

        $statement = $this->db->prepare("
            SELECT
                p.id,
                p.category_id,
                p.name,
                p.slug,
                p.sku,
                p.short_description,
                p.price,
                p.sale_price,
                p.is_featured,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c
                ON c.id = p.category_id
            WHERE p.status = 'active'
              AND p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT {$limit}
        ");

        $statement->execute();

        return $statement->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->db->prepare("
            SELECT
                p.*,
                c.name AS category_name
            FROM products p
            LEFT JOIN categories c
                ON c.id = p.category_id
            WHERE p.slug = ?
              AND p.status = 'active'
            LIMIT 1
        ");

        $statement->execute([$slug]);

        $product = $statement->fetch();

        return $product ?: null;
    }

    public function getByCategory(
        int $categoryId,
        int $limit = 12,
        int $offset = 0
    ): array {
        $limit = max(1, min($limit, 50));
        $offset = max(0, $offset);

        $statement = $this->db->prepare("
            SELECT
                id,
                category_id,
                name,
                slug,
                sku,
                short_description,
                price,
                sale_price
            FROM products
            WHERE category_id = ?
              AND status = 'active'
            ORDER BY created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");

        $statement->execute([$categoryId]);

        return $statement->fetchAll();
    }


    public function getImages(int $productId): array
{
    $statement = $this->db->prepare("
        SELECT
            id,
            product_id,
            image_path,
            alt_text,
            is_primary,
            sort_order
        FROM product_images
        WHERE product_id = ?
        ORDER BY is_primary DESC, sort_order ASC, id ASC
    ");

    $statement->execute([$productId]);

    return $statement->fetchAll();
}
}