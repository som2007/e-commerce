<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;

/** CMS pages, policies and blog. */
class ContentController extends Controller
{
    public function page(Request $request): string
    {
        $st = db()->prepare('SELECT * FROM pages WHERE slug = ?');
        $st->execute([(string) $request->param('slug')]);
        $page = $st->fetch();

        if (!$page) {
            abort(404);
        }

        return $this->view('frontend/page', ['title' => $page['title'], 'page' => $page]);
    }

    /** /policies/privacy-policy -> pages.privacy-policy */
    public function policy(Request $request): string
    {
        $request->setParams(['slug' => $request->param('slug')]);

        return $this->page($request);
    }

    public function blog(Request $request): string
    {
        $posts = db()->query(
            'SELECT id, slug, title, excerpt, created_at FROM blog_posts WHERE is_published = 1 ORDER BY created_at DESC, id DESC'
        )->fetchAll();

        return $this->view('frontend/blog', ['title' => 'News & Style Guides', 'posts' => $posts]);
    }

    public function post(Request $request): string
    {
        $st = db()->prepare('SELECT * FROM blog_posts WHERE slug = ? AND is_published = 1');
        $st->execute([(string) $request->param('slug')]);
        $post = $st->fetch();

        if (!$post) {
            abort(404);
        }

        return $this->view('frontend/post', ['title' => $post['title'], 'post' => $post]);
    }
}
