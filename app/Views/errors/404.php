<section class="container narrow center section err-page">
    <p class="err-code" aria-hidden="true">404</p>
    <h1>Page not found</h1>
    <p class="muted">The page you are looking for doesn't exist, was moved, or the link is incorrect.</p>

    <form class="err-search" method="get" action="<?= e(url('search')) ?>" role="search">
        <input type="search" name="q" placeholder="Search jeans, hoodies, shirts..." aria-label="Search" maxlength="80">
        <button class="btn btn-dark" type="submit">Search</button>
    </form>

    <p class="err-actions">
        <a class="btn btn-dark" href="<?= e(url('/')) ?>">Back to home</a>
        <a class="btn btn-outline" href="<?= e(url('collections/new-arrivals')) ?>">New arrivals</a>
    </p>
    <p class="err-links muted small">
        Popular:
        <a href="<?= e(url('collections/men-tshirts')) ?>">T-Shirts</a> &middot;
        <a href="<?= e(url('collections/men-bottomwear')) ?>">Jeans &amp; Trousers</a> &middot;
        <a href="<?= e(url('collections/men-winter')) ?>">Hoodies &amp; Jackets</a> &middot;
        <a href="<?= e(url('collections/women')) ?>">Women</a>
    </p>
</section>
