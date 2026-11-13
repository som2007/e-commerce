<?php

namespace Rk\Threadwell\Middleware;

use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Core\Response;

/** Page-level guard: send guests to /login (JSON requests get a 401 instead). */
class CustomerMiddleware
{
    public function handle(Request $request): bool
    {
        if (auth_id()) {
            return true;
        }

        if ($request->wantsJson()) {
            (new Response())->json(['success' => false, 'message' => 'Please log in to continue.', 'login' => true], 401);

            return false;
        }

        if ($request->isMethod('GET')) {
            session()->set('intended', $request->uri());
        }

        flash('info', 'Please log in or create an account to place an order.');
        redirect('/login');
    }
}
