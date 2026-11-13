<section class="container section-sm">
    <h1 class="page-title">My account</h1>
    <div class="acct">
        <?php partial('../frontend/account/_nav', ['active' => 'home']); ?>
        <div>
            <div class="panel">
                <h3>Profile</h3>
                <form class="form" method="post" action="<?= e(url('account/profile')) ?>">
                    <?= csrf_field() ?>
                    <div class="form-row">
                        <label>First name <input type="text" name="first_name" value="<?= e($user['first_name']) ?>" required></label>
                        <label>Last name <input type="text" name="last_name" value="<?= e($user['last_name']) ?>"></label>
                    </div>
                    <div class="form-row">
                        <label>Email <input type="email" value="<?= e($user['email']) ?>" disabled></label>
                        <label>Mobile <input type="tel" name="phone" value="<?= e($user['phone']) ?>"></label>
                    </div>
                    <button class="btn btn-dark" type="submit">Save changes</button>
                </form>
            </div>

            <div class="panel">
                <div class="row"><h3>Recent orders</h3><a class="link-arrow" href="<?= e(url('account/orders')) ?>">All orders (<?= (int) $orderCount ?>) &rarr;</a></div>
                <?php if (!$orders): ?>
                    <p class="muted">You have not placed any orders yet. <a href="<?= e(url('collections/all')) ?>">Start shopping</a></p>
                <?php endif; ?>
                <?php foreach ($orders as $o): ?>
                    <a class="order-row" href="<?= e(url('account/orders/' . $o['order_number'])) ?>">
                        <strong><?= e($o['order_number']) ?></strong><span class="muted"><?= e(time_ago($o['created_at'])) ?></span>
                        <span class="status status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span><span><?= money($o['total']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
