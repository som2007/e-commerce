<?php

namespace Rk\Threadwell\Services;

use PDO;

/**
 * Session based cart: [variant_id => qty]. Prices always come fresh from the DB.
 */
class CartService
{
    private const KEY = 'cart';
    private const MAX_QTY = 10;

    public function __construct(private PDO $db)
    {
    }

    public function raw(): array
    {
        $cart = session()->get(self::KEY, []);

        return is_array($cart) ? $cart : [];
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function add(int $variantId, int $qty = 1): array
    {
        $variant = $this->variant($variantId);

        if (!$variant) {
            return [false, 'This size is no longer available.'];
        }

        $cart = $this->raw();
        $current = $cart[$variantId] ?? 0;
        $target = min($current + max(1, $qty), self::MAX_QTY, (int) $variant['stock']);

        if ((int) $variant['stock'] < 1) {
            return [false, 'Sorry, this size is sold out.'];
        }

        $cart[$variantId] = $target;
        session()->set(self::KEY, $cart);

        return [true, $target < $current + $qty ? 'Added the maximum available quantity.' : 'Added to cart.'];
    }

    public function setQty(int $variantId, int $qty): void
    {
        $cart = $this->raw();

        if (!isset($cart[$variantId])) {
            return;
        }

        if ($qty <= 0) {
            unset($cart[$variantId]);
        } else {
            $variant = $this->variant($variantId);
            $cart[$variantId] = $variant ? min($qty, self::MAX_QTY, (int) $variant['stock']) : 0;

            if ($cart[$variantId] < 1) {
                unset($cart[$variantId]);
            }
        }

        session()->set(self::KEY, $cart);
    }

    public function remove(int $variantId): void
    {
        $this->setQty($variantId, 0);
    }

    public function clear(): void
    {
        session()->remove(self::KEY);
    }

    private function variant(int $id): ?array
    {
        $st = $this->db->prepare(
            "SELECT v.* FROM product_variants v JOIN products p ON p.id = v.product_id
             WHERE v.id = ? AND p.status = 'active'"
        );
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    /** Fully hydrated cart lines + totals. */
    public function summary(): array
    {
        $cart = $this->raw();
        $lines = [];
        $subtotal = 0.0;
        $mrp = 0.0;
        $count = 0;

        if ($cart) {
            $in = implode(',', array_map('intval', array_keys($cart)));
            $rows = $this->db->query(
                "SELECT v.id AS variant_id, v.size, v.price, v.sale_price, v.stock, v.product_id,
                        p.name, p.slug, m.color,
                        (SELECT image_path FROM product_images i WHERE i.product_id = p.id
                            ORDER BY i.is_primary DESC, i.sort_order ASC, i.id ASC LIMIT 1) AS image
                 FROM product_variants v
                 JOIN products p ON p.id = v.product_id
                 LEFT JOIN product_meta m ON m.product_id = p.id
                 WHERE v.id IN ({$in}) AND p.status = 'active'"
            )->fetchAll();

            $fixed = [];
            foreach ($rows as $r) {
                $qty = min((int) $cart[$r['variant_id']], max(0, (int) $r['stock']));

                if ($qty < 1) {
                    continue;
                }

                $unit = (float) ($r['sale_price'] ?: $r['price']);
                $r['qty'] = $qty;
                $r['unit'] = $unit;
                $r['line_total'] = $unit * $qty;
                $lines[] = $r;
                $subtotal += $r['line_total'];
                $mrp += (float) $r['price'] * $qty;
                $count += $qty;
                $fixed[$r['variant_id']] = $qty;
            }

            // Drop vanished / sold out lines from the session
            if ($fixed !== $cart) {
                session()->set(self::KEY, $fixed);
            }
        }

        return [
            'lines' => $lines,
            'count' => $count,
            'subtotal' => $subtotal,
            'mrp' => $mrp,
            'savings' => max(0, $mrp - $subtotal),
        ];
    }
}
