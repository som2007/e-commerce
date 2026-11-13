<?php
/** @var array $p  card row from Catalog::cardSelect() */
$price = (float) $p['price'];
$sale = $p['sale_price'] !== null ? (float) $p['sale_price'] : null;
$off = discount_percent($price, $sale);
$soldOut = (int) ($p['in_stock'] ?? 1) === 0;
$link = url('products/' . $p['slug']);
?>
<article class="card">
    <a class="card-media" href="<?= e($link) ?>" aria-label="<?= e($p['name']) ?>">
        <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy" width="600" height="760">
        <?php if (!empty($p['image2'])): ?>
            <img class="alt" src="<?= e(product_image($p['image2'])) ?>" alt="" loading="lazy" width="600" height="760">
        <?php endif; ?>
        <?php if ($soldOut): ?><span class="badge badge-dark">Sold out</span>
        <?php elseif ($off > 0): ?><span class="badge">Save <?= $off ?>%</span><?php endif; ?>
    </a>
    <div class="card-body">
        <h3 class="card-title"><a href="<?= e($link) ?>"><?= e($p['name']) ?></a></h3>
        <?php if ((int) $p['rating_count'] > 0): ?>
            <div class="rating" title="<?= e($p['rating_avg']) ?> out of 5">
                <span class="stars"><?= stars((float) $p['rating_avg']) ?></span> <span class="muted">(<?= (int) $p['rating_count'] ?>)</span>
            </div>
        <?php endif; ?>
        <div class="price">
            <span class="now"><?= $sale ? 'From ' . money($sale) : money($price) ?></span>
            <?php if ($off > 0): ?><s class="was"><?= money($price) ?></s><?php endif; ?>
        </div>
        <a class="btn btn-outline btn-sm btn-block" href="<?= e($link) ?>"><?= $soldOut ? 'View' : 'Choose options' ?></a>
    </div>
</article>
