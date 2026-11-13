<?php $brand = config('app.name'); ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="f-about">
            <a class="logo logo-light" href="<?= e(url('/')) ?>"><?= e($brand) ?></a>
            <p><?= e($brand) ?> is a clothing label making everyday wear look sharp, feel comfortable and stay affordable.
                <?= e(config('store.address')) ?></p>
            <p class="muted-light"><?= e(config('store.email')) ?><br><?= e(config('store.phone')) ?></p>
        </div>

        <div>
            <h4>Categories</h4>
            <a href="<?= e(url('collections/formal-shirts-for-men')) ?>">Shirts</a>
            <a href="<?= e(url('collections/men-tshirts')) ?>">T-Shirts &amp; Polos</a>
            <a href="<?= e(url('collections/men-bottomwear')) ?>">Jeans &amp; Trousers</a>
            <a href="<?= e(url('collections/men-winter')) ?>">Hoodies &amp; Jackets</a>
            <a href="<?= e(url('collections/men-ethnic')) ?>">Kurtas</a>
            <a href="<?= e(url('collections/women')) ?>">Women</a>
        </div>

        <div>
            <h4>Quick links</h4>
            <a href="<?= e(url('pages/about-us')) ?>">About Us</a>
            <a href="<?= e(url('pages/contact')) ?>">Contact</a>
            <a href="<?= e(url('policies/privacy-policy')) ?>">Privacy Policy</a>
            <a href="<?= e(url('pages/return-and-exchange-policy')) ?>">Return and Exchange Policy</a>
            <a href="<?= e(url('exchange-request')) ?>">Raise Exchange Request</a>
            <a href="<?= e(url('policies/terms-of-service')) ?>">Terms of Service</a>
            <a href="<?= e(url('pages/shipping-policy')) ?>">Shipping Policy</a>
            <a href="<?= e(url('blogs/news')) ?>">Blog</a>
            <a href="<?= e(url('track-order')) ?>">Track Your Order</a>
        </div>

        <div>
            <h4>Stay in touch</h4>
            <p class="muted-light">Get new arrivals, offers and style tips in your inbox.</p>
            <form class="newsletter" id="newsletterForm">
                <input type="email" name="email" placeholder="Your email" required aria-label="Email">
                <button class="btn btn-light" type="submit">Subscribe</button>
            </form>
            <p class="small muted-light" id="newsletterMsg"></p>
        </div>
    </div>

    <div class="container trust-row">
        <div><strong>Fast shipping</strong><span>Delivery in 3-6 days</span></div>
        <div><strong>Secure payments</strong><span>UPI, cards, net banking, COD</span></div>
        <div><strong>Easy exchange</strong><span>7-day exchange policy</span></div>
        <div><strong>Top-notch support</strong><span>We reply within a working day</span></div>
    </div>

    <div class="container footer-bottom">
        <span>&copy; <?= date('Y') ?> <?= e($brand) ?>. All rights reserved.</span>
        <span class="pay-icons">UPI &middot; Visa &middot; Mastercard &middot; RuPay &middot; Net Banking &middot; COD</span>
    </div>
</footer>
