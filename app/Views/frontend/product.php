<?php
$price = (float) $product['price'];
$sale = $product['sale_price'] !== null ? (float) $product['sale_price'] : null;
$off = discount_percent($price, $sale);
$totalReviews = array_sum($breakdown);
$firstAvail = null;
foreach ($variants as $v) {
    if ((int) $v['stock'] > 0) { $firstAvail = $v; break; }
}
$anyStock = $firstAvail !== null;
$variantData = array_map(fn ($v) => [
    'id' => (int) $v['id'], 'size' => $v['size'], 'stock' => (int) $v['stock'],
    'price' => (float) $v['price'], 'sale' => $v['sale_price'] !== null ? (float) $v['sale_price'] : null,
    'priceText' => money($v['price']), 'saleText' => $v['sale_price'] !== null ? money($v['sale_price']) : null,
], $variants);
$old = session()->get('_old', []);
?>
<section class="container section-sm">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(url('/')) ?>">Home</a><span>/</span>
        <?php if ($product['category_slug']): ?><a href="<?= e(url('collections/' . $product['category_slug'])) ?>"><?= e($product['category_name']) ?></a><span>/</span><?php endif; ?>
        <span><?= e($product['name']) ?></span>
    </nav>

    <div class="pdp">
        <!-- Gallery -->
        <div class="gallery" data-gallery>
            <div class="gallery-thumbs">
                <?php foreach ($images as $i => $img): ?>
                    <button type="button" class="thumb <?= $i === 0 ? 'active' : '' ?>" data-src="<?= e(product_image($img['image_path'])) ?>" aria-label="Image <?= $i + 1 ?>">
                        <img src="<?= e(product_image($img['image_path'])) ?>" alt="" loading="lazy" width="80" height="100">
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="gallery-main">
                <?php if ($product['badge']): ?><span class="badge badge-gold"><?= e($product['badge']) ?></span><?php endif; ?>
                <img id="mainImage" src="<?= e(product_image($images[0]['image_path'] ?? null)) ?>" alt="<?= e($product['name']) ?>" width="600" height="760">
            </div>
        </div>

        <!-- Buy box -->
        <div class="buybox">
            <h1 class="pdp-title"><?= e($product['name']) ?></h1>

            <?php if ($product['rating_count'] > 0): ?>
                <a class="rating" href="#reviews"><span class="stars"><?= stars((float) $product['rating_avg']) ?></span>
                    <span class="muted"><?= (int) $product['rating_count'] ?> review<?= $product['rating_count'] == 1 ? '' : 's' ?></span></a>
            <?php endif; ?>

            <div class="price big" id="priceBox">
                <span class="now" id="priceNow"><?= money($sale ?: $price) ?></span>
                <?php if ($off > 0): ?>
                    <s class="was" id="priceWas"><?= money($price) ?></s>
                    <span class="badge inline" id="priceOff">Save <?= $off ?>%</span>
                <?php endif; ?>
            </div>
            <p class="muted small">Inclusive of all taxes.</p>

            <p class="promo">Pay online (UPI / card / net banking) and get <strong><?= (int) config('store.prepaid_discount_percent') ?>% off</strong> automatically at checkout.</p>

            <?php if (count($siblings) > 1): ?>
                <div class="field-block">
                    <div class="label-row"><span class="label">Colour: <strong><?= e($product['color']) ?></strong></span></div>
                    <div class="swatches">
                        <?php foreach ($siblings as $s): ?>
                            <a class="swatch <?= (int) $s['id'] === (int) $product['id'] ? 'active' : '' ?> <?= (int) $s['in_stock'] === 0 ? 'oos' : '' ?>"
                               href="<?= e(url('products/' . $s['slug'])) ?>" title="<?= e($s['color']) ?>"
                               style="--c:#<?= e($s['color_hex']) ?>" aria-label="<?= e($s['color']) ?>"></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form id="addForm">
                <div class="field-block">
                    <div class="label-row">
                        <span class="label">Size</span>
                        <button class="link" type="button" data-open-modal="sizeModal">Size chart</button>
                    </div>
                    <div class="sizes" id="sizes">
                        <?php foreach ($variants as $v): ?>
                            <button type="button" class="size <?= (int) $v['stock'] < 1 ? 'oos' : '' ?>"
                                    data-variant="<?= (int) $v['id'] ?>" <?= (int) $v['stock'] < 1 ? 'disabled' : '' ?>
                                    aria-pressed="false"><?= e($v['size']) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <p class="muted small" id="sizeHint">Select a size</p>
                </div>

                <div class="field-block">
                    <span class="label">Quantity</span>
                    <div class="qty" data-qty>
                        <button type="button" data-dec aria-label="Decrease quantity">&minus;</button>
                        <input type="number" id="qtyInput" value="1" min="1" max="10" aria-label="Quantity">
                        <button type="button" data-inc aria-label="Increase quantity">+</button>
                    </div>
                </div>

                <div class="cta-row">
                    <button class="btn btn-outline btn-lg" type="button" id="addBtn" <?= $anyStock ? '' : 'disabled' ?>><?= $anyStock ? 'Add to cart' : 'Sold out' ?></button>
                    <button class="btn btn-dark btn-lg" type="button" id="buyBtn" <?= $anyStock ? '' : 'disabled' ?>>Buy now</button>
                </div>
            </form>

            <ul class="assure">
                <li>Delivery within 3-6 days</li>
                <li>Only 7-day exchange accepted</li>
                <li>Secure payments &middot; Cash on delivery available</li>
            </ul>

            <div class="tabs" data-tabs>
                <div class="tab-buttons">
                    <button type="button" class="active" data-tab="t1">Description</button>
                    <button type="button" data-tab="t2">Details</button>
                    <button type="button" data-tab="t3">Care</button>
                    <button type="button" data-tab="t4">Shipping &amp; returns</button>
                </div>
                <div class="tab-panel active" id="t1"><p><?= e($product['description'] ?: $product['short_description']) ?></p></div>
                <div class="tab-panel" id="t2">
                    <table class="spec">
                        <?php foreach ([
                            'Fabric' => $product['fabric'], 'Fit' => $product['fit'], 'Sleeve' => ['half' => 'Half sleeve', 'full' => 'Full sleeve'][$product['sleeve']] ?? null,
                            'Collar' => $product['collar'], 'Colour' => $product['color'], 'Occasion' => $product['occasion'], 'SKU' => $product['sku'], 'Country of origin' => 'India',
                        ] as $k => $val): if (!$val) continue; ?>
                            <tr><th><?= e($k) ?></th><td><?= e($val) ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <div class="tab-panel" id="t3"><p><?= e($product['care'] ?: 'Cold machine wash, gentle drying, iron on medium heat.') ?></p></div>
                <div class="tab-panel" id="t4"><p>Orders ship within 24-48 hours and arrive in 3-6 days. We accept exchanges within 7 days of delivery for size or fit issues and defective items. <a href="<?= e(url('pages/return-and-exchange-policy')) ?>">Read the full policy</a>.</p></div>
            </div>
        </div>
    </div>

    <!-- Reviews -->
    <section class="reviews" id="reviews">
        <h2>Customer reviews</h2>
        <div class="reviews-grid">
            <div class="review-summary">
                <?php if ($totalReviews): ?>
                    <div class="avg"><span class="big-num"><?= e(number_format((float) $product['rating_avg'], 1)) ?></span>
                        <span class="stars"><?= stars((float) $product['rating_avg']) ?></span>
                        <span class="muted">Based on <?= (int) $totalReviews ?> reviews</span></div>
                    <?php foreach ($breakdown as $star => $n): $pct = $totalReviews ? round($n / $totalReviews * 100) : 0; ?>
                        <div class="bar-row"><span><?= $star ?> ★</span>
                            <div class="bar"><i style="width:<?= $pct ?>%"></i></div><span class="muted"><?= $pct ?>% (<?= $n ?>)</span></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="muted">No reviews yet. Be the first to write one.</p>
                <?php endif; ?>
                <button class="btn btn-outline" type="button" data-open-modal="reviewModal">Write a review</button>
            </div>
            <div class="review-list">
                <?php foreach ($reviews as $r): ?>
                    <article class="review">
                        <div class="stars"><?= stars((float) $r['rating']) ?></div>
                        <?php if ($r['title']): ?><strong><?= e($r['title']) ?></strong><?php endif; ?>
                        <p><?= e($r['body']) ?></p>
                        <p class="muted small"><?= e($r['name']) ?> &middot; <?= e(time_ago($r['created_at'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($related): ?>
        <section class="section-sm">
            <div class="section-head"><h2>You may also like</h2></div>
            <div class="rail" data-rail>
                <?php foreach ($related as $p): ?><div class="rail-item"><?php partial('product-card', ['p' => $p]); ?></div><?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>

<!-- Size chart modal -->
<div class="modal" id="sizeModal" hidden>
    <div class="modal-box">
        <button class="modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
        <h3>Size chart</h3>
        <?= $sizeChart ?? '<p>Size chart unavailable.</p>' ?>
    </div>
</div>

<!-- Review modal -->
<div class="modal" id="reviewModal" hidden>
    <div class="modal-box">
        <button class="modal-close" type="button" data-close-modal aria-label="Close">&times;</button>
        <h3>Write a review</h3>
        <form method="post" action="<?= e(url('products/' . $product['slug'] . '/reviews')) ?>" class="form">
            <?= csrf_field() ?>
            <label>Rating
                <span class="star-input" data-star-input>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" name="rating" id="st<?= $i ?>" value="<?= $i ?>" <?= (int) ($old['rating'] ?? 0) === $i ? 'checked' : '' ?> required><label for="st<?= $i ?>" title="<?= $i ?> stars">★</label>
                    <?php endfor; ?>
                </span>
            </label>
            <label>Your name <input type="text" name="name" value="<?= e($old['name'] ?? (auth_user()['first_name'] ?? '')) ?>" required maxlength="120"></label>
            <label>Title <input type="text" name="title" value="<?= e($old['title'] ?? '') ?>" maxlength="180"></label>
            <label>Review <textarea name="body" rows="4" required maxlength="2000"><?= e($old['body'] ?? '') ?></textarea></label>
            <button class="btn btn-dark" type="submit">Submit review</button>
            <p class="muted small">Reviews are published after a quick moderation check.</p>
        </form>
    </div>
</div>

<script nonce="<?= csp_nonce() ?>">window.PRODUCT = <?= json_encode(['variants' => $variantData, 'slug' => $product['slug']], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
