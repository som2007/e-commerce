<article class="container narrow section-sm">
    <p><a class="link-arrow" href="<?= e(url('blogs/news')) ?>">&larr; All posts</a></p>
    <h1 class="page-title"><?= e($post['title']) ?></h1>
    <p class="muted small"><?= e(time_ago($post['created_at'])) ?></p>
    <div class="prose"><?= html_clean($post['body']) ?></div>
</article>
