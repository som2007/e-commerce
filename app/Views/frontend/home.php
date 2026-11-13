<?php
$brand = config('app.name');
$rail = function (string $eyebrow, string $title, array $items, string $href) {
    if (!$items) {
        return;
    }
    ?>
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div><p class="eyebrow"><?= e($eyebrow) ?></p><h2><?= e($title) ?></h2></div>
                <a class="link-arrow" href="<?= e(url($href)) ?>">View all &rarr;</a>
            </div>
            <div class="rail" data-rail>
                <?php foreach ($items as $p): ?><div class="rail-item"><?php partial('product-card', ['p' => $p]); ?></div><?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
};
?>

<section class="hero">
    <div class="hero-inner container">
        <p class="eyebrow light">New season</p>
        <h1>Dress well.<br>Every single day.</h1>
        <p class="hero-sub">Shirts, tees, denim, hoodies, jackets, kurtas and more for him and her, in 25+ colours.</p>
        <div class="hero-cta">
            <a class="btn btn-light" href="<?= e(url('collections/new-arrivals')) ?>">Shop new arrivals</a>
            <a class="btn btn-ghost" href="<?= e(url('collections/best-selling-products')) ?>">Best sellers</a>
        </div>
    </div>
    <div class="hero-art" aria-hidden="true">
        <img src="<?= e(url('img/shirt/27386b/front.svg?s=blazer')) ?>" alt="">
        <img src="<?= e(url('img/shirt/b5502e/front.svg?s=hoodie')) ?>" alt="">
        <img src="<?= e(url('img/shirt/4a6fa5/front.svg?s=jeans')) ?>" alt="">
    </div>
</section>

<section class="offer-strip">
    <div class="container offer-grid">
        <div><strong><?= (int) config('store.prepaid_discount_percent') ?>% off</strong> on online payments</div>
        <div><strong>Free shipping</strong> above <?= money(config('store.free_shipping_over'), false) ?></div>
        <div><strong>7-day</strong> easy exchange</div>
    </div>
</section>


<section class="section-sm cat-tiles">
    <div class="container">
        <div class="section-head"><div><p class="eyebrow">Shop by category</p><h2>Find your fit</h2></div></div>
        <div class="tile-grid">
            <?php foreach ([
                ['Shirts', 'collections/formal-shirts-for-men', '2650b5', 'full'],
                ['T-Shirts & Polos', 'collections/men-tshirts', '178a5b', 'polo'],
                ['Jeans & Trousers', 'collections/men-bottomwear', '4a6fa5', 'jeans'],
                ['Hoodies', 'collections/hoodies-for-men', 'b5502e', 'hoodie'],
                ['Jackets & Blazers', 'collections/men-winter', '27386b', 'blazer'],
                ['Kurtas', 'collections/men-ethnic', 'd9a21b', 'kurta'],
                ['Women\'s Dresses', 'collections/women-dresses', 'b9a9d6', 'dress'],
                ['Women\'s Wear', 'collections/women', 'f6c6a8', 'tee'],
            ] as [$label, $href, $hex, $kind]): ?>
                <a class="tile" href="<?= e(url($href)) ?>">
                    <img src="<?= e(url("img/shirt/{$hex}/front.svg?s={$kind}")) ?>" alt="" loading="lazy">
                    <span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php $rail('Just landed', 'New Arrivals', $newArrivals, 'collections/new-arrivals'); ?>
<?php $rail('Stay warm in style', 'Hoodies, Jackets & Blazers', $winter, 'collections/men-winter'); ?>
<?php $rail('Denim & more', 'Jeans, Trousers & Chinos', $bottoms, 'collections/men-bottomwear'); ?>
<?php $rail('For her', 'Women\'s Collection', $women, 'collections/women'); ?>

<section class="section video-band">
    <div class="container video-grid">
        <div class="video-card">
            <div class="video-poster">
                <img src="<?= e(url('img/shirt/1f7a8c/folded.svg?s=hoodie')) ?>" alt="" loading="lazy">
                <button class="play" type="button" aria-label="Play brand film" data-play-film>&#9654;</button>
            </div>
        </div>
        <div>
            <p class="eyebrow">Behind the stitch</p>
            <h2>Made with 20+ years of menswear know-how</h2>
            <p class="muted">From fabric selection to the last button, every piece goes through a multi-point quality check. Watch how we make the clothes you wear every day.</p>
            <a class="btn btn-dark" href="<?= e(url('pages/our-story')) ?>">Our story</a>
        </div>
    </div>
</section>

<?php if ($latest): ?>
<section class="section latest">
    <div class="container latest-grid">
        <div class="latest-copy">
            <p class="eyebrow">Latest collection</p>
            <h2>T-Shirts &amp; Polos for Every Day</h2>
            <p class="muted">Soft cotton, clean cuts and colours that go with everything.</p>
            <p class="price big"><span class="now">From <?= money($latest[0]['sale_price'] ?: $latest[0]['price']) ?></span>
                <s class="was"><?= money($latest[0]['price']) ?></s>
                <span class="badge inline">Save <?= discount_percent((float) $latest[0]['price'], (float) $latest[0]['sale_price']) ?>%</span></p>
            <a class="btn btn-dark" href="<?= e(url('collections/men-tshirts')) ?>">View products</a>
        </div>
        <div class="shop-look" data-rail>
            <?php foreach (array_slice($latest, 0, 6) as $p): ?>
                <a class="look" href="<?= e(url('products/' . $p['slug'])) ?>">
                    <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                    <span><?= e($p['color']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php $rail('Customer favourites', 'Best Selling Products', $bestSellers, 'collections/best-selling-products'); ?>

<section class="tagline">
    <div class="container center">
        <h2><?= e(config('store.tagline')) ?></h2>
        <p>More than 20 years of apparel expertise, now delivered to your doorstep.</p>
    </div>
</section>

<section class="section about-band">
    <div class="container narrow center">
        <p class="eyebrow">About <?= e($brand) ?></p>
        <p class="lead">We are a clothing label for men and women focused on dependable quality: wrinkle-resistant fabrics, a wide size range and prices that respect your budget.</p>
        <a class="link-arrow" href="<?= e(url('pages/about-us')) ?>">Know more &rarr;</a>
    </div>
</section>
