<?php

namespace Rk\Threadwell\Controllers\Admin;

use Rk\Threadwell\Controllers\Controller;
use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Models\Catalog;
use Rk\Threadwell\Services\OrderService;

class AdminController extends Controller
{
    private const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'];

    private function page(string $view, array $data = []): string
    {
        return $this->view('admin/' . $view, $data + ['title' => 'Admin'], 'admin/layout');
    }

    private function rows(string $sql, array $params = []): array
    {
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll();
    }

    private function one(string $sql, array $params = []): ?array
    {
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetch() ?: null;
    }

    private function scalar(string $sql, array $params = []): mixed
    {
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchColumn();
    }

    /* ------------------------------------------------------- dashboard */

    public function dashboard(Request $request): string
    {
        $today = date('Y-m-d');

        return $this->page('dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'Orders today' => (int) $this->scalar("SELECT COUNT(*) FROM orders WHERE created_at >= ?", [$today . ' 00:00:00']),
                'Revenue (paid / delivered)' => money($this->scalar("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid' AND status != 'cancelled'")),
                'Pending orders' => (int) $this->scalar("SELECT COUNT(*) FROM orders WHERE status IN ('placed','confirmed','packed')"),
                'Products' => (int) $this->scalar("SELECT COUNT(*) FROM products"),
                'Customers' => (int) $this->scalar("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = 'customer'"),
                'Reviews to approve' => (int) $this->scalar("SELECT COUNT(*) FROM reviews WHERE status = 'pending'"),
                'Open exchanges' => (int) $this->scalar("SELECT COUNT(*) FROM exchange_requests WHERE status = 'pending'"),
                'Unread messages' => (int) $this->scalar("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0"),
            ],
            'recent' => $this->rows('SELECT * FROM orders ORDER BY id DESC LIMIT 8'),
            'lowStock' => $this->rows(
                "SELECT p.name, v.size, v.stock FROM product_variants v JOIN products p ON p.id = v.product_id
                 WHERE v.stock <= 3 AND p.status = 'active' ORDER BY v.stock, p.name LIMIT 10"
            ),
        ]);
    }

    /* -------------------------------------------------------- products */

    public function products(Request $request): string
    {
        $q = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $per = 25;
        $where = $q !== '' ? 'WHERE p.name LIKE ? OR p.sku LIKE ?' : '';
        $params = $q !== '' ? ["%{$q}%", "%{$q}%"] : [];

        $total = (int) $this->scalar("SELECT COUNT(*) FROM products p {$where}", $params);

        return $this->page('products', [
            'title' => 'Products',
            'q' => $q,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $per)),
            'total' => $total,
            'items' => $this->rows(
                "SELECT p.id, p.name, p.sku, p.price, p.sale_price, p.status, m.color,
                        (SELECT COALESCE(SUM(stock),0) FROM product_variants v WHERE v.product_id = p.id) AS stock,
                        (SELECT image_path FROM product_images i WHERE i.product_id = p.id ORDER BY is_primary DESC, sort_order, id LIMIT 1) AS image
                 FROM products p LEFT JOIN product_meta m ON m.product_id = p.id {$where}
                 ORDER BY p.id DESC LIMIT {$per} OFFSET " . (($page - 1) * $per),
                $params
            ),
        ]);
    }

    public function productForm(Request $request): string
    {
        $id = (int) $request->param('id', 0);
        $product = null;
        $variants = [];
        $images = [];
        $catIds = [];

        if ($id) {
            $product = $this->one(
                'SELECT p.*, m.group_key, m.color, m.color_hex, m.fabric, m.fit, m.sleeve, m.collar, m.occasion, m.care, m.badge
                 FROM products p LEFT JOIN product_meta m ON m.product_id = p.id WHERE p.id = ?',
                [$id]
            );

            if (!$product) {
                abort(404);
            }

            foreach ((new Catalog(db()))->variants($id) as $v) {
                $variants[$v['size']] = $v;
            }

            $images = (new Catalog(db()))->images($id);
            $catIds = array_column($this->rows('SELECT category_id FROM category_product WHERE product_id = ?', [$id]), 'category_id');
        }

        return $this->page('product-form', [
            'title' => $product ? 'Edit product' : 'New product',
            'product' => $product,
            'variants' => $variants,
            'images' => $images,
            'catIds' => array_map('intval', $catIds),
            'categories' => $this->rows('SELECT id, name, parent_id FROM categories ORDER BY sort_order, name'),
            'sizes' => self::SIZES,
        ]);
    }

    public function productSave(Request $request): void
    {
        $id = (int) $request->param('id', 0);
        $db = db();

        $name = $this->str($request, 'name', 200);
        $price = (float) $request->input('price', 0);
        $saleRaw = trim((string) $request->input('sale_price', ''));
        $sale = $saleRaw !== '' ? (float) $saleRaw : null;
        $status = in_array($request->input('status'), ['draft', 'active', 'inactive'], true) ? $request->input('status') : 'draft';
        $categoryIds = array_map('intval', (array) ($_POST['category_ids'] ?? []));
        $primaryCat = $categoryIds[0] ?? null;

        if ($name === '' || $price <= 0 || ($sale !== null && $sale >= $price)) {
            flash('error', 'Name and price are required, and the sale price must be below the regular price.');
            $this->back('/admin/products');
        }

        $slug = slugify($this->str($request, 'slug', 220) ?: $name);
        $sku = $this->str($request, 'sku', 100) ?: strtoupper('SKU-' . substr(md5($slug), 0, 8));
        $short = $this->str($request, 'short_description', 500);
        $desc = $this->str($request, 'description', 5000);
        $featured = $request->input('is_featured') ? 1 : 0;

        try {
            $db->beginTransaction();

            if ($id) {
                $db->prepare(
                    'UPDATE products SET category_id=?, name=?, slug=?, sku=?, short_description=?, description=?, price=?, sale_price=?, status=?, is_featured=? WHERE id=?'
                )->execute([$primaryCat, $name, $slug, $sku, $short ?: null, $desc ?: null, $price, $sale, $status, $featured, $id]);
            } else {
                $db->prepare(
                    'INSERT INTO products (category_id, name, slug, sku, short_description, description, price, sale_price, status, is_featured)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([$primaryCat, $name, $slug, $sku, $short ?: null, $desc ?: null, $price, $sale, $status, $featured]);
                $id = (int) $db->lastInsertId();
            }

            $hex = preg_replace('/[^0-9a-fA-F]/', '', $this->str($request, 'color_hex', 7));
            $meta = [
                $this->str($request, 'group_key', 150) ?: $slug, $this->str($request, 'color', 80) ?: null,
                strlen($hex) === 6 ? strtolower($hex) : null, $this->str($request, 'fabric', 150) ?: null,
                $this->str($request, 'fit', 80) ?: null, $this->str($request, 'sleeve', 40) ?: null,
                $this->str($request, 'collar', 80) ?: null, $this->str($request, 'occasion', 255) ?: null,
                $this->str($request, 'care', 1000) ?: null, $this->str($request, 'badge', 60) ?: null,
            ];

            if ($this->scalar('SELECT 1 FROM product_meta WHERE product_id = ?', [$id])) {
                $db->prepare('UPDATE product_meta SET group_key=?, color=?, color_hex=?, fabric=?, fit=?, sleeve=?, collar=?, occasion=?, care=?, badge=? WHERE product_id=?')
                    ->execute([...$meta, $id]);
            } else {
                $db->prepare('INSERT INTO product_meta (group_key, color, color_hex, fabric, fit, sleeve, collar, occasion, care, badge, product_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute([...$meta, $id]);
            }

            // Collections
            $db->prepare('DELETE FROM category_product WHERE product_id = ?')->execute([$id]);
            $link = $db->prepare('INSERT INTO category_product (category_id, product_id, sort_order) VALUES (?, ?, ?)');
            foreach (array_unique($categoryIds) as $n => $cid) {
                $link->execute([$cid, $id, $n]);
            }

            // Variants (size rows)
            $submitted = (array) ($_POST['v'] ?? []);
            $existing = [];
            foreach ($this->rows('SELECT id, size FROM product_variants WHERE product_id = ?', [$id]) as $row) {
                $existing[$row['size']] = (int) $row['id'];
            }

            foreach (self::SIZES as $k => $size) {
                $row = $submitted[$size] ?? [];
                $stockRaw = trim((string) ($row['stock'] ?? ''));

                if ($stockRaw === '') {
                    if (isset($existing[$size])) {
                        $db->prepare('DELETE FROM product_variants WHERE id = ?')->execute([$existing[$size]]);
                    }
                    continue;
                }

                $vPrice = trim((string) ($row['price'] ?? '')) !== '' ? (float) $row['price'] : $price;
                $vSale = trim((string) ($row['sale'] ?? '')) !== '' ? (float) $row['sale'] : ($sale !== null && $vPrice === $price ? $sale : null);

                if (isset($existing[$size])) {
                    $db->prepare('UPDATE product_variants SET price=?, sale_price=?, stock=?, sort_order=? WHERE id=?')
                        ->execute([$vPrice, $vSale, (int) $stockRaw, $k, $existing[$size]]);
                } else {
                    $db->prepare('INSERT INTO product_variants (product_id, size, sku, price, sale_price, stock, sort_order) VALUES (?,?,?,?,?,?,?)')
                        ->execute([$id, $size, $sku . '-' . $size, $vPrice, $vSale, (int) $stockRaw, $k]);
                }
            }

            // Colour based placeholder image when none exists yet
            if (!$this->scalar('SELECT 1 FROM product_images WHERE product_id = ?', [$id]) && strlen($hex) === 6) {
                $sleeve = $meta[5] === 'half' ? 'half' : 'full';
                foreach (['front', 'back', 'detail', 'folded'] as $n => $view) {
                    $db->prepare('INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (?,?,?,?,?)')
                        ->execute([$id, "shirt:{$hex}:{$view}:{$sleeve}", $name, $n === 0 ? 1 : 0, $n]);
                }
            }

            $db->commit();
        } catch (\PDOException $e) {
            $db->rollBack();
            flash('error', str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate')
                ? 'Slug or SKU already exists. Please use a different one.'
                : 'Could not save the product.');
            $this->back('/admin/products');
        }

        flash('success', 'Product saved.');
        redirect('/admin/products/' . $id . '/edit');
    }

    public function productDelete(Request $request): void
    {
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([(int) $request->param('id')]);
        flash('success', 'Product deleted.');
        redirect('/admin/products');
    }

    public function imageUpload(Request $request): void
    {
        $id = (int) $request->param('id');
        $dir = config('app.base_path') . '/public/assets/uploads/products';
        @mkdir($dir, 0775, true);

        $count = 0;
        $files = $_FILES['images'] ?? null;

        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $orig) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['size'][$i] > 6 * 1024 * 1024) {
                    continue;
                }

                $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($files['tmp_name'][$i]);
                $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;

                if (!$ext || @getimagesize($files['tmp_name'][$i]) === false) {
                    continue;
                }

                $name = $id . '-' . bin2hex(random_bytes(6)) . '.' . $ext;

                if (move_uploaded_file($files['tmp_name'][$i], $dir . '/' . $name)) {
                    $hasPrimary = $this->scalar('SELECT 1 FROM product_images WHERE product_id = ? AND is_primary = 1 AND image_path NOT LIKE ?', [$id, 'shirt:%']);
                    db()->prepare('INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (?,?,?,?,?)')
                        ->execute([$id, 'uploads/products/' . $name, 'Product image', $hasPrimary ? 0 : 1, 10 + $i]);

                    if (!$hasPrimary) {
                        // uploaded photo outranks generated placeholder
                        db()->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ? AND image_path LIKE 'shirt:%'")->execute([$id]);
                    }
                    $count++;
                }
            }
        }

        flash($count ? 'success' : 'error', $count ? "{$count} image(s) uploaded." : 'No valid image uploaded (JPG, PNG or WebP, max 6 MB).');
        redirect('/admin/products/' . $id . '/edit');
    }

    public function imageDelete(Request $request): void
    {
        $img = $this->one('SELECT * FROM product_images WHERE id = ?', [(int) $request->param('id')]);

        if ($img) {
            if (str_starts_with($img['image_path'], 'uploads/')) {
                @unlink(config('app.base_path') . '/public/assets/' . $img['image_path']);
            }
            db()->prepare('DELETE FROM product_images WHERE id = ?')->execute([$img['id']]);
        }

        $this->back('/admin/products');
    }

    public function imagePrimary(Request $request): void
    {
        $img = $this->one('SELECT product_id FROM product_images WHERE id = ?', [(int) $request->param('id')]);

        if ($img) {
            db()->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = ?')->execute([$img['product_id']]);
            db()->prepare('UPDATE product_images SET is_primary = 1 WHERE id = ?')->execute([(int) $request->param('id')]);
        }

        $this->back('/admin/products');
    }

    /* ------------------------------------------------------ categories */

    public function categories(Request $request): string
    {
        return $this->page('categories', [
            'title' => 'Categories',
            'items' => $this->rows(
                'SELECT c.*, p.name AS parent_name, (SELECT COUNT(*) FROM category_product cp WHERE cp.category_id = c.id) AS product_count
                 FROM categories c LEFT JOIN categories p ON p.id = c.parent_id ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.sort_order, c.name'
            ),
            'parents' => $this->rows('SELECT id, name FROM categories ORDER BY name'),
        ]);
    }

    public function categorySave(Request $request): void
    {
        $name = $this->str($request, 'name', 150);

        if ($name === '') {
            flash('error', 'Category name is required.');
            redirect('/admin/categories');
        }

        $slug = slugify($this->str($request, 'slug', 180) ?: $name);
        $parent = (int) $request->input('parent_id', 0) ?: null;
        $id = (int) $request->input('id', 0);

        try {
            if ($id) {
                if ($parent === $id) {
                    $parent = null;
                }
                db()->prepare('UPDATE categories SET name=?, slug=?, parent_id=?, sort_order=?, status=? WHERE id=?')
                    ->execute([$name, $slug, $parent, (int) $request->input('sort_order', 0), $request->input('status') === 'inactive' ? 'inactive' : 'active', $id]);
            } else {
                db()->prepare("INSERT INTO categories (name, slug, parent_id, sort_order, status) VALUES (?,?,?,?,'active')")
                    ->execute([$name, $slug, $parent, (int) $request->input('sort_order', 0)]);
            }
            flash('success', 'Category saved.');
        } catch (\PDOException $e) {
            flash('error', 'That slug is already in use.');
        }

        redirect('/admin/categories');
    }

    public function categoryDelete(Request $request): void
    {
        db()->prepare('DELETE FROM categories WHERE id = ?')->execute([(int) $request->param('id')]);
        flash('success', 'Category deleted.');
        redirect('/admin/categories');
    }

    /* ----------------------------------------------------------- orders */

    public function orders(Request $request): string
    {
        $status = (string) $request->query('status', '');
        $q = trim((string) $request->query('q', ''));
        $where = [];
        $params = [];

        if (in_array($status, OrderService::STATUSES, true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }

        if ($q !== '') {
            $where[] = '(order_number LIKE ? OR email LIKE ? OR phone LIKE ? OR ship_name LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
        }

        return $this->page('orders', [
            'title' => 'Orders',
            'status' => $status,
            'q' => $q,
            'statuses' => OrderService::STATUSES,
            'items' => $this->rows('SELECT * FROM orders ' . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 200', $params),
        ]);
    }

    public function orderShow(Request $request): string
    {
        $order = $this->one('SELECT * FROM orders WHERE id = ?', [(int) $request->param('id')]);

        if (!$order) {
            abort(404);
        }

        $svc = new OrderService(db());

        return $this->page('order', [
            'title' => 'Order ' . $order['order_number'],
            'order' => $order,
            'items' => $svc->items((int) $order['id']),
            'timeline' => $svc->timeline((int) $order['id']),
            'statuses' => OrderService::STATUSES,
        ]);
    }

    public function orderStatus(Request $request): void
    {
        $id = (int) $request->param('id');

        try {
            (new OrderService(db()))->updateStatus(
                $id,
                (string) $request->input('status'),
                $this->str($request, 'courier', 80),
                $this->str($request, 'tracking_no', 80),
                $this->str($request, 'note', 255) ?: null
            );
            flash('success', 'Order updated.');
        } catch (\Throwable $e) {
            flash('error', 'Could not update the order.');
        }

        redirect('/admin/orders/' . $id);
    }

    /* ---------------------------------------------------------- coupons */

    public function coupons(Request $request): string
    {
        return $this->page('coupons', ['title' => 'Coupons', 'items' => $this->rows('SELECT * FROM coupons ORDER BY id DESC')]);
    }

    public function couponSave(Request $request): void
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $this->str($request, 'code', 40)));
        $value = (float) $request->input('value', 0);
        $type = $request->input('type') === 'flat' ? 'flat' : 'percent';

        if ($code === '' || $value <= 0 || ($type === 'percent' && $value > 100)) {
            flash('error', 'Enter a code and a valid value.');
            redirect('/admin/coupons');
        }

        $max = trim((string) $request->input('max_discount', ''));
        $limit = trim((string) $request->input('usage_limit', ''));
        $exp = trim((string) $request->input('expires_at', ''));

        try {
            db()->prepare('INSERT INTO coupons (code, type, value, min_amount, max_discount, prepaid_only, usage_limit, expires_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$code, $type, $value, (float) $request->input('min_amount', 0), $max !== '' ? (float) $max : null,
                    $request->input('prepaid_only') ? 1 : 0, $limit !== '' ? (int) $limit : null, $exp !== '' ? $exp . ' 23:59:59' : null]);
            flash('success', 'Coupon created.');
        } catch (\PDOException $e) {
            flash('error', 'A coupon with that code already exists.');
        }

        redirect('/admin/coupons');
    }

    public function couponToggle(Request $request): void
    {
        db()->prepare('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?')->execute([(int) $request->param('id')]);
        redirect('/admin/coupons');
    }

    public function couponDelete(Request $request): void
    {
        db()->prepare('DELETE FROM coupons WHERE id = ?')->execute([(int) $request->param('id')]);
        redirect('/admin/coupons');
    }

    /* ---------------------------------------------------------- reviews */

    public function reviews(Request $request): string
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true) ? $request->query('status') : 'pending';

        return $this->page('reviews', [
            'title' => 'Reviews',
            'status' => $status,
            'items' => $this->rows(
                'SELECT r.*, p.name AS product_name FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.status = ? ORDER BY r.id DESC LIMIT 100',
                [$status]
            ),
        ]);
    }

    public function reviewAction(Request $request): void
    {
        $id = (int) $request->param('id');
        $action = (string) $request->param('action');
        $review = $this->one('SELECT product_id FROM reviews WHERE id = ?', [$id]);

        if ($review) {
            if ($action === 'delete') {
                db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
            } elseif (in_array($action, ['approve', 'reject'], true)) {
                db()->prepare('UPDATE reviews SET status = ? WHERE id = ?')->execute([$action === 'approve' ? 'approved' : 'rejected', $id]);
            }

            (new Catalog(db()))->refreshRating((int) $review['product_id']);
        }

        $this->back('/admin/reviews');
    }

    /* -------------------------------------------------------- exchanges */

    public function exchanges(Request $request): string
    {
        return $this->page('exchanges', ['title' => 'Exchange requests', 'items' => $this->rows('SELECT * FROM exchange_requests ORDER BY id DESC LIMIT 200')]);
    }

    public function exchangeUpdate(Request $request): void
    {
        $status = in_array($request->input('status'), ['pending', 'approved', 'rejected', 'completed'], true) ? $request->input('status') : 'pending';
        db()->prepare('UPDATE exchange_requests SET status = ?, admin_note = ? WHERE id = ?')
            ->execute([$status, $this->str($request, 'admin_note', 255) ?: null, (int) $request->param('id')]);

        flash('success', 'Exchange request updated.');
        redirect('/admin/exchanges');
    }

    /* ------------------------------------------- messages / subscribers */

    public function messages(Request $request): string
    {
        return $this->page('messages', ['title' => 'Messages', 'items' => $this->rows('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 200')]);
    }

    public function messageRead(Request $request): void
    {
        db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = ?')->execute([(int) $request->param('id')]);
        redirect('/admin/messages');
    }

    public function subscribers(Request $request): string
    {
        if ($request->query('export') === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="subscribers.csv"');
            echo "email,subscribed_at\n";
            foreach ($this->rows('SELECT email, created_at FROM newsletter_subscribers ORDER BY id DESC') as $r) {
                echo $r['email'] . ',' . $r['created_at'] . "\n";
            }
            exit;
        }

        return $this->page('subscribers', ['title' => 'Subscribers', 'items' => $this->rows('SELECT * FROM newsletter_subscribers ORDER BY id DESC LIMIT 500')]);
    }

    /* -------------------------------------------------------------- CMS */

    public function pages(Request $request): string
    {
        return $this->page('pages', ['title' => 'Pages', 'items' => $this->rows('SELECT id, slug, title, updated_at FROM pages ORDER BY title')]);
    }

    public function pageForm(Request $request): string
    {
        $p = $this->one('SELECT * FROM pages WHERE id = ?', [(int) $request->param('id')]);

        if (!$p) {
            abort(404);
        }

        return $this->page('page-form', ['title' => 'Edit page', 'p' => $p]);
    }

    public function pageSave(Request $request): void
    {
        db()->prepare('UPDATE pages SET title = ?, body = ? WHERE id = ?')
            ->execute([$this->str($request, 'title', 200), html_clean((string) $request->input('body', '')), (int) $request->param('id')]);

        flash('success', 'Page saved.');
        redirect('/admin/pages');
    }

    public function blog(Request $request): string
    {
        return $this->page('blog', ['title' => 'Blog', 'items' => $this->rows('SELECT id, slug, title, is_published, created_at FROM blog_posts ORDER BY id DESC')]);
    }

    public function postForm(Request $request): string
    {
        $id = (int) $request->param('id', 0);
        $post = $id ? $this->one('SELECT * FROM blog_posts WHERE id = ?', [$id]) : null;

        if ($id && !$post) {
            abort(404);
        }

        return $this->page('post-form', ['title' => $post ? 'Edit post' : 'New post', 'post' => $post]);
    }

    public function postSave(Request $request): void
    {
        $id = (int) $request->param('id', 0);
        $title = $this->str($request, 'title', 220);

        if ($title === '') {
            flash('error', 'Title is required.');
            $this->back('/admin/blog');
        }

        $slug = slugify($this->str($request, 'slug', 180) ?: $title);
        $vals = [$title, $slug, $this->str($request, 'excerpt', 400) ?: null, html_clean((string) $request->input('body', '')), $request->input('is_published') ? 1 : 0];

        try {
            if ($id) {
                db()->prepare('UPDATE blog_posts SET title=?, slug=?, excerpt=?, body=?, is_published=? WHERE id=?')->execute([...$vals, $id]);
            } else {
                db()->prepare('INSERT INTO blog_posts (title, slug, excerpt, body, is_published) VALUES (?,?,?,?,?)')->execute($vals);
            }
            flash('success', 'Post saved.');
        } catch (\PDOException $e) {
            flash('error', 'That slug is already used by another post.');
        }

        redirect('/admin/blog');
    }

    public function postDelete(Request $request): void
    {
        db()->prepare('DELETE FROM blog_posts WHERE id = ?')->execute([(int) $request->param('id')]);
        redirect('/admin/blog');
    }
}
