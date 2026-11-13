<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;

class ReviewController extends Controller
{
    public function store(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $product = $this->catalog()->product($slug);

        if (!$product) {
            abort(404);
        }

        $rating = (int) $request->input('rating', 0);
        $name = $this->str($request, 'name', 120);
        $title = $this->str($request, 'title', 180);
        $body = $this->str($request, 'body', 2000);

        $user = auth_user();
        if ($user && $name === '') {
            $name = trim($user['first_name'] . ' ' . ($user['last_name'] ?? ''));
        }

        if ($rating < 1 || $rating > 5 || $name === '' || $body === '') {
            flash('error', 'Please add a rating, your name and a few words about the product.');
            $this->remember($request->all());
            redirect('/products/' . $slug . '#reviews');
        }

        db()->prepare(
            "INSERT INTO reviews (product_id, user_id, name, rating, title, body, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        )->execute([$product['id'], $user['id'] ?? null, $name, $rating, $title, $body]);

        $this->forget();
        flash('success', 'Thanks! Your review will appear once it has been approved.');
        redirect('/products/' . $slug . '#reviews');
    }
}
