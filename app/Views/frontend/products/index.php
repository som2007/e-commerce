
<?php

$pageTitle = $pageTitle ?? 'Shop All Products';

$escape = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

$formatPrice = static function ($price): string {
    return '₹' . number_format((float) $price, 2);
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $escape($pageTitle) ?> | Threadwell</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= $escape(asset('css/style.css')) ?>"
    >
</head>
<body>

<main class="container py-5">

    <div class="mb-4">
        <p class="text-uppercase text-secondary small mb-2">
            Threadwell Collection
        </p>

        <h1 class="fw-bold">Shop All Products</h1>

        <p class="text-secondary">
            Explore our latest collection.
        </p>
    </div>

    <?php if (!empty($categories)): ?>
        <div class="d-flex flex-wrap gap-2 mb-5">
            <?php foreach ($categories as $category): ?>
                <a
                    href="<?= $escape(url(
                        'category/' . $category['slug']
                    )) ?>"
                    class="btn btn-outline-dark rounded-pill"
                >
                    <?= $escape($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 mb-0">Featured Products</h2>

        <span class="text-secondary small">
            <?= count($products ?? []) ?> products
        </span>
    </div>

    <div class="row g-4">

        <?php if (empty($products)): ?>

            <div class="col-12">
                <div class="alert alert-light border">
                    No featured products available yet.
                </div>
            </div>

        <?php else: ?>

            <?php foreach ($products as $product): ?>

                <?php
                $price = (float) $product['price'];
                $salePrice = $product['sale_price'];

                $hasDiscount = $salePrice !== null
                    && $salePrice !== ''
                    && (float) $salePrice > 0
                    && (float) $salePrice < $price;
                ?>

                <div class="col-6 col-md-4 col-lg-3">

                    <article class="card h-100 border-0 product-card">

                        <a
                            href="<?= $escape(url(
                                'product/' . $product['slug']
                            )) ?>"
                            class="text-decoration-none text-dark"
                        >
                            <div class="product-image-placeholder">
                                <span>FILOHEVIS</span>
                            </div>
                        </a>

                        <div class="card-body px-0">

                            <p class="small text-secondary mb-1">
                                <?= $escape(
                                    $product['category_name']
                                    ?? 'Collection'
                                ) ?>
                            </p>

                            <h3 class="h6 mb-2">
                                <a
                                    href="<?= $escape(url(
                                        'product/' . $product['slug']
                                    )) ?>"
                                    class="text-decoration-none text-dark"
                                >
                                    <?= $escape($product['name']) ?>
                                </a>
                            </h3>

                            <div class="d-flex flex-wrap gap-2 align-items-center">

                                <?php if ($hasDiscount): ?>

                                    <span class="fw-bold">
                                        <?= $escape(
                                            $formatPrice($salePrice)
                                        ) ?>
                                    </span>

                                    <del class="small text-secondary">
                                        <?= $escape(
                                            $formatPrice($price)
                                        ) ?>
                                    </del>

                                <?php else: ?>

                                    <span class="fw-bold">
                                        <?= $escape(
                                            $formatPrice($price)
                                        ) ?>
                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>
                    </article>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</main>

</body>
</html>
