<?php
$brand = config('app.name');
$pageTitle = isset($title) ? $title . ' | ' . $brand : $brand;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="description" content="<?= e($metaDescription ?? config('store.tagline')) ?>">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 32 32%27%3E%3Crect width=%2732%27 height=%2732%27 rx=%276%27 fill=%27%2314171f%27/%3E%3Ctext x=%2716%27 y=%2723%27 font-size=%2720%27 text-anchor=%27middle%27 fill=%27white%27 font-family=%27Georgia%27%3EF%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/store.css')) ?>">
    <script nonce="<?= csp_nonce() ?>">window.APP = {base: <?= json_encode(rtrim((string) config('app.url'), '/')) ?>, loggedIn: <?= auth_id() ? 'true' : 'false' ?>};</script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<?php partial('header'); ?>

<main id="main">
    <?php
    $flashOk = flash('success');
    $flashErr = flash('error');
    $flashErrs = flash('errors');
    ?>
    <?php if ($flashOk || $flashErr || $flashErrs): ?>
        <div class="container">
            <?php if ($flashOk): ?><div class="alert alert-success"><?= e($flashOk) ?></div><?php endif; ?>
            <?php if ($flashErr): ?><div class="alert alert-error"><?= e($flashErr) ?></div><?php endif; ?>
            <?php if ($flashErrs): ?>
                <div class="alert alert-error"><ul><?php foreach ((array) $flashErrs as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?= $content ?>
</main>

<?php partial('footer'); ?>

<?php partial('loader'); ?>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="<?= e(asset('js/store.js')) ?>"></script>
</body>
</html>
