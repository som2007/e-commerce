<?php
/** @var int $page @var int $pages @var string $base @var array $query */
if ($pages > 1):
    $window = range(max(1, $page - 2), min($pages, $page + 2));
?>
<nav class="pagination" aria-label="Pagination">
    <?php if ($page > 1): ?><a href="<?= e(page_url($base, $query, ['page' => $page - 1])) ?>">&laquo; Prev</a><?php endif; ?>
    <?php if ($window[0] > 1): ?><a href="<?= e(page_url($base, $query, ['page' => 1])) ?>">1</a><span>&hellip;</span><?php endif; ?>
    <?php foreach ($window as $n): ?>
        <a class="<?= $n === $page ? 'active' : '' ?>" href="<?= e(page_url($base, $query, ['page' => $n])) ?>"><?= $n ?></a>
    <?php endforeach; ?>
    <?php if (end($window) < $pages): ?><span>&hellip;</span><a href="<?= e(page_url($base, $query, ['page' => $pages])) ?>"><?= $pages ?></a><?php endif; ?>
    <?php if ($page < $pages): ?><a href="<?= e(page_url($base, $query, ['page' => $page + 1])) ?>">Next &raquo;</a><?php endif; ?>
</nav>
<?php endif; ?>
