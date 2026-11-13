<?php

namespace Rk\Threadwell\Services;

use RuntimeException;

/**
 * Online payments.
 *  - With RAZORPAY_KEY_ID / RAZORPAY_KEY_SECRET set: real Razorpay Standard Checkout.
 *  - Without keys (local dev): a built-in test gateway so the whole flow can be tried.
 */
class PaymentService
{
    public function usesRazorpay(): bool
    {
        return config('payment.razorpay_key') && config('payment.razorpay_secret');
    }

    public function createRazorpayOrder(array $order): array
    {
        $payload = json_encode([
            'amount' => (int) round($order['total'] * 100),
            'currency' => 'INR',
            'receipt' => $order['order_number'],
        ]);

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_USERPWD => config('payment.razorpay_key') . ':' . config('payment.razorpay_secret'),
        ]);

        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $body, true);

        if ($code >= 300 || empty($data['id'])) {
            throw new RuntimeException('Could not start the payment. Please try again.');
        }

        return $data;
    }

    public function verifyRazorpay(string $rzpOrderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $rzpOrderId . '|' . $paymentId, (string) config('payment.razorpay_secret'));

        return hash_equals($expected, $signature);
    }

    /** The built-in test gateway is only available outside production. */
    public function testGatewayEnabled(): bool
    {
        return !$this->usesRazorpay() && config('app.env') !== 'production';
    }
}
