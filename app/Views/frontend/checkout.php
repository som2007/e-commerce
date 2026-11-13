<?php
$val = function (string $key, $fallback = '') use ($user, $address) {
    $o = old($key, null);
    if ($o !== null) {
        return $o;
    }
    $map = [
        'email' => $user['email'] ?? '',
        'name' => $address['name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        'phone' => $address['phone'] ?? ($user['phone'] ?? ''),
        'line1' => $address['line1'] ?? '', 'line2' => $address['line2'] ?? '',
        'city' => $address['city'] ?? '', 'state' => $address['state'] ?? '', 'pincode' => $address['pincode'] ?? '',
    ];

    return $map[$key] ?? $fallback;
};
?>
<section class="container section-sm">
    <h1 class="page-title">Checkout</h1>

    <form class="checkout" method="post" action="<?= e(url('checkout')) ?>" id="checkoutForm" data-verified="<?= e((new \Rk\Threadwell\Services\OrderOtp())->verifiedEmail()) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="checkout-main">
            <?php if (!$user): ?>
                
            <?php endif; ?>

            <fieldset class="form">
                <legend>Contact</legend>
                <label>Email <input type="email" name="email" value="<?= e($val('email')) ?>" required autocomplete="email"></label>
                <label>Mobile number <input type="tel" name="phone" value="<?= e($val('phone')) ?>" required pattern="[0-9+ ]{10,14}" autocomplete="tel" placeholder="10-digit mobile"></label>
            </fieldset>

            <fieldset class="form">
                <legend>Delivery address</legend>
                <button type="button" class="btn btn-light loc-btn" data-use-location>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-6.2 7-11.5A7 7 0 005 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                    Use my current location
                </button>
                <p class="muted small loc-hint">We will fill the address fields for you. You can edit them before ordering.</p>
                <label>Full name <input type="text" name="name" value="<?= e($val('name')) ?>" required autocomplete="name"></label>
                <label>Address <input type="text" name="line1" value="<?= e($val('line1')) ?>" required placeholder="House no., street, area" autocomplete="address-line1"></label>
                <label>Apartment, landmark (optional) <input type="text" name="line2" value="<?= e($val('line2')) ?>" autocomplete="address-line2"></label>
                <div class="form-row">
                    <label>City <input type="text" name="city" value="<?= e($val('city')) ?>" required autocomplete="address-level2"></label>
                    <label>Pincode <input type="text" name="pincode" value="<?= e($val('pincode')) ?>" required inputmode="numeric" maxlength="6" autocomplete="postal-code"></label>
                </div>
                <label>State
                    <select name="state" required>
                        <option value="">Select state</option>
                        <?php foreach ($states as $s): ?><option <?= $val('state') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label>Order notes (optional) <textarea name="notes" rows="2" maxlength="500"><?= e(old('notes')) ?></textarea></label>
            </fieldset>

            <fieldset class="form">
                <legend>Payment</legend>
                <label class="radio-card">
                    <input type="radio" name="payment_method" value="online" <?= $method === 'online' ? 'checked' : '' ?>>
                    <span><strong>Pay online - UPI, card, net banking</strong>
                        <em class="tag"><?= (int) config('store.prepaid_discount_percent') ?>% OFF</em></span>
                </label>
                <label class="radio-card">
                    <input type="radio" name="payment_method" value="cod" <?= $method === 'cod' ? 'checked' : '' ?>>
                    <span><strong>Cash on delivery</strong>
                        <small class="muted">Extra <?= money(config('store.cod_fee'), false) ?> handling fee</small></span>
                </label>
            </fieldset>

            <p class="muted small">For your security we will email a 6-digit code to confirm this order.</p>
            <button class="btn btn-dark btn-lg btn-block" type="submit" id="placeBtn">Place order</button>
        </div>

        <aside class="summary">
            <h3>Order summary</h3>
            <?php foreach ($cart['lines'] as $l): ?>
                <div class="mini-line">
                    <div class="mini-img"><img src="<?= e(product_image($l['image'])) ?>" alt="" width="56" height="70"><span class="mini-qty"><?= (int) $l['qty'] ?></span></div>
                    <div><strong><?= e($l['name']) ?></strong><p class="muted small">Size <?= e($l['size']) ?></p></div>
                    <span><?= money($l['line_total']) ?></span>
                </div>
            <?php endforeach; ?>

            <div class="coupon">
                <input type="text" name="coupon" id="couponInput" value="<?= e(old('coupon', $totals['coupon_applied'] ?? '')) ?>" placeholder="Coupon code" aria-label="Coupon code" autocomplete="off">
                <button class="btn btn-light btn-sm" type="button" id="couponBtn">Apply</button>
            </div>
            <p class="small" id="couponMsg"></p>

            <div class="row"><span>Subtotal</span><span id="sumSubtotal"><?= money($totals['subtotal']) ?></span></div>
            <div class="row save" id="rowPrepaid" <?= $totals['prepaid_discount'] > 0 ? '' : 'hidden' ?>><span>Online payment discount</span><span id="sumPrepaid">- <?= money($totals['prepaid_discount']) ?></span></div>
            <div class="row save" id="rowCoupon" <?= $totals['coupon_discount'] > 0 ? '' : 'hidden' ?>><span>Coupon</span><span id="sumCoupon">- <?= money($totals['coupon_discount']) ?></span></div>
            <div class="row"><span>Shipping</span><span id="sumShipping"><?= $totals['shipping'] > 0 ? money($totals['shipping']) : 'Free' ?></span></div>
            <div class="row" id="rowCod" <?= $totals['cod_fee'] > 0 ? '' : 'hidden' ?>><span>COD fee</span><span id="sumCod"><?= money($totals['cod_fee']) ?></span></div>
            <div class="row total"><span>Total</span><strong id="sumTotal"><?= money($totals['total']) ?></strong></div>
        </aside>
    </form>
</section>

<div class="modal" id="otpModal" hidden>
    <div class="modal-box otp-box" role="dialog" aria-modal="true" aria-labelledby="otpTitle">
        <button class="modal-close" type="button" data-close-otp aria-label="Close">&times;</button>
        <h3 id="otpTitle">Confirm your order</h3>
        <p class="muted" id="otpInfo">Enter the 6-digit code we emailed you.</p>
        <input type="text" id="otpInput" class="otp-input" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="------" aria-label="6-digit code">
        <p class="small" id="otpDev" hidden></p>
        <p class="small err" id="otpErr" role="alert"></p>
        <button class="btn btn-dark btn-block btn-lg" type="button" id="otpVerifyBtn">Verify &amp; place order</button>
        <p class="small center otp-resend"><span id="otpTimer"></span> <button class="link" type="button" id="otpResend" hidden>Resend code</button></p>
    </div>
</div>
