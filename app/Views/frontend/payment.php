<section class="container narrow section-sm center">
    <h1 class="page-title">Complete your payment</h1>
    <p class="muted">Order <strong><?= e($order['order_number']) ?></strong></p>
    <p class="price big"><span class="now"><?= money($order['total']) ?></span></p>

    <?php if ($rzp): ?>
        <button class="btn btn-dark btn-lg" id="rzpBtn" type="button">Pay securely with Razorpay</button>
        <p class="muted small">UPI, cards, net banking and wallets.</p>
        <script nonce="<?= csp_nonce() ?>" src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script nonce="<?= csp_nonce() ?>">
        (function () {
            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            var options = {
                key: <?= json_encode($rzpKey) ?>,
                amount: <?= (int) $rzp['amount'] ?>,
                currency: 'INR',
                name: <?= json_encode(config('app.name')) ?>,
                description: 'Order <?= e($order['order_number']) ?>',
                order_id: <?= json_encode($rzp['id']) ?>,
                prefill: {name: <?= json_encode($order['ship_name']) ?>, email: <?= json_encode($order['email']) ?>, contact: <?= json_encode($order['phone']) ?>},
                handler: function (resp) {
                    fetch(<?= json_encode(url('payment/' . $order['order_number'] . '/verify')) ?>, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                        body: JSON.stringify(resp)
                    }).then(function (r) { return r.json(); }).then(function (d) { window.location = d.redirect; });
                }
            };
            document.getElementById('rzpBtn').addEventListener('click', function () { new Razorpay(options).open(); });
        })();
        </script>
    <?php elseif ($testGateway): ?>
        <div class="test-gateway">
            <p class="tag">Test payment gateway</p>
            <p class="muted small">No Razorpay keys are configured, so this local test gateway simulates the payment.
                Add <code>RAZORPAY_KEY_ID</code> and <code>RAZORPAY_KEY_SECRET</code> to <code>.env</code> to go live.</p>
            <form method="post" action="<?= e(url('payment/' . $order['order_number'] . '/test')) ?>" class="cta-row">
                <?= csrf_field() ?>
                <button class="btn btn-dark btn-lg" name="result" value="success">Simulate successful payment</button>
                <button class="btn btn-outline btn-lg" name="result" value="fail">Simulate failure</button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-error">Online payment is temporarily unavailable. Please try again later.</div>
    <?php endif; ?>

    <?php if ($order['payment_status'] === 'failed'): ?>
        <p class="alert alert-error">Your last payment attempt failed. You can retry above.</p>
    <?php endif; ?>
</section>
