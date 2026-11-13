<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Services\MailService;
use Rk\Threadwell\Services\OrderService;
use Rk\Threadwell\Services\PaymentService;

class CheckoutController extends Controller
{
    private function orders(): OrderService
    {
        return new OrderService(db());
    }

    public function show(Request $request): string
    {
        $cart = $this->cart()->summary();

        if (!$cart['lines']) {
            flash('error', 'Your cart is empty.');
            redirect('/cart');
        }

        $user = auth_user();
        $address = null;

        if ($user) {
            $st = db()->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1');
            $st->execute([$user['id']]);
            $address = $st->fetch() ?: null;
        }

        $method = old('payment_method', 'online');

        return $this->view('frontend/checkout', [
            'title' => 'Checkout',
            'cart' => $cart,
            'user' => $user,
            'address' => $address,
            'totals' => $this->orders()->totals($cart['subtotal'], $method === 'cod' ? 'cod' : 'online', old('coupon') ?: null),
            'method' => $method === 'cod' ? 'cod' : 'online',
            'states' => self::STATES,
        ]);
    }

    /** JSON: recalculates totals when payment method / coupon changes. */
    public function quote(Request $request): void
    {
        $cart = $this->cart()->summary();
        $method = $request->input('payment_method') === 'cod' ? 'cod' : 'online';
        $coupon = trim((string) $request->input('coupon', ''));

        $t = $this->orders()->totals($cart['subtotal'], $method, $coupon ?: null);

        $this->json([
            'success' => true,
            'coupon_error' => $t['coupon_error'],
            'coupon_applied' => $t['coupon_applied'],
            'rows' => [
                'subtotal' => money($t['subtotal']),
                'prepaid' => $t['prepaid_discount'] > 0 ? '- ' . money($t['prepaid_discount']) : null,
                'coupon' => $t['coupon_discount'] > 0 ? '- ' . money($t['coupon_discount']) : null,
                'shipping' => $t['shipping'] > 0 ? money($t['shipping']) : 'Free',
                'cod_fee' => $t['cod_fee'] > 0 ? money($t['cod_fee']) : null,
                'total' => money($t['total']),
            ],
        ]);
    }

    /** JSON: emails a 6-digit confirmation code. */
    public function otpSend(Request $request): void
    {
        $res = (new \Rk\Threadwell\Services\OrderOtp())->send((string) $request->input('email', ''));
        \Rk\Threadwell\Core\Security::log('order_otp_send', ['ok' => $res['ok']]);
        $this->json(['success' => $res['ok']] + $res, $res['ok'] ? 200 : 422);
    }

    /** JSON: checks the code and marks the email as verified for this session. */
    public function otpVerify(Request $request): void
    {
        $res = (new \Rk\Threadwell\Services\OrderOtp())->verify((string) $request->input('email', ''), trim((string) $request->input('code', '')));
        \Rk\Threadwell\Core\Security::log('order_otp_verify', ['ok' => $res['ok']]);
        $this->json(['success' => $res['ok']] + $res, $res['ok'] ? 200 : 422);
    }

    public function place(Request $request): void
    {
        $cart = $this->cart()->summary();

        if (!$cart['lines']) {
            redirect('/cart');
        }

        $data = [
            'email' => strtolower($this->str($request, 'email', 191)),
            'name' => $this->str($request, 'name', 150),
            'phone' => preg_replace('/\D/', '', $this->str($request, 'phone', 20)),
            'line1' => $this->str($request, 'line1'),
            'line2' => $this->str($request, 'line2'),
            'city' => $this->str($request, 'city', 100),
            'state' => $this->str($request, 'state', 100),
            'pincode' => preg_replace('/\D/', '', $this->str($request, 'pincode', 10)),
            'notes' => $this->str($request, 'notes', 500),
        ];
        $method = $request->input('payment_method') === 'cod' ? 'cod' : 'online';
        $coupon = trim((string) $request->input('coupon', '')) ?: null;

        $errors = [];
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (mb_strlen($data['name']) < 2) {
            $errors[] = 'Enter your full name.';
        }
        if (!preg_match('/^(?:91)?[6-9]\d{9}$/', $data['phone'])) {
            $errors[] = 'Enter a valid 10-digit mobile number.';
        }
        if (mb_strlen($data['line1']) < 5 || $data['city'] === '' || $data['state'] === '') {
            $errors[] = 'Enter your complete delivery address.';
        }
        if (!preg_match('/^[1-9]\d{5}$/', $data['pincode'])) {
            $errors[] = 'Enter a valid 6-digit pincode.';
        }
        $data['phone'] = substr($data['phone'], -10);

        $otp = new \Rk\Threadwell\Services\OrderOtp();
        if (!$errors && !$otp->isVerified($data['email'])) {
            $errors[] = 'Please verify the OTP sent to your email before placing the order.';
        }

        $orders = $this->orders();

        if (!$errors) {
            [, $couponError] = $orders->couponDiscount($coupon, (float) $cart['subtotal'], $method);
            if ($couponError) {
                $errors[] = $couponError;
            }
        }

        if ($errors) {
            flash('errors', $errors);
            $this->remember($request->all());
            redirect('/checkout');
        }

        try {
            $order = $orders->place($cart, $data, $method, $coupon, auth_id());
        } catch (\RuntimeException $e) {
            flash('errors', [$e->getMessage()]);
            $this->remember($request->all());
            redirect('/checkout');
        }

        $otp->consume();
        $this->forget();
        $this->cart()->clear();

        // Remember which orders this browser session may view (guest checkout).
        $mine = session()->get('my_orders', []);
        $mine[] = $order['order_number'];
        session()->set('my_orders', $mine);

        if (auth_id() && $method) {
            $this->saveAddressIfNew((int) auth_id(), $data);
        }

        if ($method === 'cod') {
            $this->confirmationMail($order);
            redirect('/order/success/' . $order['order_number']);
        }

        redirect('/payment/' . $order['order_number']);
    }

    private function saveAddressIfNew(int $userId, array $d): void
    {
        $st = db()->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = ? AND line1 = ? AND pincode = ?');
        $st->execute([$userId, $d['line1'], $d['pincode']]);

        if ((int) $st->fetchColumn() === 0) {
            db()->prepare(
                'INSERT INTO addresses (user_id, name, phone, line1, line2, city, state, pincode, is_default)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)'
            )->execute([$userId, $d['name'], $d['phone'], $d['line1'], $d['line2'] ?: null, $d['city'], $d['state'], $d['pincode']]);
        }
    }

    /* ----------------------------------------------------------- payment */

    private function ownOrder(string $number): array
    {
        $order = $this->orders()->find($number);

        if (!$order) {
            abort(404);
        }

        $allowed = ($order['user_id'] && (int) $order['user_id'] === auth_id()) || is_admin();

        if (!$allowed) {
            abort(404);
        }

        return $order;
    }

    public function payment(Request $request): string
    {
        $order = $this->ownOrder((string) $request->param('number'));

        if ($order['payment_method'] !== 'online' || $order['payment_status'] === 'paid') {
            redirect('/order/success/' . $order['order_number']);
        }

        $pay = new PaymentService();
        $rzp = null;

        if ($pay->usesRazorpay()) {
            try {
                $rzp = $pay->createRazorpayOrder($order);
            } catch (\RuntimeException $e) {
                flash('error', $e->getMessage());
            }
        }

        return $this->view('frontend/payment', [
            'title' => 'Complete payment',
            'order' => $order,
            'rzp' => $rzp,
            'rzpKey' => config('payment.razorpay_key'),
            'testGateway' => $pay->testGatewayEnabled(),
        ]);
    }

    /** Razorpay callback (fetch from checkout handler). */
    public function verify(Request $request): void
    {
        $order = $this->ownOrder((string) $request->param('number'));
        $pay = new PaymentService();

        $ok = $pay->usesRazorpay() && $pay->verifyRazorpay(
            (string) $request->input('razorpay_order_id'),
            (string) $request->input('razorpay_payment_id'),
            (string) $request->input('razorpay_signature')
        );

        if ($ok) {
            $this->orders()->markPaid($order['order_number'], (string) $request->input('razorpay_payment_id'));
            $this->confirmationMail($this->orders()->find($order['order_number']));
        } else {
            $this->orders()->markPaymentFailed($order['order_number']);
        }

        $this->json([
            'success' => $ok,
            'redirect' => url($ok ? '/order/success/' . $order['order_number'] : '/payment/' . $order['order_number']),
        ], $ok ? 200 : 422);
    }

    /** Local test gateway (never in production, never alongside real keys). */
    public function testPay(Request $request): void
    {
        $order = $this->ownOrder((string) $request->param('number'));

        if (!(new PaymentService())->testGatewayEnabled()) {
            abort(404);
        }

        if ($request->input('result') === 'success') {
            $this->orders()->markPaid($order['order_number'], 'TEST-' . strtoupper(bin2hex(random_bytes(4))));
            $this->confirmationMail($this->orders()->find($order['order_number']));
            redirect('/order/success/' . $order['order_number']);
        }

        $this->orders()->markPaymentFailed($order['order_number']);
        flash('error', 'Payment failed. You can try again or choose cash on delivery.');
        redirect('/payment/' . $order['order_number']);
    }

    public function success(Request $request): string
    {
        $order = $this->ownOrder((string) $request->param('number'));
        $orders = $this->orders();

        return $this->view('frontend/order-success', [
            'title' => 'Order confirmed',
            'order' => $order,
            'items' => $orders->items((int) $order['id']),
        ]);
    }

    private function confirmationMail(?array $order): void
    {
        if (!$order) {
            return;
        }

        $rows = '';
        foreach ($this->orders()->items((int) $order['id']) as $i) {
            $rows .= '<tr><td>' . e($i['name']) . ' (' . e($i['size']) . ') x ' . (int) $i['qty'] . '</td><td align="right">'
                . money($i['price'] * $i['qty']) . '</td></tr>';
        }

        $html = '<div style="font-family:Arial,sans-serif;max-width:560px"><h2>Thank you for your order!</h2>'
            . '<p>Order <strong>' . e($order['order_number']) . '</strong> has been placed.</p>'
            . '<table width="100%" cellpadding="6" style="border-top:1px solid #ddd">' . $rows . '</table>'
            . '<p><strong>Total: ' . money($order['total']) . '</strong> (' . ($order['payment_method'] === 'cod' ? 'Cash on delivery' : 'Paid online') . ')</p>'
            . '<p>Track your order any time at ' . e(url('/track-order')) . '</p></div>';

        (new MailService())->send($order['email'], 'Order confirmed - ' . $order['order_number'], $html);
    }

    public const STATES = [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Delhi', 'Goa', 'Gujarat',
        'Haryana', 'Himachal Pradesh', 'Jammu and Kashmir', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh',
        'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim',
        'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Chandigarh',
        'Puducherry', 'Ladakh',
    ];
}
