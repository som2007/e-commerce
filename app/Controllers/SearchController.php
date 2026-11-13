<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Models\Catalog;

class SearchController extends Controller
{
    public function index(Request $request): string
    {
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 80));
        $sort = (string) $request->query('sort_by', 'relevance');
        if (!isset(Catalog::SORTS[$sort])) {
            $sort = 'relevance';
        }

        $result = $q === ''
            ? ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1]
            : $this->catalog()->listProducts([
                'q' => $q,
                'sort' => $sort,
                'page' => (int) $request->query('page', 1),
                'per_page' => 20,
            ]);

        return $this->view('frontend/search', $result + [
            'title' => $q === '' ? 'Search' : "Search: {$q}",
            'q' => $q,
            'sort' => $sort,
            'sorts' => Catalog::SORTS,
        ]);
    }

    /** JSON for the live search dropdown. */
    public function suggest(Request $request): void
    {
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 60));

        if (mb_strlen($q) < 2) {
            $this->json(['success' => true, 'items' => []]);

            return;
        }

        $items = array_map(fn ($p) => [
            'name' => $p['name'],
            'url' => url('products/' . $p['slug']),
            'image' => product_image($p['image']),
            'price' => money($p['sale_price'] ?: $p['price']),
        ], $this->catalog()->suggest($q, 6));

        $this->json(['success' => true, 'items' => $items]);
    }
}
