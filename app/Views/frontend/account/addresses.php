<section class="container section-sm">
    <h1 class="page-title">My addresses</h1>
    <div class="acct">
        <?php partial('../frontend/account/_nav', ['active' => 'addresses']); ?>
        <div>
            <div class="addr-grid">
                <?php foreach ($addresses as $a): ?>
                    <div class="panel">
                        <?php if ($a['is_default']): ?><span class="tag">Default</span><?php endif; ?>
                        <p><strong><?= e($a['name']) ?></strong><br><?= e($a['line1']) ?><?= $a['line2'] ? ', ' . e($a['line2']) : '' ?><br>
                            <?= e($a['city']) ?>, <?= e($a['state']) ?> - <?= e($a['pincode']) ?><br><?= e($a['phone']) ?></p>
                        <div class="cta-row">
                            <?php if (!$a['is_default']): ?>
                                <form method="post" action="<?= e(url('account/addresses/' . $a['id'] . '/default')) ?>"><?= csrf_field() ?><button class="btn btn-light btn-sm">Make default</button></form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('account/addresses/' . $a['id'] . '/delete')) ?>" data-confirm="Remove this address?"><?= csrf_field() ?><button class="btn btn-outline btn-sm">Remove</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="panel">
                <h3>Add a new address</h3>
                <form class="form" method="post" action="<?= e(url('account/addresses')) ?>">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-light loc-btn" data-use-location>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.2 7-11.5A7 7 0 005 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                        Use my current location
                    </button>
                    <div class="form-row">
                        <label>Full name <input type="text" name="name" required></label>
                        <label>Mobile <input type="tel" name="phone" required></label>
                    </div>
                    <label>Address <input type="text" name="line1" required></label>
                    <label>Apartment, landmark <input type="text" name="line2"></label>
                    <div class="form-row">
                        <label>City <input type="text" name="city" required></label>
                        <label>Pincode <input type="text" name="pincode" maxlength="6" required></label>
                    </div>
                    <label>State <select name="state" required><option value="">Select state</option>
                        <?php foreach ($states as $s): ?><option><?= e($s) ?></option><?php endforeach; ?></select></label>
                    <button class="btn btn-dark" type="submit">Save address</button>
                </form>
            </div>
        </div>
    </div>
</section>
