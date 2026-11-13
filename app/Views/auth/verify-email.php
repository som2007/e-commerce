<?php
$authPage = 'verify';
$authTitle = 'Verify your email';
$authData = ['user' => (string) ($_GET['user_id'] ?? ''), 'login-url' => url('login'), 'register-url' => url('register')];
ob_start();
?>
<div class="mail-badge" aria-hidden="true">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
</div>
<div id="verifyView">
    <h1>Verify your email</h1>
    <p class="sub">We sent a 6-digit code to <strong><?= e($maskedEmail ?? 'your email address') ?></strong>. Enter it below to activate your account.</p>

    <div class="alert" id="msg" role="alert" hidden></div>

    <div class="otp" id="otp" role="group" aria-label="6-digit verification code"></div>
    <button class="btn btn-primary" type="button" id="submitBtn" style="margin-top:14px">Verify email</button>

    <div class="resend">Didn't get the code? <button class="btn-link" type="button" id="resendBtn" disabled>Resend code</button></div>
    <p class="hint" style="text-align:center">The code is valid for 10 minutes. Check your spam folder too.</p>
</div>
<div id="doneView" hidden class="center" style="text-align:center">
    <div class="success-mark">&#10003;</div>
    <h1>Email verified</h1>
    <p class="sub">Your account is ready. Taking you to sign in...</p>
    <a class="btn btn-primary" href="<?= e(url('login')) ?>" style="text-decoration:none">Continue to sign in</a>
</div>
<p class="foot"><a href="<?= e(url('register')) ?>">Use a different email</a></p>
<?php
$authContent = ob_get_clean();
require __DIR__ . '/_layout.php';
