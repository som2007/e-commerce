<?php
/**
 * Shared shell for every authentication screen.
 * Expects: $authPage (js key), $authTitle (tab title), $authContent (html), optional $authData (data-* attributes).
 */
$brand = (string) config('app.name');
$authData = $authData ?? [];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="robots" content="noindex">
    <title><?= e($authTitle) ?> | <?= e($brand) ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%276%27 fill=%27%233a0f1c%27/%3E%3Ctext x=%2716%27 y=%2723%27 font-size=%2720%27 text-anchor=%27middle%27 fill=%27%23d6a84f%27 font-family=%27Georgia%27%3ET%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/auth.css')) ?>">
</head>
<body data-page="<?= e($authPage) ?>" data-base="<?= e(rtrim((string) config('app.url'), '/')) ?>"<?php foreach ($authData as $k => $v): ?> data-<?= e($k) ?>="<?= e($v) ?>"<?php endforeach; ?>>
<div class="auth">
    <aside class="auth-side">
        <a class="brand" href="<?= e(url('/')) ?>"><?= e($brand) ?><small>CLOTHING &middot; EST. 2024</small></a>
        <div class="auth-quote">
            <h2>Classic style,<br>made to be lived in.</h2>
            <hr class="rule">
            <p>Shirts, tees, denim, jackets and kurtas &mdash; crafted for everyday wear and the occasions in between.</p>
        </div>
        <ul class="perks">
            <li><i></i>Free shipping above <?= e(money(config('store.free_shipping_over'), false)) ?></li>
            <li><i></i><?= (int) config('store.prepaid_discount_percent') ?>% off on online payments</li>
            <li><i></i>7-day easy exchange</li>
        </ul>
    </aside>

    <main class="auth-main">
        <div class="auth-top">
            <a class="back" href="<?= e(url('/')) ?>">&larr; Back to home</a>
        </div>
        <div class="auth-card">
            <?= $authContent ?>
        </div>
    </main>
</div>
<?php partial('loader'); ?>
<script src="<?= e(asset('js/auth.js')) ?>"></script>
</body>
</html>
