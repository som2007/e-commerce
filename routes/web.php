<?php

use Rk\Threadwell\Controllers\AccountController;
use Rk\Threadwell\Controllers\AuthController;
use Rk\Threadwell\Controllers\CartController;
use Rk\Threadwell\Controllers\CheckoutController;
use Rk\Threadwell\Controllers\CollectionController;
use Rk\Threadwell\Controllers\ContentController;
use Rk\Threadwell\Controllers\HomeController;
use Rk\Threadwell\Controllers\ImageController;
use Rk\Threadwell\Controllers\ProductController;
use Rk\Threadwell\Controllers\ReviewController;
use Rk\Threadwell\Controllers\SearchController;
use Rk\Threadwell\Controllers\SupportController;
use Rk\Threadwell\Middleware\AuthMiddleware;
use Rk\Threadwell\Middleware\CsrfMiddleware;
use Rk\Threadwell\Middleware\CustomerMiddleware;

$csrf = [CsrfMiddleware::class];

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
*/

$router->get('/', [HomeController::class, 'index']);

$router->get('/collections/all', [CollectionController::class, 'all']);
$router->get('/collections/{slug}', [CollectionController::class, 'show']);
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{slug}', [ProductController::class, 'show']);
$router->post('/products/{slug}/reviews', [ReviewController::class, 'store'], $csrf);

$router->get('/search', [SearchController::class, 'index']);
$router->get('/api/v1/search/suggest', [SearchController::class, 'suggest']);

$router->get('/img/shirt/{hex}/{view}', [ImageController::class, 'shirt']);

/* Cart */
$router->get('/cart', [CartController::class, 'page']);
$router->get('/api/v1/cart', [CartController::class, 'state']);
$router->post('/api/v1/cart/add', [CartController::class, 'add'], $csrf);
$router->post('/api/v1/cart/update', [CartController::class, 'update'], $csrf);
$router->post('/api/v1/cart/remove', [CartController::class, 'remove'], $csrf);

/* Checkout + payment */
// Ordering requires a signed-in customer.
$router->group('', [CustomerMiddleware::class], function ($r) use ($csrf) {
    $r->get('/checkout', [CheckoutController::class, 'show']);
    $r->post('/checkout', [CheckoutController::class, 'place'], $csrf);
    $r->post('/api/v1/checkout/quote', [CheckoutController::class, 'quote'], $csrf);
    $r->post('/api/v1/checkout/otp/send', [CheckoutController::class, 'otpSend'], $csrf);
    $r->post('/api/v1/checkout/otp/verify', [CheckoutController::class, 'otpVerify'], $csrf);
    $r->get('/payment/{number}', [CheckoutController::class, 'payment']);
    $r->post('/payment/{number}/verify', [CheckoutController::class, 'verify'], $csrf);
    $r->post('/payment/{number}/test', [CheckoutController::class, 'testPay'], $csrf);
    $r->get('/order/success/{number}', [CheckoutController::class, 'success']);
});

/* Support */
$router->get('/track-order', [SupportController::class, 'track']);
$router->get('/exchange-request', [SupportController::class, 'exchangeForm']);
$router->post('/exchange-request', [SupportController::class, 'exchangeStore'], $csrf);
$router->get('/pages/contact', [SupportController::class, 'contactForm']);
$router->get('/pages/contact-1', [SupportController::class, 'contactForm']);
$router->post('/pages/contact', [SupportController::class, 'contactStore'], $csrf);
$router->post('/api/v1/newsletter', [SupportController::class, 'subscribe'], $csrf);

/* CMS */
$router->get('/pages/{slug}', [ContentController::class, 'page']);
$router->get('/policies/{slug}', [ContentController::class, 'policy']);
$router->get('/blogs/news', [ContentController::class, 'blog']);
$router->get('/blogs/news/{slug}', [ContentController::class, 'post']);

/* Customer account */
// $router->get('/dashboard', function () {
//     if (!auth_id()) {
//         redirect('/login');
//     }

//     $intended = session()->get('intended');
//     session()->remove('intended');

//     redirect(is_admin() ? '/admin' : ($intended ?: '/account'));
// });


$router->get('/dashboard', function () {
    if (!auth_id()) {
        redirect('/login');
    }

    $intended = session()->get('intended');
    session()->remove('intended');

    // `intended` is a raw request URI (includes /threadwell/public), so drop the app's
    // base path before url() adds it again, and only allow internal paths.
    $intended = (string) $intended;
    $basePath = rtrim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
    foreach (array_unique([$basePath, preg_replace('#/public$#', '', $basePath)]) as $b) {
        if ($b !== '' && ($intended === $b || str_starts_with($intended, $b . '/'))) {
            $intended = substr($intended, strlen($b));
            break;
        }
    }
    if ($intended === '' || $intended[0] !== '/' || str_starts_with($intended, '//')) {
        $intended = '';
    }

    redirect(is_admin() ? '/admin' : ($intended ?: '/account'));
});

$router->group('/account', [CustomerMiddleware::class], function ($r) use ($csrf) {
    $r->get('/', [AccountController::class, 'index']);
    $r->post('/profile', [AccountController::class, 'updateProfile'], $csrf);
    $r->get('/orders', [AccountController::class, 'orders']);
    $r->get('/orders/{number}', [AccountController::class, 'order']);
    $r->post('/orders/{number}/cancel', [AccountController::class, 'cancel'], $csrf);
    $r->get('/addresses', [AccountController::class, 'addresses']);
    $r->post('/addresses', [AccountController::class, 'saveAddress'], $csrf);
    $r->post('/addresses/{id}/delete', [AccountController::class, 'deleteAddress'], $csrf);
    $r->post('/addresses/{id}/default', [AccountController::class, 'defaultAddress'], $csrf);
});

/*
|--------------------------------------------------------------------------
| Auth pages
|--------------------------------------------------------------------------
*/

$router->get('/login', [AuthController::class, 'loginPage']);
$router->get('/register', [AuthController::class, 'registerPage']);
$router->get('/verify-email', [AuthController::class, 'verifyEmailPage']);
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordPage']);

require __DIR__ . '/admin.php';

/*
|--------------------------------------------------------------------------
| Authentication API Routes
|--------------------------------------------------------------------------
*/

$router->post('/api/v1/auth/register', [
    AuthController::class,
    'register'
], [
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);


$router->post('/api/v1/auth/login', [
    AuthController::class,
    'login'
], [
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);


$router->post('/api/v1/auth/logout', [
    AuthController::class,
    'logout'
], [
    AuthMiddleware::class,
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$router->get('/api/v1/auth/csrf', [
    AuthController::class,
    'csrf'
]);


/*
|--------------------------------------------------------------------------
| Authenticated User
|--------------------------------------------------------------------------
*/

$router->get('/api/v1/auth/me', [
    AuthController::class,
    'me'
], [
    AuthMiddleware::class
]);


/*
|--------------------------------------------------------------------------
| Email Verification
|--------------------------------------------------------------------------
*/

$router->post('/api/v1/auth/verify-email', [
    AuthController::class,
    'verifyEmail'
], [
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);


$router->post('/api/v1/auth/resend-verification-otp', [
    AuthController::class,
    'resendVerificationOtp'
], [
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);


/*
|--------------------------------------------------------------------------
| Password Reset
|--------------------------------------------------------------------------
*/

$router->post('/api/v1/auth/forgot-password', [
    AuthController::class,
    'forgotPassword'
], [
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);


$router->post('/api/v1/auth/verify-password-reset-otp', [
    AuthController::class,
    'verifyPasswordResetOtp'
], [
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);


$router->post('/api/v1/auth/resend-password-reset-otp', [
    AuthController::class,
    'resendPasswordResetOtp'
], [
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);


$router->post('/api/v1/auth/reset-password', [
    AuthController::class,
    'resetPassword'
], [
    \Rk\Threadwell\Middleware\CsrfMiddleware::class
]);
