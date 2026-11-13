<?php

$basePath = require dirname(__DIR__) . '/bootstrap/config.php';

use Rk\Threadwell\Core\Database;

$db = (new Database())->connection();

echo "Database connection successful." . PHP_EOL;

require_once $basePath . '/database/seeders/RoleSeeder.php';
require_once $basePath . '/database/seeders/CatalogSeeder.php';
require_once $basePath . '/database/seeders/StoreSeeder.php';

(new RoleSeeder())->run($db);

// Legacy products from version 1 (kept, so images/links keep working).
$legacy = $db->query("SELECT COUNT(*) FROM categories WHERE slug = 'mens-shirts'")->fetchColumn();
if (!$legacy) {
    $now = date('Y-m-d H:i:s');
    $legacyCats = [
        ['Ethnic Wear', 'ethnic-wear'], ['Accessories', 'accessories'],
        ['Shirts', 'mens-shirts'], ['T-Shirts', 'mens-tshirts'], ['Kurtas', 'kurtas'],
    ];
    $ins = $db->prepare("INSERT INTO categories (name, slug, status) VALUES (?, ?, 'inactive')");
    foreach ($legacyCats as $c) {
        $ex = $db->prepare('SELECT id FROM categories WHERE slug = ?');
        $ex->execute([$c[1]]);
        if ($ex->fetchColumn() === false) {
            $ins->execute($c);
        }
    }

    $products = [
        ['mens-shirts', 'Classic Linen Shirt', 'classic-linen-shirt', 'FIL-MEN-001', 'A versatile linen shirt for everyday wear.', 1999, 1499],
        ['mens-shirts', 'Premium Casual Shirt', 'premium-casual-shirt', 'FIL-MEN-002', 'A smart casual shirt for everyday styling.', 1799, 1299],
        ['mens-tshirts', 'Essential Cotton T-Shirt', 'essential-cotton-tshirt', 'FIL-MEN-003', 'A comfortable cotton T-shirt.', 999, 799],
        ['kurtas', 'Classic Ethnic Kurta', 'classic-ethnic-kurta', 'FIL-ETH-001', 'A classic kurta for festive occasions.', 2499, 1999],
    ];
    $find = $db->prepare('SELECT id FROM categories WHERE slug = ?');
    $exP = $db->prepare('SELECT id FROM products WHERE slug = ?');
    $insP = $db->prepare(
        "INSERT INTO products (category_id, name, slug, sku, short_description, price, sale_price, status, is_featured)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 1)"
    );
    foreach ($products as $p) {
        $exP->execute([$p[2]]);
        if ($exP->fetchColumn() !== false) {
            continue;
        }
        $find->execute([$p[0]]);
        $insP->execute([$find->fetchColumn(), $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]]);
    }
}

(new CatalogSeeder())->run($db);
(new StoreSeeder())->run($db);

echo "Seeding completed." . PHP_EOL;
