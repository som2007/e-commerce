<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;

class HomeController extends Controller
{
    public function index(Request $request): string
    {
        $c = $this->catalog();

        return $this->view('frontend/home', [
            'title' => 'Modern everyday clothing',
            'newArrivals' => $c->collectionRail('new-arrivals', 8),
            'winter' => $c->collectionRail('men-winter', 8),
            'bottoms' => $c->collectionRail('men-bottomwear', 8),
            'women' => $c->collectionRail('women', 8),
            'latest' => $c->collectionRail('men-tshirts', 6),
            'bestSellers' => $c->collectionRail('best-selling-products', 8, 'best-selling'),
        ]);
    }
}
