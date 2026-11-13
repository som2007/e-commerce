<?php

namespace Rk\Threadwell\Models;

use PDO;

class Category
{
    public function __construct(
        private PDO $db
    ) {
    }

    public function getActiveCategories(): array
    {
        $statement = $this->db->prepare("
            SELECT
                id,
                parent_id,
                name,
                slug,
                description,
                image
            FROM categories
            WHERE status = 'active'
            ORDER BY sort_order ASC, name ASC
        ");

        $statement->execute();

        return $statement->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->db->prepare("
            SELECT *
            FROM categories
            WHERE slug = ?
              AND status = 'active'
            LIMIT 1
        ");

        $statement->execute([$slug]);

        $category = $statement->fetch();

        return $category ?: null;
    }
}