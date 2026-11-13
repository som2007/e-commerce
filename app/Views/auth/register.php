<?php
$authPage = 'register';
$authTitle = 'Create account';
$authData = ['verify-url' => url('verify-email')];
ob_start();
?>
<h1>Create your account</h1>
<p class="sub">Join <?= e(config('app.name')) ?> for faster checkout and order tracking.</p>

<div class="alert" id="msg" role="alert" hidden></div>

<form id="registerForm" novalidate>
    <div class="row2">
        <div class="field"><label for="first_name">First name</label>
            <input class="input" type="text" id="first_name" autocomplete="given-name" maxlength="80" required autofocus></div>
        <div class="field"><label for="last_name">Last name</label>
            <input class="input" type="text" id="last_name" autocomplete="family-name" maxlength="80"></div>
    </div>
    <div class="field"><label for="email">Email address</label>
        <input class="input" type="email" id="email" placeholder="you@example.com" autocomplete="email" required></div>
    <div class="field"><label for="phone">Mobile number <span style="text-transform:none;font-weight:500">(optional)</span></label>
        <input class="input" type="tel" id="phone" placeholder="10-digit mobile" autocomplete="tel" maxlength="15"></div>
    <div class="field"><label for="password">Password</label>
        <div class="pw">
            <input class="input" type="password" id="password" placeholder="At least 8 characters" autocomplete="new-password" minlength="8" required>
            <button type="button" data-toggle-pw aria-label="Show password">Show</button>
        </div>
        <div class="meter"><i id="meter"></i></div>
        <div class="hint" id="meterText">Use letters, numbers and a symbol for a strong password.</div>
    </div>
    <div class="field"><label for="password_confirmation">Confirm password</label>
        <input class="input" type="password" id="password_confirmation" placeholder="Re-enter password" autocomplete="new-password" required></div>
    <button class="btn btn-primary" type="submit" id="submitBtn">Create account</button>
</form>
<p class="foot">Already have an account? <a href="<?= e(url('login')) ?>">Sign in</a></p>
<?php
$authContent = ob_get_clean();
require __DIR__ . '/_layout.php';
