<?php

namespace Rk\Threadwell\Services;

use Rk\Threadwell\Models\PasswordReset;
use Rk\Threadwell\Models\Role;
use Rk\Threadwell\Models\User;
use RuntimeException;

class AuthService
{
    public function __construct(
        private User $user,
        private Role $role,
        private OtpService $otpService,
        private MailService $mailService,
        private PasswordResetService $passwordResetService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTER
    |--------------------------------------------------------------------------
    */

    public function register(array $data): int
    {
        $email = strtolower(
            trim((string) $data['email'])
        );

        /*
         * Check whether email already exists.
         */
        $existingUser = $this->user->findByEmail($email);

        /*
         * Existing user found
         */
        if ($existingUser) {

            /*
             * Already verified account.
             * New registration is not allowed.
             */
            if (!empty($existingUser['email_verified_at'])) {
                throw new RuntimeException(
                    'An account with this email already exists.'
                );
            }

            /*
             * Existing account is NOT verified.
             *
             * Instead of creating duplicate account,
             * update the existing unverified account.
             */
            $passwordHash = password_hash(
                (string) $data['password'],
                PASSWORD_DEFAULT
            );

            $this->user->updateUnverifiedUser(
                (int) $existingUser['id'],
                [
                    'first_name' => trim(
                        (string) $data['first_name']
                    ),

                    'last_name' => trim(
                        (string) ($data['last_name'] ?? '')
                    ),

                    'phone' => trim(
                        (string) ($data['phone'] ?? '')
                    ),

                    'password' => $passwordHash,
                ]
            );

            /*
             * Generate a fresh email verification OTP.
             */
            $otp = $this->otpService->generate(
                (int) $existingUser['id'],
                $email
            );

            /*
             * Send fresh OTP.
             */
            $this->mailService->sendOtp(
                $email,
                $otp
            );

            return (int) $existingUser['id'];
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE NEW USER
        |--------------------------------------------------------------------------
        */

        $customerRole = $this->role->findByName('customer');

        if (!$customerRole) {
            throw new RuntimeException(
                'Customer role not found.'
            );
        }

        $passwordHash = password_hash(
            (string) $data['password'],
            PASSWORD_DEFAULT
        );

        $userId = $this->user->create([
            'role_id' => (int) $customerRole['id'],

            'first_name' => trim(
                (string) $data['first_name']
            ),

            'last_name' => trim(
                (string) ($data['last_name'] ?? '')
            ),

            'email' => $email,

            'phone' => trim(
                (string) ($data['phone'] ?? '')
            ),

            'password' => $passwordHash,

            'status' => 'active',
        ]);

        /*
         * Generate verification OTP.
         */
        $otp = $this->otpService->generate(
            $userId,
            $email
        );

        /*
         * Send verification OTP.
         */
        $this->mailService->sendOtp(
            $email,
            $otp
        );

        return $userId;
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function login(
        string $email,
        string $password
    ): array {

        $email = strtolower(
            trim($email)
        );

        $user = $this->user->findByEmail($email);

        if (!$user) {
            throw new RuntimeException(
                'Invalid email or password.'
            );
        }

        /*
         * Account status check.
         */
        if ($user['status'] !== 'active') {
            throw new RuntimeException(
                'Your account is not active.'
            );
        }

        /*
         * Email verification check.
         */
        if (empty($user['email_verified_at'])) {
            throw new RuntimeException(
                'Please verify your email before logging in.'
            );
        }

        /*
         * Password check.
         */
        if (!password_verify(
            $password,
            $user['password']
        )) {
            throw new RuntimeException(
                'Invalid email or password.'
            );
        }

        /*
         * Update last login.
         */
        $this->user->updateLastLogin(
            (int) $user['id']
        );

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY EMAIL
    |--------------------------------------------------------------------------
    */

    public function verifyEmail(
        int $userId,
        string $otp
    ): void {

        $user = $this->user->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'User not found.'
            );
        }

        /*
         * Already verified.
         */
        if (!empty($user['email_verified_at'])) {
            throw new RuntimeException(
                'Email is already verified.'
            );
        }

        /*
         * Verify OTP.
         */
        $this->otpService->verify(
            $userId,
            trim($otp)
        );

        /*
         * Mark email as verified.
         */
        $this->user->markEmailVerified(
            $userId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND EMAIL VERIFICATION OTP
    |--------------------------------------------------------------------------
    */

    public function resendVerificationOtp(
        int $userId
    ): void {

        $user = $this->user->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'User not found.'
            );
        }

        /*
         * Don't resend OTP after verification.
         */
        if (!empty($user['email_verified_at'])) {
            throw new RuntimeException(
                'Email is already verified.'
            );
        }

        /*
         * Generate a fresh OTP.
         *
         * OtpService handles the 60-second cooldown.
         */
        $otp = $this->otpService->resend(
            $userId,
            $user['email']
        );

        /*
         * Send fresh OTP.
         */
        $this->mailService->sendOtp(
            $user['email'],
            $otp
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD
    |--------------------------------------------------------------------------
    */

    public function forgotPassword(
        string $email
    ): void {

        $email = strtolower(
            trim($email)
        );

        $user = $this->user->findByEmail($email);

        /*
         * Don't send password reset OTP if:
         *
         * 1. User doesn't exist
         * 2. Email isn't verified
         */
        if (
            !$user ||
            empty($user['email_verified_at'])
        ) {
            return;
        }

        $otp = $this->passwordResetService->generate(
            (int) $user['id'],
            $user['email']
        );

        $this->mailService->sendPasswordResetOtp(
            $user['email'],
            $otp
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY PASSWORD RESET OTP
    |--------------------------------------------------------------------------
    */

    public function verifyPasswordResetOtp(
        int $userId,
        string $otp
    ): void {

        $user = $this->user->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'User not found.'
            );
        }

        /*
         * Password reset is only allowed
         * for verified accounts.
         */
        if (empty($user['email_verified_at'])) {
            throw new RuntimeException(
                'Please verify your email first.'
            );
        }

        $this->passwordResetService->verify(
            $userId,
            trim($otp)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESEND PASSWORD RESET OTP
    |--------------------------------------------------------------------------
    */

    public function resendPasswordResetOtp(
        int $userId
    ): void {

        $user = $this->user->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'User not found.'
            );
        }

        /*
         * Don't allow password reset for
         * unverified accounts.
         */
        if (empty($user['email_verified_at'])) {
            throw new RuntimeException(
                'Please verify your email first.'
            );
        }

        $otp = $this->passwordResetService->resend(
            $userId,
            $user['email']
        );

        $this->mailService->sendPasswordResetOtp(
            $user['email'],
            $otp
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD
    |--------------------------------------------------------------------------
    */

    public function resetPassword(
        int $userId,
        string $password
    ): void {

        $user = $this->user->findById($userId);

        if (!$user) {
            throw new RuntimeException(
                'User not found.'
            );
        }

        /*
         * Only verified users can reset password.
         */
        if (empty($user['email_verified_at'])) {
            throw new RuntimeException(
                'Please verify your email first.'
            );
        }

        /*
         * OTP must be verified first.
         */
        $reset =
            $this->passwordResetService
                ->getLatestVerified($userId);

        if (!$reset) {
            throw new RuntimeException(
                'Please verify the password reset OTP first.'
            );
        }

        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $this->user->updatePassword(
            $userId,
            $passwordHash
        );
    }
}