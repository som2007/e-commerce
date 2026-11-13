<?php

namespace Rk\Threadwell\Models;

use PDO;

/**
 * Read-side queries for the storefront: menu, collections, product pages, search.
 */
class Catalog
{
    public const SORTS = [
        'manual' => 'Featured',
        'relevance' => 'Most relevant',
        'best-selling' => 'Best selling',
        'title-ascending' => 'Alphabetically, A-Z',
        'title-descending' => 'Alphabetically, Z-A',
        'price-ascending' => 'Price, low to high',
        'price-descending' => 'Price, high to low',
        'created-ascending' => 'Date, old to new',
        'created-descending' => 'Date, new to old',
    ];

    public function __construct(private PDO $db)
    {
    }

    /* ------------------------------------------------------------ menu */

    public function menuTree(): array
    {
        static $tree = null;

        if ($tree !== null) {
            return $tree;
        }

        $rows = $this->db->query(
            "SELECT id, parent_id, name, slug, description, image FROM categories
             WHERE status = 'active' ORDER BY sort_order ASC, name ASC"
        )->fetchAll();

        $byParent = [];
        foreach ($rows as $r) {
            $byParent[(int) $r['parent_id']][] = $r;
        }

        $build = function (int $parent) use (&$build, $byParent): array {
            $out = [];
            foreach ($byParent[$parent] ?? [] as $r) {
                $r['children'] = $build((int) $r['id']);
                $out[] = $r;
            }

            return $out;
        };

        return $tree = $build(0);
    }

    public function category(string $slug): ?array
    {
        $st = $this->db->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
        $st->execute([$slug]);

        return $st->fetch() ?: null;
    }

    /** Category id + every descendant id. */
    public function categoryIds(int $id): array
    {
        $ids = [$id];
        $queue = [$id];

        while ($queue) {
            $in = implode(',', array_map('intval', $queue));
            $queue = $this->db->query("SELECT id FROM categories WHERE parent_id IN ({$in})")
                ->fetchAll(PDO::FETCH_COLUMN);
            $ids = array_merge($ids, array_map('intval', $queue));
        }

        return array_values(array_unique($ids));
    }

    public function breadcrumb(array $category): array
    {
        $trail = [$category];
        $cur = $category;

        while (!empty($cur['parent_id'])) {
            $st = $this->db->prepare('SELECT id, parent_id, name, slug FROM categories WHERE id = ?');
            $st->execute([$cur['parent_id']]);
            $cur = $st->fetch();

            if (!$cur) {
                break;
            }

            array_unshift($trail, $cur);
        }

        return $trail;
    }

    /* ------------------------------------------------------- listings */

    private function cardSelect(): string
    {
        return "p.id, p.name, p.slug, p.price, p.sale_price, p.is_featured, p.created_at,
                m.color, m.color_hex, m.rating_avg, m.rating_count, m.badge, m.sold_count, m.group_key,
                (SELECT image_path FROM product_images i WHERE i.product_id = p.id
                    ORDER BY i.is_primary DESC, i.sort_order ASC, i.id ASC LIMIT 1) AS image,
                (SELECT image_path FROM product_images i WHERE i.product_id = p.id
                    ORDER BY i.is_primary DESC, i.sort_order ASC, i.id ASC LIMIT 1 OFFSET 1) AS image2,
                (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id AND v.stock > 0) AS in_stock";
    }

    private function orderBy(string $sort): string
    {
        return match ($sort) {
            'best-selling', 'relevance' => 'COALESCE(m.sold_count, 0) DESC, p.id ASC',
            'title-ascending' => 'p.name ASC',
            'title-descending' => 'p.name DESC',
            'price-ascending' => 'COALESCE(p.sale_price, p.price) ASC, p.id ASC',
            'price-descending' => 'COALESCE(p.sale_price, p.price) DESC, p.id ASC',
            'created-ascending' => 'p.created_at ASC, p.id ASC',
            'created-descending' => 'p.created_at DESC, p.id DESC',
            default => 'p.is_featured DESC, p.id ASC',
        };
    }

    /**
     * @param array{category_ids?:int[], q?:string, in_stock?:bool, min?:?float, max?:?float, sort?:string, page?:int, per_page?:int} $o
     * @return array{items:array, total:int, page:int, pages:int}
     */
    public function listProducts(array $o): array
    {
        $where = ["p.status = 'active'"];
        $params = [];
        $join = 'LEFT JOIN product_meta m ON m.product_id = p.id';

        if (!empty($o['category_ids'])) {
            $in = implode(',', array_map('intval', $o['category_ids']));
            $where[] = "p.id IN (SELECT cp.product_id FROM category_product cp WHERE cp.category_id IN ({$in}))";
        }

        if (!empty($o['q'])) {
            foreach (preg_split('/\s+/', trim($o['q'])) as $term) {
                if ($term === '') {
                    continue;
                }
                $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
                $where[] = "(p.name LIKE ? ESCAPE '!' OR m.color LIKE ? ESCAPE '!' OR m.fabric LIKE ? ESCAPE '!'
                    OR p.short_description LIKE ? ESCAPE '!')";
                array_push($params, $like, $like, $like, $like);
            }
        }

        if (!empty($o['in_stock'])) {
            $where[] = 'EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.stock > 0)';
        }

        if (isset($o['min']) && $o['min'] !== null) {
            $where[] = 'COALESCE(p.sale_price, p.price) >= ?';
            $params[] = $o['min'];
        }

        if (isset($o['max']) && $o['max'] !== null) {
            $where[] = 'COALESCE(p.sale_price, p.price) <= ?';
            $params[] = $o['max'];
        }

        $whereSql = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM products p {$join} WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $perPage = max(1, min((int) ($o['per_page'] ?? 20), 60));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min((int) ($o['page'] ?? 1), $pages));
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT ' . $this->cardSelect() . " FROM products p {$join} WHERE {$whereSql}
                ORDER BY " . $this->orderBy($o['sort'] ?? 'manual') . " LIMIT {$perPage} OFFSET {$offset}";

        $st = $this->db->prepare($sql);
        $st->execute($params);

        return ['items' => $st->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** Products of a collection by category slug (for home page rails). */
    public function collectionRail(string $slug, int $limit = 8, string $sort = 'manual'): array
    {
        $cat = $this->category($slug);

        if (!$cat) {
            return [];
        }

        return $this->listProducts([
            'category_ids' => $this->categoryIds((int) $cat['id']),
            'sort' => $sort,
            'per_page' => $limit,
        ])['items'];
    }

    /** Min / max price inside a set of categories (price filter placeholder). */
    public function priceRange(array $categoryIds = []): array
    {
        $sql = 'SELECT MIN(COALESCE(sale_price, price)) lo, MAX(COALESCE(sale_price, price)) hi FROM products WHERE status = \'active\'';

        if ($categoryIds) {
            $in = implode(',', array_map('intval', $categoryIds));
            $sql .= " AND id IN (SELECT product_id FROM category_product WHERE category_id IN ({$in}))";
        }

        $r = $this->db->query($sql)->fetch();

        return [(int) floor((float) $r['lo']), (int) ceil((float) $r['hi'])];
    }

    /* -------------------------------------------------------- detail */

    public function product(string $slug): ?array
    {
        $st = $this->db->prepare(
            "SELECT p.*, m.group_key, m.color, m.color_hex, m.fabric, m.fit, m.sleeve, m.collar, m.occasion,
                    m.care, m.badge, m.rating_avg, m.rating_count, m.sold_count, c.name AS category_name, c.slug AS category_slug
             FROM products p
             LEFT JOIN product_meta m ON m.product_id = p.id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.status = 'active' LIMIT 1"
        );
        $st->execute([$slug]);

        return $st->fetch() ?: null;
    }

    public function variants(int $productId): array
    {
        $st = $this->db->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sort_order, id');
        $st->execute([$productId]);

        return $st->fetchAll();
    }

    public function images(int $productId): array
    {
        $st = $this->db->prepare(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC'
        );
        $st->execute([$productId]);

        return $st->fetchAll();
    }

    /** Other colours of the same style. */
    public function siblings(string $groupKey): array
    {
        $st = $this->db->prepare(
            "SELECT p.id, p.slug, m.color, m.color_hex,
                    (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id AND v.stock > 0) AS in_stock
             FROM products p JOIN product_meta m ON m.product_id = p.id
             WHERE m.group_key = ? AND p.status = 'active' ORDER BY p.id"
        );
        $st->execute([$groupKey]);

        return $st->fetchAll();
    }

    public function related(array $product, int $limit = 8): array
    {
        $st = $this->db->prepare(
            'SELECT ' . $this->cardSelect() . "
             FROM products p LEFT JOIN product_meta m ON m.product_id = p.id
             WHERE p.status = 'active' AND p.id != ?
               AND p.id IN (SELECT cp.product_id FROM category_product cp
                            WHERE cp.category_id IN (SELECT category_id FROM category_product WHERE product_id = ?))
               AND COALESCE(m.group_key, '') != ?
             ORDER BY COALESCE(m.sold_count, 0) DESC LIMIT {$limit}"
        );
        $st->execute([$product['id'], $product['id'], $product['group_key'] ?? '']);

        return $st->fetchAll();
    }

    public function reviews(int $productId, int $limit = 20): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM reviews WHERE product_id = ? AND status = 'approved' ORDER BY id DESC LIMIT {$limit}"
        );
        $st->execute([$productId]);

        return $st->fetchAll();
    }

    /** [5 => count, 4 => count, ...] */
    public function ratingBreakdown(int $productId): array
    {
        $out = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $st = $this->db->prepare(
            "SELECT rating, COUNT(*) c FROM reviews WHERE product_id = ? AND status = 'approved' GROUP BY rating"
        );
        $st->execute([$productId]);

        foreach ($st->fetchAll() as $r) {
            $out[(int) $r['rating']] = (int) $r['c'];
        }

        return $out;
    }

    public function refreshRating(int $productId): void
    {
        $st = $this->db->prepare(
            "SELECT COUNT(*) c, COALESCE(AVG(rating), 0) a FROM reviews WHERE product_id = ? AND status = 'approved'"
        );
        $st->execute([$productId]);
        $r = $st->fetch();

        $this->db->prepare('UPDATE product_meta SET rating_count = ?, rating_avg = ? WHERE product_id = ?')
            ->execute([(int) $r['c'], round((float) $r['a'], 2), $productId]);
    }

    /** Quick suggestions for the search box. */
    public function suggest(string $q, int $limit = 6): array
    {
        return $this->listProducts(['q' => $q, 'per_page' => $limit, 'sort' => 'best-selling'])['items'];
    }
}
