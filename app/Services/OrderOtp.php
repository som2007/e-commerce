<?php

namespace Rk\Threadwell\Services;

/**
 * Email OTP that must be verified before an order is placed.
 * State lives in the session: the code is stored only as a keyed hash,
 * expires in 10 minutes, allows 5 wrong attempts and a resend every 30 seconds.
 */
class OrderOtp
{
    private const TTL = 600;
    private const VERIFIED_TTL = 900;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_AFTER = 30;
    private const MAX_SENDS_PER_HOUR = 6;

    private function key(): string
    {
        return hash('sha256', 'order-otp|' . (string) config('app.url') . '|' . session_id());
    }

    private function hash(string $email, string $code): string
    {
        return hash_hmac('sha256', strtolower($email) . '|' . $code, $this->key());
    }

    /** @return array{ok:bool,message:string,wait?:int,dev_otp?:string} */
    public function send(string $email): array
    {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Enter a valid email address first.'];
        }

        $s = session();
        $cur = $s->get('order_otp');
        $now = time();

        if ($cur && ($now - $cur['sent_at']) < self::RESEND_AFTER && $cur['email'] === $email) {
            return ['ok' => false, 'message' => 'Please wait before requesting another code.', 'wait' => self::RESEND_AFTER - ($now - $cur['sent_at'])];
        }

        $log = array_filter((array) $s->get('order_otp_log', []), fn ($t) => $t > $now - 3600);
        if (count($log) >= self::MAX_SENDS_PER_HOUR) {
            return ['ok' => false, 'message' => 'Too many codes requested. Try again in a while.'];
        }

        $code = (string) random_int(100000, 999999);
        $s->set('order_otp', [
            'email' => $email, 'hash' => $this->hash($email, $code),
            'exp' => $now + self::TTL, 'sent_at' => $now, 'attempts' => 0,
        ]);
        $log[] = $now;
        $s->set('order_otp_log', array_values($log));
        $s->remove('order_otp_ok');

        $brand = e(config('app.name'));
        $html = "<div style=\"font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;background:#f5ecd8\">"
            . "<div style=\"background:#fff;padding:28px;border-radius:12px\"><h2 style=\"margin-top:0\">{$brand} order confirmation</h2>"
            . "<p>Use this code to confirm your order:</p>"
            . "<div style=\"font-size:34px;font-weight:700;letter-spacing:8px;margin:22px 0;color:#3a0f1c\">{$code}</div>"
            . "<p>The code is valid for <strong>10 minutes</strong>. If you did not try to place an order, ignore this email.</p></div></div>";

        $sent = (new MailService())->send($email, "Your {$brand} order confirmation code", $html);

        $res = ['ok' => true, 'message' => $sent ? 'We have emailed a 6-digit code to ' . $email . '.' : 'Email is not configured on this server.'];

        if (!$sent) {
            if (config('app.env') === 'local') {
                $res['dev_otp'] = $code; // local development only, never in production
                $res['message'] = 'SMTP not configured - dev code shown below (local only).';
            } else {
                $s->remove('order_otp');

                return ['ok' => false, 'message' => 'We could not send the code right now. Please try again shortly.'];
            }
        }

        return $res;
    }

    /** @return array{ok:bool,message:string} */
    public function verify(string $email, string $code): array
    {
        $email = strtolower(trim($email));
        $s = session();
        $cur = $s->get('order_otp');

        if (!$cur || $cur['email'] !== $email) {
            return ['ok' => false, 'message' => 'Request a new code first.'];
        }

        if (time() > $cur['exp']) {
            $s->remove('order_otp');

            return ['ok' => false, 'message' => 'This code has expired. Request a new one.'];
        }

        if ($cur['attempts'] >= self::MAX_ATTEMPTS) {
            $s->remove('order_otp');

            return ['ok' => false, 'message' => 'Too many wrong attempts. Request a new code.'];
        }

        if (!preg_match('/^\d{6}$/', $code) || !hash_equals($cur['hash'], $this->hash($email, $code))) {
            $cur['attempts']++;
            $s->set('order_otp', $cur);

            return ['ok' => false, 'message' => 'Incorrect code. ' . max(0, self::MAX_ATTEMPTS - $cur['attempts']) . ' attempt(s) left.'];
        }

        $s->remove('order_otp');
        $s->set('order_otp_ok', ['email' => $email, 'exp' => time() + self::VERIFIED_TTL]);

        return ['ok' => true, 'message' => 'Email verified.'];
    }

    public function isVerified(string $email): bool
    {
        $ok = session()->get('order_otp_ok');

        return $ok && $ok['exp'] >= time() && $ok['email'] === strtolower(trim($email));
    }

    public function verifiedEmail(): string
    {
        $ok = session()->get('order_otp_ok');

        return ($ok && $ok['exp'] >= time()) ? (string) $ok['email'] : '';
    }

    public function consume(): void
    {
        session()->remove('order_otp_ok');
    }
}
