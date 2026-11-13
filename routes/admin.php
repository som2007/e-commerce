<?php

use Rk\Threadwell\Controllers\Admin\AdminController;
use Rk\Threadwell\Middleware\AdminMiddleware;
use Rk\Threadwell\Middleware\CsrfMiddleware;

$csrf = [CsrfMiddleware::class];

$router->group('/admin', [AdminMiddleware::class], function ($r) use ($csrf) {
    $c = AdminController::class;

    $r->get('/', [$c, 'dashboard']);

    // Products
    $r->get('/products', [$c, 'products']);
    $r->get('/products/new', [$c, 'productForm']);
    $r->get('/products/{id}/edit', [$c, 'productForm']);
    $r->post('/products', [$c, 'productSave'], $csrf);
    $r->post('/products/{id}', [$c, 'productSave'], $csrf);
    $r->post('/products/{id}/delete', [$c, 'productDelete'], $csrf);
    $r->post('/products/{id}/images', [$c, 'imageUpload'], $csrf);
    $r->post('/images/{id}/delete', [$c, 'imageDelete'], $csrf);
    $r->post('/images/{id}/primary', [$c, 'imagePrimary'], $csrf);

    // Categories
    $r->get('/categories', [$c, 'categories']);
    $r->post('/categories', [$c, 'categorySave'], $csrf);
    $r->post('/categories/{id}/delete', [$c, 'categoryDelete'], $csrf);

    // Orders
    $r->get('/orders', [$c, 'orders']);
    $r->get('/orders/{id}', [$c, 'orderShow']);
    $r->post('/orders/{id}/status', [$c, 'orderStatus'], $csrf);

    // Coupons
    $r->get('/coupons', [$c, 'coupons']);
    $r->post('/coupons', [$c, 'couponSave'], $csrf);
    $r->post('/coupons/{id}/toggle', [$c, 'couponToggle'], $csrf);
    $r->post('/coupons/{id}/delete', [$c, 'couponDelete'], $csrf);

    // Reviews / exchanges / messages / subscribers
    $r->get('/reviews', [$c, 'reviews']);
    $r->post('/reviews/{id}/{action}', [$c, 'reviewAction'], $csrf);
    $r->get('/exchanges', [$c, 'exchanges']);
    $r->post('/exchanges/{id}', [$c, 'exchangeUpdate'], $csrf);
    $r->get('/messages', [$c, 'messages']);
    $r->post('/messages/{id}/read', [$c, 'messageRead'], $csrf);
    $r->get('/subscribers', [$c, 'subscribers']);

    // CMS
    $r->get('/pages', [$c, 'pages']);
    $r->get('/pages/{id}/edit', [$c, 'pageForm']);
    $r->post('/pages/{id}', [$c, 'pageSave'], $csrf);
    $r->get('/blog', [$c, 'blog']);
    $r->get('/blog/new', [$c, 'postForm']);
    $r->get('/blog/{id}/edit', [$c, 'postForm']);
    $r->post('/blog', [$c, 'postSave'], $csrf);
    $r->post('/blog/{id}', [$c, 'postSave'], $csrf);
    $r->post('/blog/{id}/delete', [$c, 'postDelete'], $csrf);
});
