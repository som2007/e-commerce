<?php
$steps = ['placed' => 'Order placed', 'confirmed' => 'Confirmed', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
?>
<section class="container narrow section-sm">
    <h1 class="page-title">Track your order</h1>
    <form class="form panel" method="get" action="<?= e(url('track-order')) ?>">
        <label>Order number <input type="text" name="order" value="<?= e($number) ?>" placeholder="e.g. FH2410181234" required></label>
        <label>Email or phone used at checkout <input type="text" name="contact" value="<?= e($contact) ?>" required></label>
        <button class="btn btn-dark" type="submit">Track</button>
    </form>

    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($order): ?>
        <div class="panel">
            <div class="row"><strong>Order <?= e($order['order_number']) ?></strong><span class="status status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></div>
            <p class="muted small">Placed on <?= e(time_ago($order['created_at'])) ?> &middot; <?= money($order['total']) ?> &middot; <?= $order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Online (' . e($order['payment_status']) . ')' ?></p>

            <?php if ($order['status'] !== 'cancelled'): ?>
                <?php $reached = array_search($order['status'], array_keys($steps), true); ?>
                <ol class="stepper">
                    <?php $n = 0; foreach ($steps as $key => $label): ?>
                        <li class="<?= $n <= $reached ? 'done' : '' ?>"><span class="dot"></span><span><?= e($label) ?></span></li>
                    <?php $n++; endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php if ($order['tracking_no']): ?>
                <p><strong>Courier:</strong> <?= e($order['courier'] ?: 'Courier partner') ?> &middot; <strong>Tracking no:</strong> <?= e($order['tracking_no']) ?></p>
            <?php endif; ?>

            <h4>Updates</h4>
            <ul class="timeline">
                <?php foreach (array_reverse($timeline) as $t): ?>
                    <li><strong><?= e(ucfirst($t['status'])) ?></strong> <span class="muted small"><?= e(date('d M Y, h:i A', strtotime($t['created_at']))) ?></span>
                        <?php if ($t['note']): ?><br><span class="muted"><?= e($t['note']) ?></span><?php endif; ?></li>
                <?php endforeach; ?>
            </ul>

            <h4>Items</h4>
            <?php foreach ($items as $i): ?>
                <div class="mini-line">
                    <div class="mini-img"><img src="<?= e(product_image($i['image'])) ?>" alt="" width="56" height="70"><span class="mini-qty"><?= (int) $i['qty'] ?></span></div>
                    <div><strong><?= e($i['name']) ?></strong><p class="muted small">Size <?= e($i['size']) ?></p></div>
                    <span><?= money($i['price'] * $i['qty']) ?></span>
                </div>
            <?php endforeach; ?>

            <?php if ($order['status'] === 'delivered'): ?>
                <p><a class="btn btn-outline" href="<?= e(url('exchange-request')) ?>">Need a different size? Raise an exchange</a></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
