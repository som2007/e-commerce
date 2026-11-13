<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;

class ProductController extends Controller
{
    /** Old /products listing now points at the "all" collection. */
    public function index(Request $request): void
    {
        redirect('/collections/all', 301);
    }

    public function show(Request $request): string
    {
        $c = $this->catalog();
        $product = $c->product((string) $request->param('slug'));

        if (!$product) {
            abort(404);
        }

        $pid = (int) $product['id'];
        $variants = $c->variants($pid);

        $sizeMin = $variants ? min(array_map(fn ($v) => (float) ($v['sale_price'] ?: $v['price']), $variants)) : (float) $product['price'];

        return $this->view('frontend/product', [
            'title' => $product['name'],
            'metaDescription' => $product['short_description'],
            'product' => $product,
            'variants' => $variants,
            'images' => $c->images($pid),
            'siblings' => $product['group_key'] ? $c->siblings($product['group_key']) : [],
            'related' => $c->related($product, 8),
            'reviews' => $c->reviews($pid),
            'breakdown' => $c->ratingBreakdown($pid),
            'fromPrice' => $sizeMin,
            'sizeChart' => ($variants && ctype_digit((string) $variants[0]['size'])) ? $this->bottomChart() : $this->sizeChart(),
        ]);
    }

    private function sizeChart(): ?string
    {
        $st = db()->prepare("SELECT body FROM pages WHERE slug = 'size-chart'");
        $st->execute();

        return $st->fetchColumn() ?: null;
    }

    private function bottomChart(): string
    {
        $rows = [['28', '28', '38', '40'], ['30', '30', '40', '41'], ['32', '32', '42', '42'], ['34', '34', '44', '43'],
            ['36', '36', '46', '44'], ['38', '38', '48', '45'], ['40', '40', '50', '46']];
        $html = '<p class="muted small">Garment measurements in inches. Pick your waist size.</p><table class="spec"><tr><th>Size</th><th>Waist</th><th>Hip</th><th>Length</th></tr>';

        foreach ($rows as [$sz, $w, $h, $l]) {
            $html .= "<tr><td>{$sz}</td><td>{$w}</td><td>{$h}</td><td>{$l}</td></tr>";
        }

        return $html . '</table>';
    }
}
