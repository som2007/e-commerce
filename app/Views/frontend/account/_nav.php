<nav class="acct-nav" aria-label="Account">
    <a href="<?= e(url('account')) ?>" class="<?= ($active ?? '') === 'home' ? 'active' : '' ?>">Overview</a>
    <a href="<?= e(url('account/orders')) ?>" class="<?= ($active ?? '') === 'orders' ? 'active' : '' ?>">Orders</a>
    <a href="<?= e(url('account/addresses')) ?>" class="<?= ($active ?? '') === 'addresses' ? 'active' : '' ?>">Addresses</a>
    <a href="#" data-logout>Log out</a>
</nav>
