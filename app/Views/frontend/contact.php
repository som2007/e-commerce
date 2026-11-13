<section class="container section-sm">
    <h1 class="page-title">Contact us</h1>
    <div class="contact-grid">
        <form class="form panel" method="post" action="<?= e(url('pages/contact')) ?>">
            <?= csrf_field() ?>
            <label>Name <input type="text" name="name" value="<?= e(old('name', auth_user()['first_name'] ?? '')) ?>" required></label>
            <div class="form-row">
                <label>Email <input type="email" name="email" value="<?= e(old('email', auth_user()['email'] ?? '')) ?>" required></label>
                <label>Phone (optional) <input type="tel" name="phone" value="<?= e(old('phone')) ?>"></label>
            </div>
            <label>How can we help? <textarea name="message" rows="5" required maxlength="3000"><?= e(old('message')) ?></textarea></label>
            <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <button class="btn btn-dark" type="submit">Send message</button>
        </form>
        <div class="panel">
            <h3>Reach us</h3>
            <p><strong>Email</strong><br><a href="mailto:<?= e(config('store.email')) ?>"><?= e(config('store.email')) ?></a></p>
            <p><strong>Phone</strong><br><?= e(config('store.phone')) ?></p>
            <p><strong>Address</strong><br><?= e(config('store.address')) ?></p>
            <p class="muted small">Support hours: Mon-Sat, 10am-7pm IST.</p>
        </div>
    </div>
</section>
