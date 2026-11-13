<?php

namespace Rk\Threadwell\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Rk\Threadwell\Core\Config;
use RuntimeException;

class MailService
{
    /**
     * Generic HTML mail. Never throws: storefront flows must not fail
     * just because SMTP is not configured.
     */
    public function send(string $toEmail, string $subject, string $html): bool
    {
        if (!Config::get('mail.username') || !Config::get('mail.password')) {
            error_log("[mail skipped - SMTP not configured] to={$toEmail} subject={$subject}");

            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = Config::get('mail.host');
            $mail->SMTPAuth = true;
            $mail->Username = Config::get('mail.username');
            $mail->Password = Config::get('mail.password');
            $mail->SMTPSecure = Config::get('mail.encryption');
            $mail->Port = (int) Config::get('mail.port');
            $mail->setFrom(Config::get('mail.from_address'), Config::get('mail.from_name'));
            $mail->addAddress($toEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();

            return true;
        } catch (\Throwable $e) {
            error_log('[mail failed] ' . $e->getMessage());

            return false;
        }
    }

    public function sendOtp(
        string $toEmail,
        string $otp
    ): void {
        if (!Config::get('mail.username') && Config::get('app.env') === 'local') {
            error_log("[DEV OTP] to={$toEmail} code={$otp}"); // local development only (SMTP not configured)

            return;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();

            $mail->Host = Config::get(
                'mail.host'
            );

            $mail->SMTPAuth = true;

            $mail->Username = Config::get(
                'mail.username'
            );

            $mail->Password = Config::get(
                'mail.password'
            );

            $mail->SMTPSecure = Config::get(
                'mail.encryption'
            );

            $mail->Port = (int) Config::get(
                'mail.port'
            );

            $mail->CharSet = 'UTF-8';

            $mail->setFrom(
                Config::get('mail.from_address'),
                Config::get('mail.from_name')
            );

            $mail->addAddress($toEmail);

            $mail->isHTML(true);

            $mail->Subject =
                'Your Threadwell Verification Code';

            $mail->Body = $this->otpTemplate(
                $otp
            );

            $mail->AltBody =
                "Your Threadwell verification code is {$otp}. "
                . "This code will expire in 10 minutes.";

            $mail->send();

        } catch (Exception $e) {
    throw new RuntimeException(
        'Mail error: ' . $e->getMessage()
    );
}
    }


    public function sendPasswordResetOtp(
    string $toEmail,
    string $otp
): void {
        if (!Config::get('mail.username') && Config::get('app.env') === 'local') {
            error_log("[DEV OTP] to={$toEmail} code={$otp}"); // local development only (SMTP not configured)

            return;
        }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = Config::get('mail.host');
        $mail->SMTPAuth = true;
        $mail->Username = Config::get('mail.username');
        $mail->Password = Config::get('mail.password');
        $mail->SMTPSecure = Config::get('mail.encryption');
        $mail->Port = (int) Config::get('mail.port');
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            Config::get('mail.from_address'),
            Config::get('mail.from_name')
        );

        $mail->addAddress($toEmail);

        $mail->isHTML(true);

        $mail->Subject =
            'Reset Your Threadwell Password';

        $mail->Body = $this->passwordResetTemplate($otp);

        $mail->AltBody =
            "Your Threadwell password reset code is {$otp}. "
            . "This code will expire in 10 minutes.";

        $mail->send();

    } catch (Exception $e) {
        throw new RuntimeException(
            'Mail error: ' . $e->getMessage()
        );
    }
}

    private function otpTemplate(
        string $otp
    ): string {
        return "
            <div style=\"
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 0 auto;
                padding: 30px;
                background: #f8f9fa;
            \">

                <div style=\"
                    background: #ffffff;
                    padding: 30px;
                    border-radius: 10px;
                \">

                    <h2>
                        Welcome to Threadwell
                    </h2>

                    <p>
                        Your email verification code is:
                    </p>

                    <div style=\"
                        font-size: 32px;
                        font-weight: bold;
                        letter-spacing: 8px;
                        margin: 25px 0;
                    \">
                        {$otp}
                    </div>

                    <p>
                        This code will expire in
                        <strong>10 minutes</strong>.
                    </p>

                    <p>
                        If you did not request this code,
                        you can safely ignore this email.
                    </p>

                </div>

            </div>
        ";
    }

    private function passwordResetTemplate(
    string $otp
): string {
    return "
        <div style='font-family: Arial, sans-serif;'>
            <h2>Reset Your Password</h2>

            <p>
                Use the verification code below
                to reset your Threadwell password:
            </p>

            <h1
                style='
                    letter-spacing: 8px;
                    font-size: 32px;
                '
            >
                {$otp}
            </h1>

            <p>
                This code will expire in
                <strong>10 minutes</strong>.
            </p>

            <p>
                If you did not request a password reset,
                you can safely ignore this email.
            </p>
        </div>
    ";
}
}