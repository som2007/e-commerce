<?php $nav = ['' => 'Dashboard', 'products' => 'Products', 'categories' => 'Categories', 'orders' => 'Orders', 'coupons' => 'Coupons', 'reviews' => 'Reviews', 'exchanges' => 'Exchanges', 'messages' => 'Messages', 'subscribers' => 'Subscribers', 'pages' => 'Pages', 'blog' => 'Blog'];
$path = trim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(rtrim(parse_url((string) config('app.url'), PHP_URL_PATH) ?: '', '/') . '/admin')), '/');
$cur = explode('/', $path)[0]; ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>"><title><?= e($title) ?> | Admin</title>
<link rel="icon" href="data:,"><link rel="stylesheet" href="<?= e(asset('css/store.css')) ?>"><link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script nonce="<?= csp_nonce() ?>">window.APP={base:<?= json_encode(rtrim((string) config('app.url'), '/')) ?>};</script></head>
<body class="admin">
<aside class="a-side"><a class="a-logo" href="<?= e(url('admin')) ?>"><?= e(config('app.name')) ?> <small>Admin</small></a>
<?php foreach ($nav as $k => $label): ?><a class="<?= $cur === $k ? 'on' : '' ?>" href="<?= e(url('admin' . ($k ? '/' . $k : ''))) ?>"><?= e($label) ?></a><?php endforeach; ?>
<hr><a href="<?= e(url('/')) ?>" target="_blank">View store &nearr;</a><a href="#" data-logout>Log out</a></aside>
<main class="a-main"><h1 class="a-title"><?= e($title) ?></h1>
<?php foreach (['success' => flash('success'), 'error' => flash('error')] as $t => $m): if ($m): ?><div class="alert alert-<?= $t ?>"><?= e($m) ?></div><?php endif; endforeach; ?>
<?= $content ?></main>
<div class="toast" id="toast"></div><script src="<?= e(asset('js/store.js')) ?>"></script></body></html>
