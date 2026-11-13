<?php
use Rk\Threadwell\Models\Catalog;

$menu = (new Catalog(db()))->menuTree();
$user = auth_user();
$count = cart_count();
$brand = config('app.name');
$pct = (int) config('store.prepaid_discount_percent');
?>
<div class="topbar">
    <div class="container topbar-inner">
        <span class="topbar-offer"><?= $pct ?>% OFF on Card, UPI &amp; Net Banking</span>
        <span class="topbar-contact">
            <a href="mailto:<?= e(config('store.email')) ?>"><?= e(config('store.email')) ?></a>
            <span class="sep">|</span>
            <a href="tel:<?= e(preg_replace('/\s+/', '', config('store.phone'))) ?>"><?= e(config('store.phone')) ?></a>
        </span>
    </div>
</div>

<header class="site-header" id="siteHeader">
    <div class="container header-row">
        <button class="icon-btn menu-toggle" type="button" aria-label="Open menu" data-open-menu>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>

        <a class="logo" href="<?= e(url('/')) ?>"><?= e($brand) ?></a>

        <nav class="main-nav" aria-label="Main">
            <?php foreach ($menu as $top): ?>
                <?php $mega = $top['slug'] === 'men'; ?>
                <div class="nav-item <?= $mega ? 'has-mega' : 'has-drop' ?>">
                    <a class="nav-link" href="<?= e(url('collections/' . ($top['slug'] === 'trending' ? 'new-arrivals' : ($top['slug'] === 'men' ? 'formal-shirts-for-men' : $top['slug'])))) ?>">
                        <?= e(strtoupper($top['name'])) ?>
                        <?php if ($top['children']): ?><svg width="10" height="10" viewBox="0 0 10 10"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg><?php endif; ?>
                    </a>
                    <?php if ($top['children']): ?>
                        <?php if ($mega): ?>
                            <div class="mega">
                                <div class="container mega-grid">
                                    <?php foreach ($top['children'] as $group): ?>
                                        <div class="mega-col">
                                            <a class="mega-title" href="<?= e(url('collections/' . $group['slug'])) ?>"><?= e($group['name']) ?></a>
                                            <?php foreach (array_slice($group['children'], 0, 6) as $leaf): ?>
                                                <a href="<?= e(url('collections/' . $leaf['slug'])) ?>"><?= e($leaf['name']) ?></a>
                                            <?php endforeach; ?>
                                            <?php if (count($group['children']) > 6): ?>
                                                <a class="mega-all" href="<?= e(url('collections/' . $group['slug'])) ?>">View all &rarr;</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="drop">
                                <?php foreach ($top['children'] as $child): ?>
                                    <a href="<?= e(url('collections/' . $child['slug'])) ?>"><?= e($child['name']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="nav-item has-drop">
                <a class="nav-link" href="<?= e(url('pages/about-us')) ?>">ABOUT THE BRAND
                    <svg width="10" height="10" viewBox="0 0 10 10"><path d="M1 3l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></a>
                <div class="drop">
                    <a href="<?= e(url('pages/about-us')) ?>">About Us</a>
                    <a href="<?= e(url('pages/why-choose-us')) ?>">Why Choose Us</a>
                    <a href="<?= e(url('pages/our-story')) ?>">Our Story</a>
                    <a href="<?= e(url('pages/contact')) ?>">Contact</a>
                </div>
            </div>
            <a class="nav-link" href="<?= e(url('track-order')) ?>">TRACK YOUR ORDER</a>
            <a class="nav-link" href="<?= e(url('pages/awards-recognition')) ?>">AWARDS</a>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" type="button" aria-label="Search" data-open-search>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
            </button>

            <?php if ($user): ?>
                <div class="nav-item has-drop acct">
                    <a class="icon-btn" href="<?= e(url($user['role'] === 'customer' ? 'account' : 'admin')) ?>" aria-label="My account">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                    </a>
                    <div class="drop drop-right">
                        <span class="drop-hi">Hi, <?= e($user['first_name']) ?></span>
                        <?php if (in_array($user['role'], ['admin', 'staff'], true)): ?><a href="<?= e(url('admin')) ?>">Admin panel</a><?php endif; ?>
                        <a href="<?= e(url('account')) ?>">My account</a>
                        <a href="<?= e(url('account/orders')) ?>">My orders</a>
                        <a href="<?= e(url('account/addresses')) ?>">Addresses</a>
                        <a href="#" data-logout>Log out</a>
                    </div>
                </div>
            <?php else: ?>
                <a class="auth-link" href="<?= e(url('login')) ?>">Log in</a>
                <a class="auth-link auth-link-cta" href="<?= e(url('register')) ?>">Register</a>
                <?php endif; ?>

            <button class="icon-btn cart-btn" type="button" aria-label="Cart" data-open-cart>
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 8h14l-1 12H6L5 8z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg>
                <span class="cart-count" data-cart-count <?= $count ? '' : 'hidden' ?>><?= (int) $count ?></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile menu -->
<div class="drawer-backdrop" data-close-all hidden></div>
<aside class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <div class="drawer-head"><strong><?= e($brand) ?></strong>
        <button class="icon-btn" type="button" data-close-all aria-label="Close">&times;</button></div>
    <div class="mobile-links">
        <?php foreach ($menu as $top): ?>
            <details>
                <summary><?= e($top['name']) ?></summary>
                <?php foreach ($top['children'] as $group): ?>
                    <?php if ($group['children']): ?>
                        <details class="sub">
                            <summary><?= e($group['name']) ?></summary>
                            <?php foreach ($group['children'] as $leaf): ?>
                                <a href="<?= e(url('collections/' . $leaf['slug'])) ?>"><?= e($leaf['name']) ?></a>
                            <?php endforeach; ?>
                        </details>
                    <?php else: ?>
                        <a href="<?= e(url('collections/' . $group['slug'])) ?>"><?= e($group['name']) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
        <a class="plain" href="<?= e(url('pages/about-us')) ?>">About the Brand</a>
        <a class="plain" href="<?= e(url('track-order')) ?>">Track Your Order</a>
        <a class="plain" href="<?= e(url('pages/awards-recognition')) ?>">Awards</a>
        <?php if ($user): ?>
            <a class="plain" href="<?= e(url('account')) ?>">My Account</a>
        <?php else: ?>
            <a class="plain" href="<?= e(url('login')) ?>">Log in</a>
            <a class="plain" href="<?= e(url('register')) ?>">Register</a>
        <?php endif; ?>
    </div>
</aside>

<!-- Search overlay -->
<div class="search-overlay" id="searchOverlay" hidden>
    <div class="container search-box">
        <form action="<?= e(url('search')) ?>" method="get" autocomplete="off">
            <input type="search" name="q" id="searchInput" placeholder="Search jeans, hoodies, shirts, colours..." aria-label="Search">
            <button type="submit" class="btn btn-dark">Search</button>
            <button type="button" class="icon-btn" data-close-all aria-label="Close search">&times;</button>
        </form>
        <div class="search-results" id="searchResults"></div>
    </div>
</div>

<!-- Cart drawer -->
<aside class="cart-drawer" id="cartDrawer" aria-hidden="true" aria-label="Cart">
    <div class="drawer-head"><strong>Your cart</strong>
        <button class="icon-btn" type="button" data-close-all aria-label="Close cart">&times;</button></div>
    <div class="drawer-body" id="cartBody"><p class="muted pad">Loading...</p></div>
    <div class="drawer-foot" id="cartFoot" hidden>
        <div class="free-ship" id="freeShip"></div>
        <div class="row"><span>Subtotal</span><strong id="cartSubtotal"></strong></div>
        <p class="muted small">Shipping &amp; discounts calculated at checkout.</p>
        <a class="btn btn-dark btn-block" href="<?= e(url('checkout')) ?>">Checkout</a>
        <a class="btn btn-light btn-block" href="<?= e(url('cart')) ?>">View cart</a>
    </div>
</aside>
