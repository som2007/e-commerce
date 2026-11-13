<?php

namespace Rk\Threadwell\Middleware;
use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Core\Response;
use Rk\Threadwell\Core\Session;

class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        $session = new Session();

        if (!$session->get('authenticated', false)) {

            $response = new Response();

            $response->json([
                'success' => false,
                'message' => 'Authentication required.'
            ], 401);

            return false;
        }

        return true;
    }
}