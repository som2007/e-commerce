<?php
$formAction = url($base);
?>
<section class="container section-sm">
    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(url('/')) ?>">Home</a>
        <?php foreach ($breadcrumb as $i => $b): ?>
            <span>/</span>
            <?php if ($i < count($breadcrumb) - 1): ?>
                <a href="<?= e(url('collections/' . $b['slug'])) ?>"><?= e($b['name']) ?></a>
            <?php else: ?><span><?= e($b['name']) ?></span><?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$breadcrumb): ?><span>/</span><span>All products</span><?php endif; ?>
    </nav>

    <h1 class="page-title"><?= e($title) ?></h1>
    <?php if (!empty($description)): ?><p class="muted"><?= e($description) ?></p><?php endif; ?>

    <?php if (!empty($category) && $category['parent_id'] === null && !empty($category['id'])): ?>
        <?php
        $subs = [];
        foreach ((new \Rk\Threadwell\Models\Catalog(db()))->menuTree() as $t) {
            if ($t['id'] == $category['id']) { $subs = $t['children']; }
        }
        ?>
        <?php if ($subs): ?>
            <div class="chips">
                <?php foreach ($subs as $s): ?><a class="chip" href="<?= e(url('collections/' . $s['slug'])) ?>"><?= e($s['name']) ?></a><?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form class="toolbar" method="get" action="<?= e($formAction) ?>" id="filterForm">
        <details class="filter" <?= ($inStock || $min !== null || $max !== null) ? 'open' : '' ?>>
            <summary class="btn btn-outline btn-sm">Filter</summary>
            <div class="filter-panel">
                <label class="check"><input type="checkbox" name="in_stock" value="1" <?= $inStock ? 'checked' : '' ?>> In stock only</label>
                <div class="price-inputs">
                    <span>Price (Rs.)</span>
                    <input type="number" name="min_price" min="0" placeholder="<?= (int) $priceRange[0] ?>" value="<?= $min !== null ? e($min) : '' ?>" aria-label="Minimum price">
                    <span>to</span>
                    <input type="number" name="max_price" min="0" placeholder="<?= (int) $priceRange[1] ?>" value="<?= $max !== null ? e($max) : '' ?>" aria-label="Maximum price">
                </div>
                <div class="filter-actions">
                    <button class="btn btn-dark btn-sm" type="submit">View results</button>
                    <a class="btn btn-light btn-sm" href="<?= e($formAction) ?>">Clear</a>
                </div>
            </div>
        </details>

        <label class="sort">Sort by
            <select name="sort_by" data-autosubmit>
                <?php foreach ($sorts as $k => $label): ?>
                    <option value="<?= e($k) ?>" <?= $k === $sort ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <span class="count"><?= (int) $total ?> product<?= $total === 1 ? '' : 's' ?></span>
    </form>

    <?php if (!$items): ?>
        <div class="empty">
            <h3>No products found</h3>
            <p class="muted">Try removing some filters.</p>
            <a class="btn btn-dark" href="<?= e($formAction) ?>">Clear filters</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($items as $p): ?><?php partial('product-card', ['p' => $p]); ?><?php endforeach; ?>
        </div>
        <?php partial('pagination', ['page' => $page, 'pages' => $pages, 'base' => $base, 'query' => $query]); ?>
    <?php endif; ?>
</section>
