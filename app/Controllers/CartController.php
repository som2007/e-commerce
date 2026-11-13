<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;

class CartController extends Controller
{
    public function page(Request $request): string
    {
        return $this->view('frontend/cart', [
            'title' => 'Your cart',
            'cart' => $this->cart()->summary(),
        ]);
    }

    public function state(Request $request): void
    {
        $this->json($this->payload());
    }

    public function add(Request $request): void
    {
        $variantId = (int) $request->input('variant_id', 0);
        $qty = max(1, (int) $request->input('qty', 1));

        if ($variantId < 1) {
            $this->json(['success' => false, 'message' => 'Please select a size.'], 422);

            return;
        }

        [$ok, $message] = $this->cart()->add($variantId, $qty);

        $this->json($this->payload($ok, $message), $ok ? 200 : 422);
    }

    public function update(Request $request): void
    {
        $this->cart()->setQty((int) $request->input('variant_id', 0), (int) $request->input('qty', 0));
        $this->json($this->payload());
    }

    public function remove(Request $request): void
    {
        $this->cart()->remove((int) $request->input('variant_id', 0));
        $this->json($this->payload());
    }

    private function payload(bool $ok = true, string $message = ''): array
    {
        $s = $this->cart()->summary();

        $lines = array_map(fn ($l) => [
            'variant_id' => (int) $l['variant_id'],
            'name' => $l['name'],
            'color' => $l['color'],
            'size' => $l['size'],
            'qty' => (int) $l['qty'],
            'max' => min(10, (int) $l['stock']),
            'unit' => money($l['unit']),
            'line_total' => money($l['line_total']),
            'mrp' => (float) $l['price'] > $l['unit'] ? money($l['price']) : null,
            'image' => product_image($l['image']),
            'url' => url('products/' . $l['slug']),
        ], $s['lines']);

        return [
            'success' => $ok,
            'message' => $message,
            'count' => $s['count'],
            'subtotal' => money($s['subtotal']),
            'savings' => $s['savings'] > 0 ? money($s['savings']) : null,
            'free_shipping_over' => config('store.free_shipping_over'),
            'free_ship_remaining' => max(0, config('store.free_shipping_over') - $s['subtotal']),
            'lines' => $lines,
        ];
    }
}
