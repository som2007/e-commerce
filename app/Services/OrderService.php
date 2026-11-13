<?php

namespace Rk\Threadwell\Services;

use PDO;
use RuntimeException;

class OrderService
{
    public const STATUSES = ['placed', 'confirmed', 'packed', 'shipped', 'delivered', 'cancelled'];

    public function __construct(private PDO $db)
    {
    }

    /* ---------------------------------------------------------- coupons */

    public function findCoupon(string $code): ?array
    {
        $st = $this->db->prepare('SELECT * FROM coupons WHERE UPPER(code) = ? AND is_active = 1');
        $st->execute([strtoupper(trim($code))]);

        return $st->fetch() ?: null;
    }

    /** @return array{0: float, 1: ?string} [discount, errorMessage] */
    public function couponDiscount(?string $code, float $subtotal, string $method): array
    {
        if (!$code) {
            return [0.0, null];
        }

        $c = $this->findCoupon($code);

        if (!$c) {
            return [0.0, 'This coupon code is not valid.'];
        }

        if ($c['expires_at'] && strtotime($c['expires_at']) < time()) {
            return [0.0, 'This coupon has expired.'];
        }

        if ($c['usage_limit'] !== null && (int) $c['used_count'] >= (int) $c['usage_limit']) {
            return [0.0, 'This coupon has reached its usage limit.'];
        }

        if ($subtotal < (float) $c['min_amount']) {
            return [0.0, 'Add items worth ' . money($c['min_amount'], false) . ' or more to use this coupon.'];
        }

        if ((int) $c['prepaid_only'] === 1 && $method !== 'online') {
            return [0.0, 'This coupon works only with online payment (UPI / card / net banking).'];
        }

        $d = $c['type'] === 'percent' ? $subtotal * ((float) $c['value'] / 100) : (float) $c['value'];

        if ($c['max_discount'] !== null) {
            $d = min($d, (float) $c['max_discount']);
        }

        return [round(min($d, $subtotal), 2), null];
    }

    /* ----------------------------------------------------------- totals */

    public function totals(float $subtotal, string $method, ?string $coupon = null): array
    {
        $prepaid = 0.0;

        if ($method === 'online') {
            $prepaid = round($subtotal * (float) config('store.prepaid_discount_percent') / 100, 2);
        }

        [$couponDiscount, $couponError] = $this->couponDiscount($coupon, $subtotal, $method);

        $discount = min($subtotal, $prepaid + $couponDiscount);
        $afterDiscount = $subtotal - $discount;

        $shipping = $subtotal >= (float) config('store.free_shipping_over') || $subtotal <= 0
            ? 0.0
            : (float) config('store.shipping_fee');

        $codFee = $method === 'cod' && $subtotal > 0 ? (float) config('store.cod_fee') : 0.0;

        return [
            'subtotal' => $subtotal,
            'prepaid_discount' => $prepaid,
            'coupon_discount' => $couponDiscount,
            'coupon_error' => $couponError,
            'coupon_applied' => $coupon && !$couponError && $couponDiscount > 0 ? strtoupper(trim($coupon)) : null,
            'discount' => $discount,
            'shipping' => $shipping,
            'cod_fee' => $codFee,
            'total' => round($afterDiscount + $shipping + $codFee, 2),
        ];
    }

    /* ------------------------------------------------------------ place */

    /**
     * Creates the order, decrements stock, all inside one transaction.
     *
     * @param array $cart   CartService::summary()
     * @param array $data   customer + shipping fields
     */
    public function place(array $cart, array $data, string $method, ?string $coupon, ?int $userId): array
    {
        if (empty($cart['lines'])) {
            throw new RuntimeException('Your cart is empty.');
        }

        $totals = $this->totals((float) $cart['subtotal'], $method, $coupon);
        $number = $this->newOrderNumber();

        $this->db->beginTransaction();

        try {
            $stock = $this->db->prepare('SELECT stock FROM product_variants WHERE id = ?');
            $dec = $this->db->prepare(
                'UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?'
            );

            foreach ($cart['lines'] as $l) {
                $dec->execute([$l['qty'], $l['variant_id'], $l['qty']]);

                if ($dec->rowCount() !== 1) {
                    throw new RuntimeException(
                        "Sorry, {$l['name']} (size {$l['size']}) just went out of stock."
                    );
                }
            }

            $this->db->prepare(
                'INSERT INTO orders (order_number, user_id, email, phone, ship_name, ship_line1, ship_line2, ship_city,
                    ship_state, ship_pincode, subtotal, discount, shipping, cod_fee, total, coupon_code,
                    payment_method, payment_status, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $number, $userId, $data['email'], $data['phone'], $data['name'], $data['line1'],
                $data['line2'] ?: null, $data['city'], $data['state'], $data['pincode'],
                $totals['subtotal'], $totals['discount'], $totals['shipping'], $totals['cod_fee'], $totals['total'],
                $totals['coupon_applied'], $method, 'pending', 'placed', $data['notes'] ?? null,
            ]);

            $orderId = (int) $this->db->lastInsertId();

            $item = $this->db->prepare(
                'INSERT INTO order_items (order_id, product_id, variant_id, name, color, size, image, price, qty)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($cart['lines'] as $l) {
                $item->execute([
                    $orderId, $l['product_id'], $l['variant_id'], $l['name'], $l['color'], $l['size'],
                    $l['image'], $l['unit'], $l['qty'],
                ]);
            }

            if ($totals['coupon_applied']) {
                $this->db->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE UPPER(code) = ?')
                    ->execute([$totals['coupon_applied']]);
            }

            $this->history($orderId, 'placed', $method === 'cod' ? 'Order placed (cash on delivery)' : 'Order placed, awaiting payment');

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->find($number);
    }

    private function newOrderNumber(): string
    {
        do {
            $n = 'FH' . date('ymd') . random_int(1000, 9999);
            $st = $this->db->prepare('SELECT 1 FROM orders WHERE order_number = ?');
            $st->execute([$n]);
        } while ($st->fetchColumn());

        return $n;
    }

    /* ---------------------------------------------------------- queries */

    public function find(string $number): ?array
    {
        $st = $this->db->prepare('SELECT * FROM orders WHERE order_number = ?');
        $st->execute([$number]);

        return $st->fetch() ?: null;
    }

    public function items(int $orderId): array
    {
        $st = $this->db->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $st->execute([$orderId]);

        return $st->fetchAll();
    }

    public function timeline(int $orderId): array
    {
        $st = $this->db->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id');
        $st->execute([$orderId]);

        return $st->fetchAll();
    }

    public function forUser(int $userId): array
    {
        $st = $this->db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
        $st->execute([$userId]);

        return $st->fetchAll();
    }

    public function history(int $orderId, string $status, ?string $note = null): void
    {
        $this->db->prepare('INSERT INTO order_status_history (order_id, status, note) VALUES (?, ?, ?)')
            ->execute([$orderId, $status, $note]);
    }

    public function markPaid(string $number, string $ref): void
    {
        $order = $this->find($number);

        if (!$order || $order['payment_status'] === 'paid') {
            return;
        }

        $this->db->prepare("UPDATE orders SET payment_status = 'paid', payment_ref = ?, status = 'confirmed' WHERE id = ?")
            ->execute([$ref, $order['id']]);

        $this->history((int) $order['id'], 'confirmed', 'Payment received (' . $ref . ')');
    }

    public function markPaymentFailed(string $number): void
    {
        $order = $this->find($number);

        if ($order && $order['payment_status'] === 'pending') {
            $this->db->prepare("UPDATE orders SET payment_status = 'failed' WHERE id = ?")->execute([$order['id']]);
            $this->history((int) $order['id'], 'placed', 'Payment failed - customer can retry');
        }
    }

    /** Return stock of a cancelled order. */
    public function cancel(string $number, string $note = 'Order cancelled'): void
    {
        $order = $this->find($number);

        if (!$order || in_array($order['status'], ['cancelled', 'delivered'], true)) {
            return;
        }

        $this->db->beginTransaction();

        foreach ($this->items((int) $order['id']) as $i) {
            if ($i['variant_id']) {
                $this->db->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?')
                    ->execute([$i['qty'], $i['variant_id']]);
            }
        }

        $this->db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order['id']]);
        $this->history((int) $order['id'], 'cancelled', $note);
        $this->db->commit();
    }

    public function updateStatus(int $orderId, string $status, ?string $courier, ?string $tracking, ?string $note): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Invalid status');
        }

        $st = $this->db->prepare('SELECT order_number, status, payment_method FROM orders WHERE id = ?');
        $st->execute([$orderId]);
        $o = $st->fetch();

        if (!$o) {
            return;
        }

        if ($status === 'cancelled') {
            $this->cancel($o['order_number'], $note ?: 'Order cancelled by store');
        } else {
            $this->db->prepare('UPDATE orders SET status = ?, courier = COALESCE(?, courier), tracking_no = COALESCE(?, tracking_no) WHERE id = ?')
                ->execute([$status, $courier ?: null, $tracking ?: null, $orderId]);

            if ($status === 'delivered' && $o['payment_method'] === 'cod') {
                $this->db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?")->execute([$orderId]);
            }

            if ($status !== $o['status']) {
                $this->history($orderId, $status, $note);
            }
        }
    }
}
