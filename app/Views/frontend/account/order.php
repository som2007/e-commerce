<section class="container section-sm">
    <h1 class="page-title">Order <?= e($order['order_number']) ?></h1>
    <div class="acct">
        <?php partial('../frontend/account/_nav', ['active' => 'orders']); ?>
        <div>
            <div class="panel">
                <div class="row"><span class="status status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
                    <span class="muted">Placed <?= e(time_ago($order['created_at'])) ?></span></div>
                <?php if ($order['tracking_no']): ?>
                    <p><strong>Courier:</strong> <?= e($order['courier'] ?: 'Courier partner') ?> &middot; <strong>Tracking no:</strong> <?= e($order['tracking_no']) ?></p>
                <?php endif; ?>
                <?php foreach ($items as $i): ?>
                    <div class="mini-line">
                        <div class="mini-img"><img src="<?= e(product_image($i['image'])) ?>" alt="" width="56" height="70"><span class="mini-qty"><?= (int) $i['qty'] ?></span></div>
                        <div><strong><?= e($i['name']) ?></strong><p class="muted small">Size <?= e($i['size']) ?></p></div>
                        <span><?= money($i['price'] * $i['qty']) ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="row"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
                <?php if ($order['discount'] > 0): ?><div class="row save"><span>Discount</span><span>- <?= money($order['discount']) ?></span></div><?php endif; ?>
                <div class="row"><span>Shipping</span><span><?= $order['shipping'] > 0 ? money($order['shipping']) : 'Free' ?></span></div>
                <?php if ($order['cod_fee'] > 0): ?><div class="row"><span>COD fee</span><span><?= money($order['cod_fee']) ?></span></div><?php endif; ?>
                <div class="row total"><span>Total</span><strong><?= money($order['total']) ?></strong></div>
                <p class="muted small">Payment: <?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Online' ?> (<?= e($order['payment_status']) ?>)</p>
            </div>

            <div class="panel">
                <h3>Shipping address</h3>
                <p><?= e($order['ship_name']) ?><br><?= e($order['ship_line1']) ?><?= $order['ship_line2'] ? ', ' . e($order['ship_line2']) : '' ?><br>
                    <?= e($order['ship_city']) ?>, <?= e($order['ship_state']) ?> - <?= e($order['ship_pincode']) ?><br><?= e($order['phone']) ?></p>
            </div>

            <div class="panel">
                <h3>Updates</h3>
                <ul class="timeline">
                    <?php foreach (array_reverse($timeline) as $t): ?>
                        <li><strong><?= e(ucfirst($t['status'])) ?></strong> <span class="muted small"><?= e(date('d M Y, h:i A', strtotime($t['created_at']))) ?></span>
                            <?php if ($t['note']): ?><br><span class="muted"><?= e($t['note']) ?></span><?php endif; ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (in_array($order['status'], ['placed', 'confirmed'], true)): ?>
                    <form method="post" action="<?= e(url('account/orders/' . $order['order_number'] . '/cancel')) ?>" data-confirm="Cancel this order?">
                        <?= csrf_field() ?><button class="btn btn-outline" type="submit">Cancel order</button>
                    </form>
                <?php elseif ($order['status'] === 'delivered'): ?>
                    <a class="btn btn-outline" href="<?= e(url('exchange-request')) ?>">Raise exchange request</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
