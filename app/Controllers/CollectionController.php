<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Models\Catalog;

class CollectionController extends Controller
{
    public function show(Request $request): string
    {
        $c = $this->catalog();
        $category = $c->category((string) $request->param('slug'));

        if (!$category) {
            abort(404);
        }

        $ids = $c->categoryIds((int) $category['id']);

        return $this->listing($request, $c, [
            'title' => $category['name'],
            'category' => $category,
            'breadcrumb' => $c->breadcrumb($category),
            'category_ids' => $ids,
            'base' => 'collections/' . $category['slug'],
            'description' => $category['description'] ?? '',
        ]);
    }

    /** /collections/all and /products */
    public function all(Request $request): string
    {
        return $this->listing($request, $this->catalog(), [
            'title' => 'All Products',
            'category' => null,
            'breadcrumb' => [],
            'category_ids' => [],
            'base' => 'collections/all',
            'description' => '',
        ]);
    }

    private function listing(Request $request, Catalog $c, array $ctx): string
    {
        $sort = (string) $request->query('sort_by', 'manual');
        if (!isset(Catalog::SORTS[$sort])) {
            $sort = 'manual';
        }

        $min = $request->query('min_price');
        $max = $request->query('max_price');
        $min = is_numeric($min) ? (float) $min : null;
        $max = is_numeric($max) ? (float) $max : null;
        $inStock = $request->query('in_stock') === '1';

        $result = $c->listProducts([
            'category_ids' => $ctx['category_ids'],
            'in_stock' => $inStock,
            'min' => $min,
            'max' => $max,
            'sort' => $sort,
            'page' => (int) $request->query('page', 1),
            'per_page' => 20,
        ]);

        return $this->view('frontend/collection', $ctx + $result + [
            'sort' => $sort,
            'sorts' => Catalog::SORTS,
            'inStock' => $inStock,
            'min' => $min,
            'max' => $max,
            'priceRange' => $c->priceRange($ctx['category_ids']),
            'query' => array_filter([
                'sort_by' => $sort !== 'manual' ? $sort : null,
                'in_stock' => $inStock ? '1' : null,
                'min_price' => $min,
                'max_price' => $max,
            ], fn ($v) => $v !== null),
        ]);
    }
}
