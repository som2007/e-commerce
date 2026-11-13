<section class="container narrow section-sm">
    <div class="success-head center">
        <div class="tick">&#10003;</div>
        <h1>Thank you, <?= e(explode(' ', $order['ship_name'])[0]) ?>!</h1>
        <p class="muted">Your order <strong><?= e($order['order_number']) ?></strong> is confirmed. We have sent the details to <?= e($order['email']) ?>.</p>
    </div>

    <div class="panel">
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
        <div class="row total"><span>Total <?= $order['payment_method'] === 'cod' ? '(pay on delivery)' : '(paid)' ?></span><strong><?= money($order['total']) ?></strong></div>
    </div>

    <div class="panel">
        <h3>Delivering to</h3>
        <p><?= e($order['ship_name']) ?><br><?= e($order['ship_line1']) ?><?= $order['ship_line2'] ? ', ' . e($order['ship_line2']) : '' ?><br>
            <?= e($order['ship_city']) ?>, <?= e($order['ship_state']) ?> - <?= e($order['ship_pincode']) ?><br>Mobile: <?= e($order['phone']) ?></p>
        <p class="muted small">Estimated delivery in 3-6 working days.</p>
    </div>

    <p class="center">
        <a class="btn btn-dark" href="<?= e(url('track-order?order=' . $order['order_number'] . '&contact=' . urlencode($order['email']))) ?>">Track order</a>
        <a class="btn btn-outline" href="<?= e(url('collections/all')) ?>">Continue shopping</a>
    </p>
</section>
