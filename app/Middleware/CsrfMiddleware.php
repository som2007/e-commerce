<?php

namespace Rk\Threadwell\Middleware;

use Rk\Threadwell\Core\Csrf;
use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Core\Response;

class CsrfMiddleware
{
    public function handle(Request $request): bool
    {
        if (
            $request->isMethod('GET') ||
            $request->isMethod('HEAD') ||
            $request->isMethod('OPTIONS')
        ) {
            return true;
        }

        // XHR sends a header, plain HTML forms send a hidden _token field.
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_token'] ?? null);

        if ((new Csrf())->validate($token)) {
            return true;
        }

        \Rk\Threadwell\Core\Security::log('csrf_failed', ['uri' => $request->uri()]);

        if ($request->wantsJson()) {
            (new Response())->json([
                'success' => false,
                'message' => 'Invalid or missing CSRF token.',
            ], 419);

            return false;
        }

        flash('error', 'Your session expired. Please try again.');
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        redirect($ref !== '' && same_host_url($ref) ? $ref : '/');
    }
}
