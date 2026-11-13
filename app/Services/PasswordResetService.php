<?php

namespace Rk\Threadwell\Services;

use Rk\Threadwell\Models\PasswordReset;
use RuntimeException;

class PasswordResetService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN = 60;

    public function __construct(
        private PasswordReset $passwordReset
    ) {
    }

    public function generate(
        int $userId,
        string $email
    ): string {

        $otp = str_pad(
            (string) random_int(0, 999999),
            self::OTP_LENGTH,
            '0',
            STR_PAD_LEFT
        );

        $otpHash = password_hash(
            $otp,
            PASSWORD_DEFAULT
        );

        $expiresAt = date(
            'Y-m-d H:i:s',
            time() + (
                self::OTP_EXPIRY_MINUTES * 60
            )
        );

        $this->passwordReset->create(
            $userId,
            $email,
            $otpHash,
            $expiresAt
        );

        return $otp;
    }

    public function verify(
        int $userId,
        string $otp
    ): bool {
        $otp = preg_replace('/\D/', '', $otp);
        $rows = $this->passwordReset->findRecentActive($userId);

        if (!$rows) {
            throw new RuntimeException('No active reset code. Please request a new one.');
        }

        $live = array_values(array_filter(
            $rows,
            fn ($r) => strtotime($r['expires_at']) >= time()
        ));

        if (!$live) {
            throw new RuntimeException('OTP has expired. Please request a new one.');
        }

        // Wrong guesses are counted on the newest code and block further tries.
        if ((int) $live[0]['attempts'] >= self::MAX_ATTEMPTS) {
            throw new RuntimeException('Too many wrong attempts. Please request a new OTP.');
        }

        foreach ($live as $row) {
            if (password_verify($otp, $row['otp_hash'])) {
                foreach ($rows as $r) {
                    $this->passwordReset->markVerified((int) $r['id']);
                }

                return true;
            }
        }

        $this->passwordReset->incrementAttempts((int) $live[0]['id']);
        $left = max(0, self::MAX_ATTEMPTS - (int) $live[0]['attempts'] - 1);

        throw new RuntimeException('Incorrect OTP. ' . $left . ' attempt(s) left.');
    }

    public function getLatestVerified(
    int $userId
): ?array {

    $reset =
        $this->passwordReset
            ->findLatestByUserId($userId);

    if (!$reset) {
        return null;
    }

    if (!$reset['verified_at']) {
        return null;
    }

    return $reset;
}

    public function resend(
        int $userId,
        string $email
    ): string {

        $lastCreatedAt =
            $this->passwordReset
                ->getLatestCreatedAt($userId);

        if ($lastCreatedAt) {

            $currentTime = strtotime(
                date('Y-m-d H:i:s')
            );

            $secondsPassed =
                $currentTime -
                strtotime($lastCreatedAt);

            if (
                $secondsPassed
                < self::RESEND_COOLDOWN
            ) {

                $remaining =
                    self::RESEND_COOLDOWN -
                    $secondsPassed;

                throw new RuntimeException(
                    "Please wait {$remaining} seconds "
                    . "before requesting a new OTP."
                );
            }
        }

        return $this->generate(
            $userId,
            $email
        );
    }
}