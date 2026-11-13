<section class="container section-sm">
    <h1 class="page-title">News &amp; style guides</h1>
    <div class="blog-grid">
        <?php foreach ($posts as $p): ?>
            <a class="post-card" href="<?= e(url('blogs/news/' . $p['slug'])) ?>">
                <span class="muted small"><?= e(time_ago($p['created_at'])) ?></span>
                <h3><?= e($p['title']) ?></h3>
                <p class="muted"><?= e($p['excerpt']) ?></p>
                <span class="link-arrow">Read more &rarr;</span>
            </a>
        <?php endforeach; ?>
        <?php if (!$posts): ?><p class="muted">No posts yet.</p><?php endif; ?>
    </div>
</section>
