<section class="container section-sm">
    <h1 class="page-title">Your cart</h1>

    <?php if (!$cart['lines']): ?>
        <div class="empty">
            <h3>Your cart is empty</h3>
            <p class="muted">Looks like you have not added anything yet.</p>
            <a class="btn btn-dark" href="<?= e(url('collections/all')) ?>">Continue shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-layout" id="cartPage">
            <div class="cart-lines">
                <?php foreach ($cart['lines'] as $l): ?>
                    <div class="line" data-line="<?= (int) $l['variant_id'] ?>">
                        <a href="<?= e(url('products/' . $l['slug'])) ?>"><img src="<?= e(product_image($l['image'])) ?>" alt="" width="90" height="114"></a>
                        <div class="line-info">
                            <a class="line-name" href="<?= e(url('products/' . $l['slug'])) ?>"><?= e($l['name']) ?></a>
                            <p class="muted small">Size: <?= e($l['size']) ?><?= $l['color'] ? ' &middot; ' . e($l['color']) : '' ?></p>
                            <p><?= money($l['unit']) ?><?php if ((float) $l['price'] > $l['unit']): ?> <s class="was"><?= money($l['price']) ?></s><?php endif; ?></p>
                            <div class="qty" data-qty-line="<?= (int) $l['variant_id'] ?>">
                                <button type="button" data-dec aria-label="Decrease">&minus;</button>
                                <input type="number" value="<?= (int) $l['qty'] ?>" min="1" max="<?= min(10, (int) $l['stock']) ?>" aria-label="Quantity">
                                <button type="button" data-inc aria-label="Increase">+</button>
                            </div>
                        </div>
                        <div class="line-end">
                            <strong><?= money($l['line_total']) ?></strong>
                            <button class="link" type="button" data-remove-line="<?= (int) $l['variant_id'] ?>">Remove</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <aside class="summary">
                <h3>Order summary</h3>
                <div class="row"><span>Subtotal</span><strong><?= money($cart['subtotal']) ?></strong></div>
                <?php if ($cart['savings'] > 0): ?><div class="row save"><span>You save</span><span><?= money($cart['savings']) ?></span></div><?php endif; ?>
                <p class="muted small">Shipping and the <?= (int) config('store.prepaid_discount_percent') ?>% online payment discount are applied at checkout.</p>
                <a class="btn btn-dark btn-block btn-lg" href="<?= e(url('checkout')) ?>">Checkout</a>
                <a class="btn btn-light btn-block" href="<?= e(url('collections/all')) ?>">Continue shopping</a>
            </aside>
        </div>
    <?php endif; ?>
</section>
