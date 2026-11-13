<?php
$authPage = 'forgot';
$authTitle = 'Reset password';
$authData = ['login-url' => url('login')];
ob_start();
?>
<div class="steps" aria-hidden="true"><span class="on" data-step-bar="1"></span><span data-step-bar="2"></span><span data-step-bar="3"></span></div>
<div class="alert" id="msg" role="alert" hidden></div>

<section class="step" data-step="1">
    <h1>Forgot your password?</h1>
    <p class="sub">Enter your account email and we will send you a 6-digit reset code.</p>
    <form id="emailForm" novalidate>
        <div class="field"><label for="email">Email address</label>
            <input class="input" type="email" id="email" placeholder="you@example.com" autocomplete="email" required autofocus></div>
        <button class="btn btn-primary" type="submit">Send reset code</button>
    </form>
</section>

<section class="step" data-step="2" hidden>
    <h1>Enter the code</h1>
    <p class="sub">We emailed a 6-digit code to <strong id="sentTo">your email</strong>.</p>
    <div class="otp" id="otp" role="group" aria-label="6-digit reset code"></div>
    <button class="btn btn-primary" type="button" id="verifyBtn" style="margin-top:14px">Verify code</button>
    <div class="resend">Didn't get it? <button class="btn-link" type="button" id="resendBtn" disabled>Resend code</button></div>
</section>

<section class="step" data-step="3" hidden>
    <h1>Set a new password</h1>
    <p class="sub">Choose a strong password you have not used before.</p>
    <form id="resetForm" novalidate>
        <div class="field"><label for="password">New password</label>
            <div class="pw"><input class="input" type="password" id="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
                <button type="button" data-toggle-pw aria-label="Show password">Show</button></div>
            <div class="meter"><i id="meter"></i></div><div class="hint" id="meterText"></div></div>
        <div class="field"><label for="password_confirmation">Confirm new password</label>
            <input class="input" type="password" id="password_confirmation" autocomplete="new-password" required></div>
        <button class="btn btn-primary" type="submit">Update password</button>
    </form>
</section>

<section class="step" data-step="4" hidden style="text-align:center">
    <div class="success-mark">&#10003;</div>
    <h1>Password updated</h1>
    <p class="sub">You can now sign in with your new password.</p>
    <a class="btn btn-primary" href="<?= e(url('login')) ?>" style="text-decoration:none">Sign in</a>
</section>
<p class="foot">Remembered it? <a href="<?= e(url('login')) ?>">Back to sign in</a></p>
<?php
$authContent = ob_get_clean();
require __DIR__ . '/_layout.php';
