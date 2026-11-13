<section class="container section-sm">
    <h1 class="page-title">My orders</h1>
    <div class="acct">
        <?php partial('../frontend/account/_nav', ['active' => 'orders']); ?>
        <div class="panel">
            <?php if (!$orders): ?><p class="muted">No orders yet. <a href="<?= e(url('collections/all')) ?>">Start shopping</a></p><?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <a class="order-row" href="<?= e(url('account/orders/' . $o['order_number'])) ?>">
                    <strong><?= e($o['order_number']) ?></strong><span class="muted"><?= e(time_ago($o['created_at'])) ?></span>
                    <span class="status status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span><span><?= money($o['total']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
