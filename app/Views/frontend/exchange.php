<section class="container narrow section-sm">
    <h1 class="page-title">Raise an exchange request</h1>
    <p class="muted">Exchanges are accepted within 7 days of delivery for unused, unwashed items with tags. See the <a href="<?= e(url('pages/return-and-exchange-policy')) ?>">exchange policy</a>.</p>

    <form class="form panel" method="post" action="<?= e(url('exchange-request')) ?>">
        <?= csrf_field() ?>
        <div class="form-row">
            <label>Order number <input type="text" name="order_number" value="<?= e(old('order_number')) ?>" required></label>
            <label>Email used at checkout <input type="email" name="email" value="<?= e(old('email')) ?>" required></label>
        </div>
        <label>Which item? (name / colour / size) <input type="text" name="item_note" value="<?= e(old('item_note')) ?>" maxlength="255"></label>
        <div class="form-row">
            <label>Reason
                <select name="reason" required>
                    <option value="">Select a reason</option>
                    <?php foreach ($reasons as $r): ?><option <?= old('reason') === $r ? 'selected' : '' ?>><?= e($r) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Size you want
                <select name="new_size">
                    <option value="">Same size / not applicable</option>
                    <?php foreach ($sizes as $s): ?><option <?= old('new_size') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
                </select>
            </label>
        </div>
        <label>Anything else we should know? <textarea name="details" rows="4" maxlength="1500"><?= e(old('details')) ?></textarea></label>
        <button class="btn btn-dark" type="submit">Submit request</button>
    </form>
</section>
