<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Database;
use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Core\Response;
use Rk\Threadwell\Core\Session;
use Rk\Threadwell\Models\EmailVerification;
use Rk\Threadwell\Models\PasswordReset;
use Rk\Threadwell\Models\Role;
use Rk\Threadwell\Models\User;
use Rk\Threadwell\Services\AuthService;
use Rk\Threadwell\Services\MailService;
use Rk\Threadwell\Services\OtpService;
use Rk\Threadwell\Services\PasswordResetService;
use Throwable;

class AuthController
{
    /*
    |--------------------------------------------------------------------------
    | Register Page
    |--------------------------------------------------------------------------
    */

    public function registerPage(Request $request): void
    {
        $viewPath =
            dirname(__DIR__) .
            '/Views/auth/register.php';

        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo 'Register page not found.';
            return;
        }

        require $viewPath;
    }


    /*
    |--------------------------------------------------------------------------
    | Register
    |--------------------------------------------------------------------------
    */

    public function register(Request $request): void
    {
        $response = new Response();

        try {

            $data = $request->json();

            $this->validateRegistration($data);

            $authService = $this->createAuthService();

            $userId = $authService->register($data);

            $response->json([
                'success' => true,
                'message' =>
                    'Account created successfully. Please verify your email.',
                'data' => [
                    'user_id' => $userId,
                ],
            ], 201);

        } catch (\InvalidArgumentException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {

            /*
             * Registration business errors such as:
             *
             * - verified email already exists
             * - customer role missing
             * - mail/OTP related controlled errors
             *
             * are validation/business errors, not server errors.
             */
            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {

            $response->json([
                'success' => false,
                'message' => 'Unable to create account. Please try again.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Email Page
    |--------------------------------------------------------------------------
    */

    public function verifyEmailPage(Request $request): void
    {
        $viewPath =
            dirname(__DIR__) .
            '/Views/auth/verify-email.php';

        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo 'Email verification page not found.';
            return;
        }

        $maskedEmail = null;
        $uid = (int) ($_GET['user_id'] ?? 0);
        if ($uid > 0) {
            $u = $this->createUserModel()->findById($uid);
            if ($u && !empty($u['email']) && str_contains($u['email'], '@')) {
                [$local, $domain] = explode('@', $u['email'], 2);
                $maskedEmail = mb_substr($local, 0, 1) . str_repeat('•', max(2, min(6, mb_strlen($local) - 1))) . '@' . $domain;
            }
        }

        require $viewPath;
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Email
    |--------------------------------------------------------------------------
    */

    public function verifyEmail(Request $request): void
    {
        $response = new Response();

        try {

            $data = $request->json();

            $userId =
                (int) ($data['user_id'] ?? 0);

            $otp =
                trim(
                    (string) ($data['otp'] ?? '')
                );

            if ($userId <= 0) {
                throw new \InvalidArgumentException(
                    'Invalid user ID.'
                );
            }

            if (!preg_match('/^\d{6}$/', $otp)) {
                throw new \InvalidArgumentException(
                    'OTP must be exactly 6 digits.'
                );
            }

            $authService =
                $this->createAuthService();

            $authService->verifyEmail(
                $userId,
                $otp
            );

            $response->json([
                'success' => true,
                'message' =>
                    'Email verified successfully.',
            ]);

        } catch (\InvalidArgumentException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {

            $response->json([
                'success' => false,
                'message' =>
                    'Unable to verify email. Please try again.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Resend Verification OTP
    |--------------------------------------------------------------------------
    */

    public function resendVerificationOtp(
        Request $request
    ): void {

        $response = new Response();

        try {

            $data = $request->json();

            $userId =
                (int) ($data['user_id'] ?? 0);

            if ($userId <= 0) {
                throw new \InvalidArgumentException(
                    'Invalid user ID.'
                );
            }

            $authService =
                $this->createAuthService();

            $authService->resendVerificationOtp(
                $userId
            );

            $response->json([
                'success' => true,
                'message' =>
                    'A new verification code has been sent.',
            ]);

        } catch (\InvalidArgumentException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {

            $response->json([
                'success' => false,
                'message' =>
                    'Unable to resend verification code.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Forgot Password Page
    |--------------------------------------------------------------------------
    */

    public function forgotPasswordPage(
        Request $request
    ): void {

        $viewPath =
            dirname(__DIR__) .
            '/Views/auth/forgot-password.php';

        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo 'Forgot password page not found.';
            return;
        }

        require $viewPath;
    }


    /*
    |--------------------------------------------------------------------------
    | Forgot Password
    |--------------------------------------------------------------------------
    */

    public function forgotPassword(
        Request $request
    ): void {

        $response = new Response();

        try {

            $data = $request->json();

            $email =
                strtolower(
                    trim(
                        (string) ($data['email'] ?? '')
                    )
                );

            if (
                $email === '' ||
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                throw new \InvalidArgumentException(
                    'Please enter a valid email address.'
                );
            }

            $authService =
                $this->createAuthService();

            $userModel =
                $this->createUserModel();

            $user =
                $userModel->findByEmail($email);

            $session = new Session();

            /*
             * Always clear any previous password-reset
             * session before starting a new request.
             */
            $session->remove(
                'password_reset_user_id'
            );

            $session->remove(
                'password_reset_verified'
            );

            /*
             * Only verified users can start
             * password reset.
             */
            if (
                $user &&
                !empty($user['email_verified_at'])
            ) {

                $authService->forgotPassword(
                    $email
                );

                $session->set(
                    'password_reset_user_id',
                    (int) $user['id']
                );

                $session->set(
                    'password_reset_verified',
                    false
                );
            }

            /*
             * Same response for:
             *
             * - unknown email
             * - unverified email
             * - verified email
             *
             * This prevents account enumeration.
             */
            $response->json([
                'success' => true,
                'message' =>
                    'If an account exists with this email, '
                    . 'a password reset code has been sent.',
            ]);

        } catch (\InvalidArgumentException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {

            $response->json([
                'success' => false,
                'message' =>
                    'Unable to process password reset request.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Verify Password Reset OTP
    |--------------------------------------------------------------------------
    */

    public function verifyPasswordResetOtp(
        Request $request
    ): void {

        $response = new Response();

        try {

            $data = $request->json();

            $otp =
                trim(
                    (string) ($data['otp'] ?? '')
                );

            if (!preg_match('/^\d{6}$/', $otp)) {
                throw new \InvalidArgumentException(
                    'OTP must be exactly 6 digits.'
                );
            }

            $session = new Session();

            $userId =
                (int) $session->get(
                    'password_reset_user_id',
                    0
                );

            if ($userId <= 0) {
                throw new \RuntimeException(
                    'Password reset session has expired. '
                    . 'Please request a new OTP.'
                );
            }

            $authService =
                $this->createAuthService();

            $authService->verifyPasswordResetOtp(
                $userId,
                $otp
            );

            $session->set(
                'password_reset_verified',
                true
            );

            $response->json([
                'success' => true,
                'message' =>
                    'Password reset OTP verified successfully.',
            ]);

        } catch (\InvalidArgumentException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {

            $response->json([
                'success' => false,
                'message' =>
                    'Unable to verify password reset OTP.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Resend Password Reset OTP
    |--------------------------------------------------------------------------
    */

    public function resendPasswordResetOtp(
        Request $request
    ): void {

        $response = new Response();

        try {

            $session = new Session();

            $userId =
                (int) $session->get(
                    'password_reset_user_id',
                    0
                );

            if ($userId <= 0) {
                throw new \InvalidArgumentException(
                    'Password reset session has expired.'
                );
            }

            $authService =
                $this->createAuthService();

            $authService->resendPasswordResetOtp(
                $userId
            );

            /*
             * New OTP invalidates previous verification state.
             */
            $session->set(
                'password_reset_verified',
                false
            );

            $response->json([
                'success' => true,
                'message' =>
                    'A new password reset code has been sent.',
            ]);

        } catch (\InvalidArgumentException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {

            $response->json([
                'success' => false,
                'message' =>
                    'Unable to resend password reset code.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Reset Password
    |--------------------------------------------------------------------------
    */

    public function resetPassword(
        Request $request
    ): void {

        $response = new Response();

        try {

            $data = $request->json();

            $password =
                (string) ($data['password'] ?? '');

            $passwordConfirmation =
                (string) (
                    $data['password_confirmation'] ?? ''
                );

            if (strlen($password) < 8) {
                throw new \InvalidArgumentException(
                    'Password must be at least 8 characters.'
                );
            }

            if (
                $password !==
                $passwordConfirmation
            ) {
                throw new \InvalidArgumentException(
                    'Passwords do not match.'
                );
            }

            $session = new Session();

            $userId =
                (int) $session->get(
                    'password_reset_user_id',
                    0
                );

            $verified =
                (bool) $session->get(
                    'password_reset_verified',
                    false
                );

            if ($userId <= 0) {
                throw new \RuntimeException(
                    'Password reset session has expired. '
                    . 'Please request a new OTP.'
                );
            }

            if (!$verified) {
                throw new \RuntimeException(
                    'Please verify the password reset OTP first.'
                );
            }

            $authService =
                $this->createAuthService();

            $authService->resetPassword(
                $userId,
                $password
            );

            /*
             * Password reset completed.
             */
            $session->remove(
                'password_reset_user_id'
            );

            $session->remove(
                'password_reset_verified'
            );

            $response->json([
                'success' => true,
                'message' =>
                    'Password reset successfully.',
            ]);

        } catch (\InvalidArgumentException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (Throwable $e) {

            $response->json([
                'success' => false,
                'message' =>
                    'Unable to reset password.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Login Page
    |--------------------------------------------------------------------------
    */

    public function loginPage(Request $request): void
    {
        $viewPath =
            dirname(__DIR__) .
            '/Views/auth/login.php';

        if (!file_exists($viewPath)) {
            http_response_code(404);
            echo 'Login page not found.';
            return;
        }

        require $viewPath;
    }


    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    public function login(Request $request): void
    {
        $response = new Response();

        try {

            $data = $request->json();

            if (
                empty($data['email']) ||
                empty($data['password'])
            ) {
                throw new \InvalidArgumentException(
                    'Email and password are required.'
                );
            }

            $authService =
                $this->createAuthService();

            $user = $authService->login(
                $data['email'],
                $data['password']
            );

            /*
             * Regenerate session after successful login
             * to prevent session fixation.
             */
            $session = new Session();

            $session->regenerate();

            $session->set(
                'user_id',
                (int) $user['id']
            );

            $session->set(
                'role_id',
                (int) $user['role_id']
            );

            $session->set(
                'authenticated',
                true
            );

            $response->json([
                'success' => true,
                'message' =>
                    'Login successful.',
                'data' => [
                    'user_id' =>
                        $user['id'],

                    'first_name' =>
                        $user['first_name'],

                    'last_name' =>
                        $user['last_name'],

                    'email' =>
                        $user['email'],
                ],
            ]);

        } catch (\InvalidArgumentException $e) {

            $response->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\RuntimeException $e) {

            $extra = [];

            if (str_contains($e->getMessage(), 'verify your email')) {
                $u = $this->createUserModel()->findByEmail(strtolower(trim((string) ($data['email'] ?? ''))));
                if ($u) {
                    $extra = ['unverified' => true, 'user_id' => (int) $u['id']];
                }
            }

            $response->json(['success' => false, 'message' => $e->getMessage()] + $extra, 401);

        } catch (Throwable $e) {

            $response->json([
                'success' => false,
                'message' =>
                    'Unable to login. Please try again.',
            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Current User
    |--------------------------------------------------------------------------
    */

    public function me(Request $request): void
    {
        $response = new Response();

        $session = new Session();

        $userId =
            (int) $session->get(
                'user_id',
                0
            );

        if ($userId <= 0) {

            $response->json([
                'success' => false,
                'message' =>
                    'Authentication required.',
            ], 401);

            return;
        }

        $userModel =
            $this->createUserModel();

        $user =
            $userModel->findById($userId);

        if (!$user) {

            $session->destroy();

            $response->json([
                'success' => false,
                'message' =>
                    'User account not found.',
            ], 401);

            return;
        }

        $response->json([
            'success' => true,
            'data' => [
                'id' =>
                    $user['id'],

                'first_name' =>
                    $user['first_name'],

                'last_name' =>
                    $user['last_name'],

                'email' =>
                    $user['email'],

                'phone' =>
                    $user['phone'],

                'role_id' =>
                    $user['role_id'],

                'status' =>
                    $user['status'],
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CSRF Token
    |--------------------------------------------------------------------------
    */

    public function csrf(Request $request): void
    {
        $csrf =
            new \Rk\Threadwell\Core\Csrf();

        $response = new Response();

        $response->json([
            'success' => true,
            'token' => $csrf->token(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request): void
    {
        $session = new Session();

        $session->destroy();

        $response = new Response();

        $response->json([
            'success' => true,
            'message' =>
                'Logout successful.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Registration Validation
    |--------------------------------------------------------------------------
    */

    private function validateRegistration(
        array $data
    ): void {

        $required = [
            'first_name',
            'email',
            'password',
        ];

        foreach ($required as $field) {

            if (
                !isset($data[$field]) ||
                trim(
                    (string) $data[$field]
                ) === ''
            ) {
                throw new \InvalidArgumentException(
                    ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            $field
                        )
                    ) . ' is required.'
                );
            }
        }

        if (
            !filter_var(
                $data['email'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new \InvalidArgumentException(
                'Please enter a valid email address.'
            );
        }

        if (
            isset($data['password_confirmation'])
            && $data['password_confirmation'] !== $data['password']
        ) {
            throw new \InvalidArgumentException(
                'Passwords do not match.'
            );
        }

        if (
            strlen(
                (string) $data['password']
            ) < 8
        ) {
            throw new \InvalidArgumentException(
                'Password must be at least 8 characters.'
            );
        }

        /*
         * If phone is supplied, normalize it.
         */
        if (isset($data['phone'])) {

            $data['phone'] =
                trim(
                    (string) $data['phone']
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create User Model
    |--------------------------------------------------------------------------
    */

    private function createUserModel(): User
    {
        $database =
            new Database();

        return new User(
            $database->connection()
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create Auth Service
    |--------------------------------------------------------------------------
    */

    private function createAuthService(): AuthService
    {
        $database =
            new Database();

        $db =
            $database->connection();

        $userModel =
            new User($db);

        $roleModel =
            new Role($db);

        $verificationModel =
            new EmailVerification($db);

        $otpService =
            new OtpService(
                $verificationModel
            );

        $passwordResetModel =
            new PasswordReset($db);

        $passwordResetService =
            new PasswordResetService(
                $passwordResetModel
            );

        $mailService =
            new MailService();

        return new AuthService(
            $userModel,
            $roleModel,
            $otpService,
            $mailService,
            $passwordResetService
        );
    }
}