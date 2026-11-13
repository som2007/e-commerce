<?php

namespace Rk\Threadwell\Middleware;

use Rk\Threadwell\Core\Request;

class AdminMiddleware
{
    public function handle(Request $request): bool
    {
        if (is_admin()) {
            if (!$request->isMethod('GET')) {
                \Rk\Threadwell\Core\Security::log('admin_action', ['method' => $request->method(), 'uri' => $request->uri()]);
            }

            return true;
        }

        // Guests and customers get an ordinary 404, so the admin area is not advertised.
        \Rk\Threadwell\Core\Security::log('admin_denied', ['uri' => $request->uri()]);
        abort(404);
    }
}
