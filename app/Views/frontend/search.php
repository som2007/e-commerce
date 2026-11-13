<section class="container section-sm">
    <h1 class="page-title">Search</h1>
    <form class="search-page-form" method="get" action="<?= e(url('search')) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search shirts, colours, fabrics..." aria-label="Search" autofocus>
        <button class="btn btn-dark" type="submit">Search</button>
    </form>

    <?php if ($q !== ''): ?>
        <div class="toolbar">
            <span class="count"><?= (int) $total ?> result<?= $total === 1 ? '' : 's' ?> for &ldquo;<?= e($q) ?>&rdquo;</span>
            <form method="get" action="<?= e(url('search')) ?>">
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <label class="sort">Sort by
                    <select name="sort_by" data-autosubmit>
                        <?php foreach ($sorts as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= $k === $sort ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </form>
        </div>

        <?php if (!$items): ?>
            <div class="empty"><h3>No results</h3><p class="muted">Check the spelling or try a more general term, like &ldquo;shirt&rdquo; or &ldquo;black&rdquo;.</p></div>
        <?php else: ?>
            <div class="grid"><?php foreach ($items as $p): ?><?php partial('product-card', ['p' => $p]); ?><?php endforeach; ?></div>
            <?php partial('pagination', ['page' => $page, 'pages' => $pages, 'base' => 'search', 'query' => ['q' => $q, 'sort_by' => $sort]]); ?>
        <?php endif; ?>
    <?php endif; ?>
</section>
