<?php
$notice = flash('info');
$redirectTo = url('dashboard'); // sends admins to /admin, others back to where they came from
$authPage = 'login';
$authTitle = 'Sign in';
$authData = ['redirect' => $redirectTo, 'verify-url' => url('verify-email')];
ob_start();
?>
<h1>Welcome back</h1>
<p class="sub">Sign in to your <?= e(config('app.name')) ?> account to continue.</p>

<?php if ($notice): ?><div class="alert info"><?= e($notice) ?></div><?php endif; ?>
<div class="alert" id="msg" role="alert" hidden></div>

<form id="loginForm" novalidate>
    <div class="field">
        <label for="email">Email address</label>
        <input class="input" type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required autofocus>
    </div>
    <div class="field">
        <label for="password">Password</label>
        <div class="pw">
            <input class="input" type="password" id="password" name="password" placeholder="Your password" autocomplete="current-password" required>
            <button type="button" data-toggle-pw aria-label="Show password">Show</button>
        </div>
    </div>
    <div class="between">
        <span></span>
        <a href="<?= e(url('forgot-password')) ?>">Forgot password?</a>
    </div>
    <button class="btn btn-primary" type="submit" id="submitBtn">Sign in</button>
</form>
<p class="foot">New to <?= e(config('app.name')) ?>? <a href="<?= e(url('register')) ?>">Create an account</a></p>
<?php
$authContent = ob_get_clean();
require __DIR__ . '/_layout.php';
