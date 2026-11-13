<?php

/**
 * Seeds the category tree (mega-menu structure), demo products with colour
 * + size variants, generated product imagery and sample reviews.
 * Idempotent: safe to run many times.
 */
class CatalogSeeder
{
    private PDO $db;

    /** slug => [name, parentSlug|null, sort] */
    private array $categories = [
        'men' => ['Men', null, 1],
        'women' => ['Women', null, 2],
        'trending' => ['Trending Now', null, 3],

        'men-formal' => ['Formal', 'men', 1],
        'men-half-sleeve' => ['Half Sleeve Shirt', 'men', 2],
        'men-casual' => ['Casual', 'men', 3],
        'men-club-wear' => ['Club Wear', 'men', 4],

        'formal-shirts-for-men' => ['Formal Shirts For Men', 'men-formal', 1],
        'satin-formal-shirts' => ['Cotton Satin Formal Shirts', 'men-formal', 2],
        'satin-lycra-formal-shirts' => ['Anti Wrinkle Satin Lycra Shirts', 'men-formal', 3],
        'formal-partywear' => ['Formal Partywear (Lycra)', 'men-formal', 4],
        'single-pocket-formal-shirts' => ['Single Pocket Formal Shirts', 'men-formal', 5],
        'plus-size-shirts' => ['Plus Size Shirts', 'men-formal', 6],

        'half-shirts' => ['All Half Sleeve Shirts', 'men-half-sleeve', 1],
        'popcorn-half-shirts' => ['Popcorn Half Shirts', 'men-half-sleeve', 2],
        'satin-half-shirts' => ['Satin Half Shirts', 'men-half-sleeve', 3],
        'self-cord-half-shirts' => ['Self Cord Half Shirts', 'men-half-sleeve', 4],

        'casual-shirts' => ['All Casual Shirts', 'men-casual', 1],
        'linen-shirts' => ['Linen Shirts', 'men-casual', 2],
        'self-texture-shirts' => ['Self Texture Shirts', 'men-casual', 3],

        'club-wear' => ['All Club Wear', 'men-club-wear', 1],
        'club-wear-tiger' => ['Tiger Print Club Shirts', 'men-club-wear', 2],
        'club-wear-floral' => ['Floral Embroidery Shirts', 'men-club-wear', 3],

        'filafil-shirts' => ['Filafil Shirts', 'men-formal', 7],
        'signature-contrast-collection' => ['Signature Contrast Collection', 'men-formal', 8],
        'mini-stripe-check-shirts' => ['Mini Stripe & Check Shirts', 'men-formal', 9],
        'fine-ribbed-formal-shirt' => ['Fine Ribbed Formal Shirts', 'men-formal', 10],
        'mens-formal-self-striped-shirts' => ['Formal Self Striped Shirts', 'men-formal', 11],

        'bandana-printed-half-shirts' => ['Bandana Print Half Shirts', 'men-half-sleeve', 5],
        'net-half-shirts' => ['Net Half Shirts', 'men-half-sleeve', 6],
        'round-neck-t-shirts' => ['Round Neck T-Shirts', 'men-half-sleeve', 7],
        'half-formal-shirts-for-men' => ['Half Formal Shirts', 'men-half-sleeve', 8],
        'shorts-for-men' => ['Shorts For Men', 'men-half-sleeve', 9],
        'crush-half-shirts' => ['Crush Half Shirts', 'men-half-sleeve', 10],
        'half-co-ord-cord-sets-for-men' => ['Half Co-ord Sets For Men', 'men-half-sleeve', 11],

        'flower-leaf-embroidery-shirts' => ['Flower & Leaf Embroidery Shirts', 'men-casual', 4],
        'embroidered-linen' => ['Embroidered Linen Shirts', 'men-casual', 5],
        't-neck-shirt' => ['T-Neck Shirts', 'men-casual', 6],
        'corduroy-shirts' => ['Corduroy Shirts', 'men-casual', 7],

        'club-wear-lion' => ['Lion Print Club Shirts', 'men-club-wear', 4],
        'crush-with-embroidery' => ['Crush With Embroidery', 'men-club-wear', 5],
        'club-wear-butterfly' => ['Butterfly Club Shirts', 'men-club-wear', 6],
        'hunter-shirt' => ['Hunter Shirts', 'men-club-wear', 7],
        'club-wear-owl' => ['Owl Print Club Shirts', 'men-club-wear', 8],
        'club-wear-deer' => ['Deer Print Club Shirts', 'men-club-wear', 9],
        'metal-clip-s' => ['Metal Clip Shirts', 'men-club-wear', 10],
        'metal-clip-stone' => ['Metal Clip Stone Shirts', 'men-club-wear', 11],
        'rhinestone-embroidery-party-shirts' => ['Rhinestone Party Shirts', 'men-club-wear', 12],
        'club-wear-flower' => ['Flower Club Shirts', 'men-club-wear', 13],

        'men-tshirts' => ['T-Shirts & Polos', 'men', 5],
        'men-bottomwear' => ['Bottomwear', 'men', 6],
        'men-winter' => ['Winterwear & Jackets', 'men', 7],
        'men-ethnic' => ['Ethnic Wear', 'men', 8],

        'polo-t-shirts' => ['Polo T-Shirts', 'men-tshirts', 1],
        'oversized-t-shirts' => ['Oversized T-Shirts', 'men-tshirts', 2],
        'premium-t-shirts' => ['Premium Cotton T-Shirts', 'men-tshirts', 3],
        'jeans-for-men' => ['Jeans For Men', 'men-bottomwear', 1],
        'formal-trousers' => ['Formal Trousers', 'men-bottomwear', 2],
        'chinos-for-men' => ['Chinos For Men', 'men-bottomwear', 3],
        'joggers-for-men' => ['Joggers & Track Pants', 'men-bottomwear', 4],
        'hoodies-for-men' => ['Hoodies', 'men-winter', 1],
        'sweatshirts-for-men' => ['Sweatshirts', 'men-winter', 2],
        'jackets-for-men' => ['Bomber & Denim Jackets', 'men-winter', 3],
        'blazers-for-men' => ['Blazers', 'men-winter', 4],
        'kurtas-for-men' => ['Kurtas For Men', 'men-ethnic', 1],
        'kurta-pyjama-sets' => ['Kurta Pyjama Sets', 'men-ethnic', 2],

        'co-ord-sets-women' => ['Co-ord Sets', 'women', 1],
        'women-kurta-set' => ['Kurta Sets', 'women', 2],
        'women-dresses' => ['Dresses', 'women', 3],
        'women-tops' => ['Tops & T-Shirts', 'women', 4],
        'women-bottomwear' => ['Trousers & Palazzos', 'women', 5],
        'women-hoodies' => ['Hoodies & Sweatshirts', 'women', 6],

        'new-arrivals' => ['New Arrivals', 'trending', 1],
        'best-selling-products' => ['Best Selling Products', 'trending', 2],
    ];

    private array $colors = [
        'White' => 'f4f4f2', 'Sky Blue' => '8ec5e8', 'Cream' => 'efe3c8', 'Light Pink' => 'f2b8c6',
        'Light Grey' => 'bfc3c7', 'Navy Blue' => '1f2d55', 'Black' => '161616', 'Wine' => '6e1f33',
        'Royal Blue' => '2650b5', 'Teal Blue' => '1f7a8c', 'Dark Olive' => '5b6236', 'Maroon' => '7a1b1b',
        'Coffee Brown' => '5a3a29', 'Beige' => 'd8c3a5', 'Orange' => 'e07b2f', 'Yellow' => 'f0cf3a',
        'Pista Green' => 'b8d8a0', 'Sage Green' => '9caf88', 'Mustard' => 'd9a21b', 'Charcoal' => '3a3d42', 'Khaki' => 'b59f76', 'Denim Blue' => '4a6fa5', 'Indigo' => '27386b',
        'Rust' => 'b5502e', 'Stone' => 'cfc7b8', 'Mint' => 'bfe3d0', 'Peach' => 'f6c6a8', 'Burgundy' => '5b1428', 'Ice Blue' => 'cfe3f0',
        'Forest Green' => '2e5a43', 'Off White' => 'f1ece1', 'Pale Gold' => 'd9c27a', 'Lavender' => 'b9a9d6',
    ];

    /**
     * group => [name, price, sale, plusExtra, sleeve, fabric, fit, collar, occasion, categories[], colors[], featured]
     */
    private function styles(): array
    {
        return [
            'cotton-satin-formal' => [
                'Cotton Satin Formal Shirt for Men - Regular Fit Full Sleeve', 1899, 999, 100, 'full',
                'Cotton Satin', 'Regular Fit', 'Spread Collar', 'Office wear, interviews, business events',
                ['formal-shirts-for-men', 'satin-formal-shirts', 'men-formal'],
                ['White', 'Sky Blue', 'Cream', 'Light Pink', 'Light Grey', 'Navy Blue', 'Wine', 'Black'], 1,
            ],
            'satin-lycra-formal' => [
                'Anti Wrinkle Satin Lycra Formal Shirt - Imported Fabric', 1899, 1199, 100, 'full',
                'Satin Lycra', 'Slim Fit', 'Spread Collar', 'Office, parties, evening events',
                ['formal-shirts-for-men', 'satin-lycra-formal-shirts', 'new-arrivals', 'men-formal'],
                ['Teal Blue', 'Royal Blue', 'Wine', 'Black', 'Dark Olive', 'Navy Blue'], 1,
            ],
            'formal-partywear' => [
                'Formal Partywear Shirt - Japanese Lycra Fabric', 2499, 1099, 100, 'full',
                'Japanese Lycra', 'Slim Fit', 'Italian Collar', 'Parties, dinners, celebrations',
                ['formal-shirts-for-men', 'formal-partywear', 'men-formal'],
                ['Maroon', 'Black', 'Navy Blue', 'Teal Blue', 'Mustard', 'Light Grey'], 1,
            ],
            'single-pocket-cotton' => [
                'Single Pocket Formal Shirt - Pure Cotton', 1899, 899, 100, 'full',
                'Cotton', 'Regular Fit', 'Spread Collar', 'Daily office wear',
                ['formal-shirts-for-men', 'single-pocket-formal-shirts', 'plus-size-shirts', 'men-formal'],
                ['White', 'Sky Blue', 'Light Pink', 'Beige', 'Light Grey', 'Pista Green'], 0,
            ],
            'popcorn-half' => [
                'Popcorn Textured Half Sleeve Shirt', 1699, 799, 100, 'half',
                'Popcorn Knit', 'Regular Fit', 'Cuban Collar', 'Casual outings, vacations',
                ['half-shirts', 'popcorn-half-shirts', 'best-selling-products', 'men-half-sleeve'],
                ['White', 'Black', 'Navy Blue', 'Coffee Brown', 'Sage Green', 'Orange'], 1,
            ],
            'self-cord-half' => [
                'Self Cord Casual Half Sleeve Shirt', 1899, 799, 100, 'half',
                'Self Cord Cotton', 'Regular Fit', 'Spread Collar', 'Weekend wear, casual days',
                ['half-shirts', 'self-cord-half-shirts', 'best-selling-products', 'men-half-sleeve'],
                ['Cream', 'Sky Blue', 'Dark Olive', 'Maroon', 'Beige', 'Lavender'], 1,
            ],
            'satin-half' => [
                'Satin Half Sleeve Formal Shirt', 2499, 799, 100, 'half',
                'Satin', 'Regular Fit', 'Spread Collar', 'Summer office wear',
                ['half-shirts', 'satin-half-shirts', 'new-arrivals', 'men-half-sleeve'],
                ['White', 'Light Pink', 'Sky Blue', 'Black', 'Yellow', 'Wine'], 0,
            ],
            'linen-casual' => [
                'Linen Blend Casual Shirt - Full Sleeve', 2299, 1299, 100, 'full',
                'Linen Blend', 'Relaxed Fit', 'Button-down Collar', 'Brunch, travel, casual Fridays',
                ['casual-shirts', 'linen-shirts', 'men-casual'],
                ['Beige', 'White', 'Sage Green', 'Navy Blue', 'Coffee Brown', 'Lavender'], 0,
            ],
            'self-texture' => [
                'Self Texture Casual Shirt', 1999, 1099, 100, 'full',
                'Textured Cotton', 'Regular Fit', 'Spread Collar', 'Casual and smart-casual',
                ['casual-shirts', 'self-texture-shirts', 'men-casual'],
                ['Black', 'Wine', 'Teal Blue', 'Light Grey', 'Mustard'], 0,
            ],
            'club-tiger' => [
                'Club Wear Shirt - Tiger Print', 2999, 1799, 100, 'full',
                'Printed Lycra', 'Slim Fit', 'Italian Collar', 'Clubs, parties, nightlife',
                ['club-wear', 'club-wear-tiger', 'men-club-wear'],
                ['Black', 'Navy Blue', 'Maroon', 'Royal Blue'], 0,
            ],
            'club-floral' => [
                'Club Wear Shirt - Floral Embroidery', 2999, 1699, 100, 'full',
                'Embroidered Lycra', 'Slim Fit', 'Italian Collar', 'Parties, receptions, nightlife',
                ['club-wear', 'club-wear-floral', 'men-club-wear'],
                ['Black', 'White', 'Wine', 'Teal Blue'], 0,
            ],
            'women-coord' => [
                'Women Co-ord Set - Printed Top and Pants', 2999, 1599, 0, 'full',
                'Rayon Crepe', 'Relaxed Fit', 'Notch Collar', 'Brunch, travel, festive casuals',
                ['co-ord-sets-women', 'women'],
                ['Pista Green', 'Light Pink', 'Mustard', 'Lavender'], 0,
            ],
            'women-kurta' => [
                'Women Kurta Set with Dupatta', 3499, 1899, 0, 'full',
                'Cotton Blend', 'Straight Fit', 'Round Neck', 'Festive wear, family functions',
                ['women-kurta-set', 'women'],
                ['Maroon', 'Teal Blue', 'Mustard', 'Light Pink'], 0, '', 'kurta',
            ],

            'filafil' => [
                'Filafil Formal Shirt - Executive Fit', 1799, 949, 100, 'full', 'Filafil Cotton', 'Regular Fit', 'Spread Collar', 'Daily office wear',
                ['formal-shirts-for-men', 'filafil-shirts', 'men-formal'], ['Sky Blue', 'Light Grey', 'Navy Blue', 'Beige', 'Lavender', 'Pista Green'], 0, 'stripe',
            ],
            'contrast' => [
                'Signature Contrast Formal Shirt', 2199, 1199, 100, 'full', 'Cotton Poplin', 'Slim Fit', 'Contrast Collar', 'Meetings, presentations',
                ['formal-shirts-for-men', 'signature-contrast-collection', 'men-formal'], ['White', 'Sky Blue', 'Light Pink', 'Light Grey', 'Cream', 'Lavender'], 1, '',
            ],
            'mini-check' => [
                'Mini Check Formal Shirt', 1999, 999, 100, 'full', 'Cotton Check', 'Regular Fit', 'Spread Collar', 'Office and business casual',
                ['formal-shirts-for-men', 'mini-stripe-check-shirts', 'men-formal'], ['Navy Blue', 'Maroon', 'Dark Olive', 'Teal Blue', 'Coffee Brown', 'Royal Blue'], 0, 'check',
            ],
            'mini-stripe' => [
                'Mini Stripe Formal Shirt', 1999, 999, 100, 'full', 'Cotton Stripe', 'Regular Fit', 'Spread Collar', 'Office and business events',
                ['formal-shirts-for-men', 'mini-stripe-check-shirts', 'men-formal'], ['Sky Blue', 'Navy Blue', 'Light Pink', 'Light Grey', 'Wine', 'Beige'], 0, 'stripe',
            ],
            'ribbed' => [
                'Fine Ribbed Formal Shirt', 2099, 1099, 100, 'full', 'Ribbed Cotton', 'Regular Fit', 'Spread Collar', 'Office and dinners',
                ['formal-shirts-for-men', 'fine-ribbed-formal-shirt', 'men-formal'], ['White', 'Black', 'Navy Blue', 'Cream', 'Light Grey', 'Sage Green'], 0, 'rib',
            ],
            'self-stripe' => [
                'Formal Self Striped Shirt', 1999, 1049, 100, 'full', 'Self Stripe Cotton', 'Regular Fit', 'Spread Collar', 'Office, events',
                ['formal-shirts-for-men', 'mens-formal-self-striped-shirts', 'men-formal'], ['White', 'Sky Blue', 'Black', 'Beige', 'Light Pink', 'Wine'], 0, 'stripe',
            ],
            'bandana-half' => [
                'Bandana Print Half Sleeve Shirt', 1799, 849, 100, 'half', 'Printed Rayon', 'Regular Fit', 'Cuban Collar', 'Vacations, parties',
                ['half-shirts', 'bandana-printed-half-shirts', 'men-half-sleeve', 'best-selling-products'], ['Black', 'Navy Blue', 'Maroon', 'Royal Blue', 'White', 'Teal Blue'], 1, 'bandana',
            ],
            'net-half' => [
                'Net Textured Half Sleeve Shirt', 1699, 799, 100, 'half', 'Net Knit', 'Regular Fit', 'Cuban Collar', 'Summer outings',
                ['half-shirts', 'net-half-shirts', 'men-half-sleeve'], ['White', 'Black', 'Sky Blue', 'Beige', 'Sage Green', 'Orange'], 0, 'net',
            ],
            'round-neck-tee' => [
                'Round Neck Cotton T-Shirt', 999, 499, 100, 'half', 'Combed Cotton', 'Regular Fit', 'Round Neck', 'Everyday wear',
                ['round-neck-t-shirts', 'half-shirts', 'men-half-sleeve'], ['White', 'Black', 'Navy Blue', 'Light Grey', 'Maroon', 'Dark Olive', 'Mustard'], 1, '', 'tee',
            ],
            'half-formal' => [
                'Half Sleeve Formal Shirt', 1599, 799, 100, 'half', 'Cotton Blend', 'Regular Fit', 'Spread Collar', 'Summer office wear',
                ['half-formal-shirts-for-men', 'half-shirts', 'men-half-sleeve'], ['White', 'Sky Blue', 'Light Pink', 'Light Grey', 'Beige', 'Navy Blue'], 0, '',
            ],
            'shorts' => [
                'Cotton Casual Shorts for Men', 1299, 649, 0, 'none', 'Cotton Twill', 'Regular Fit', 'Elastic Waist', 'Beach, home, travel',
                ['shorts-for-men', 'men-half-sleeve'], ['Beige', 'Navy Blue', 'Black', 'Dark Olive', 'Light Grey', 'Coffee Brown'], 0, '', 'shorts',
            ],
            'crush-half' => [
                'Crush Texture Half Sleeve Shirt', 1799, 849, 100, 'half', 'Crush Cotton', 'Regular Fit', 'Spread Collar', 'Casual days, brunch',
                ['crush-half-shirts', 'half-shirts', 'men-half-sleeve'], ['Cream', 'Sky Blue', 'Light Pink', 'Sage Green', 'Mustard', 'Lavender'], 0, 'dots',
            ],
            'half-coord-men' => [
                'Men Half Sleeve Co-ord Set - Shirt and Shorts', 2799, 1499, 0, 'half', 'Printed Rayon', 'Relaxed Fit', 'Cuban Collar', 'Vacations, resorts',
                ['half-co-ord-cord-sets-for-men', 'men-half-sleeve', 'new-arrivals'], ['Black', 'Navy Blue', 'Sage Green', 'Orange', 'Teal Blue'], 0, 'bandana',
            ],
            'flower-leaf' => [
                'Flower & Leaf Embroidery Casual Shirt', 2499, 1399, 100, 'full', 'Embroidered Cotton', 'Regular Fit', 'Spread Collar', 'Parties, festive casuals',
                ['casual-shirts', 'flower-leaf-embroidery-shirts', 'men-casual'], ['White', 'Black', 'Navy Blue', 'Cream', 'Sage Green', 'Wine'], 0, 'floral',
            ],
            'emb-linen' => [
                'Embroidered Linen Shirt', 2599, 1499, 100, 'full', 'Linen Blend', 'Relaxed Fit', 'Button-down Collar', 'Brunch, destination events',
                ['embroidered-linen', 'casual-shirts', 'men-casual', 'new-arrivals'], ['Beige', 'White', 'Light Grey', 'Sky Blue', 'Sage Green', 'Lavender'], 0, 'floral',
            ],
            't-neck' => [
                'T-Neck Casual Shirt', 1899, 999, 100, 'full', 'Cotton Jersey', 'Regular Fit', 'T-Neck', 'Casual wear',
                ['t-neck-shirt', 'casual-shirts', 'men-casual'], ['Black', 'Navy Blue', 'Dark Olive', 'Maroon', 'Light Grey', 'Mustard'], 0, '',
            ],
            'corduroy' => [
                'Corduroy Casual Shirt', 2299, 1299, 100, 'full', 'Corduroy', 'Regular Fit', 'Spread Collar', 'Winter casuals',
                ['corduroy-shirts', 'casual-shirts', 'men-casual'], ['Coffee Brown', 'Dark Olive', 'Navy Blue', 'Maroon', 'Beige', 'Black'], 0, 'rib',
            ],
            'club-lion' => [
                'Club Wear Shirt - Lion Print', 2999, 1799, 100, 'full', 'Printed Lycra', 'Slim Fit', 'Italian Collar', 'Clubs, parties',
                ['club-wear', 'club-wear-lion', 'men-club-wear'], ['Black', 'Navy Blue', 'Maroon', 'Royal Blue', 'Wine'], 0, 'diag',
            ],
            'crush-emb' => [
                'Crush Fabric Shirt with Embroidery', 2799, 1599, 100, 'full', 'Crush Cotton', 'Slim Fit', 'Italian Collar', 'Parties, receptions',
                ['club-wear', 'crush-with-embroidery', 'men-club-wear'], ['Black', 'White', 'Wine', 'Navy Blue', 'Cream'], 0, 'floral',
            ],
            'club-butterfly' => [
                'Club Wear Shirt - Butterfly Print', 2899, 1699, 100, 'full', 'Printed Lycra', 'Slim Fit', 'Italian Collar', 'Parties, nightlife',
                ['club-wear', 'club-wear-butterfly', 'men-club-wear'], ['Black', 'Royal Blue', 'Maroon', 'Teal Blue', 'White'], 0, 'floral',
            ],
            'hunter' => [
                'Hunter Print Party Shirt', 2899, 1699, 100, 'full', 'Printed Lycra', 'Slim Fit', 'Italian Collar', 'Clubs, parties',
                ['club-wear', 'hunter-shirt', 'men-club-wear'], ['Black', 'Dark Olive', 'Coffee Brown', 'Navy Blue', 'Maroon'], 0, 'diag',
            ],
            'club-owl' => [
                'Club Wear Shirt - Owl Print', 2899, 1699, 100, 'full', 'Printed Lycra', 'Slim Fit', 'Italian Collar', 'Clubs, parties',
                ['club-wear', 'club-wear-owl', 'men-club-wear'], ['Black', 'Navy Blue', 'Wine', 'Dark Olive', 'Royal Blue'], 0, 'diag',
            ],
            'club-deer' => [
                'Club Wear Shirt - Deer Print', 2899, 1699, 100, 'full', 'Printed Lycra', 'Slim Fit', 'Italian Collar', 'Clubs, parties',
                ['club-wear', 'club-wear-deer', 'men-club-wear'], ['Black', 'Maroon', 'Navy Blue', 'Teal Blue', 'Coffee Brown'], 0, 'diag',
            ],
            'metal-clip' => [
                'Metal Clip Detail Party Shirt', 3199, 1899, 100, 'full', 'Satin Lycra', 'Slim Fit', 'Italian Collar', 'Parties, receptions',
                ['club-wear', 'metal-clip-s', 'men-club-wear'], ['Black', 'Wine', 'Navy Blue', 'White', 'Teal Blue'], 0, '',
            ],
            'metal-clip-stone' => [
                'Metal Clip with Stone Party Shirt', 3399, 1999, 100, 'full', 'Satin Lycra', 'Slim Fit', 'Italian Collar', 'Weddings, receptions',
                ['club-wear', 'metal-clip-stone', 'men-club-wear'], ['Black', 'Maroon', 'Royal Blue', 'White', 'Pale Gold'], 0, '',
            ],
            'rhinestone' => [
                'Rhinestone Embroidery Party Shirt', 3499, 2099, 100, 'full', 'Embroidered Lycra', 'Slim Fit', 'Italian Collar', 'Weddings, sangeet, parties',
                ['club-wear', 'rhinestone-embroidery-party-shirts', 'men-club-wear'], ['Black', 'Wine', 'Navy Blue', 'White', 'Royal Blue'], 0, 'floral',
            ],
            'club-flower' => [
                'Club Wear Shirt - Flower Print', 2899, 1699, 100, 'full', 'Printed Lycra', 'Slim Fit', 'Italian Collar', 'Parties, nightlife',
                ['club-wear', 'club-wear-flower', 'men-club-wear'], ['Black', 'Navy Blue', 'Maroon', 'Teal Blue', 'Light Pink'], 0, 'floral',
            ],
            'women-coord-2' => [
                'Women Co-ord Set - Solid Crop Top and Palazzo', 2699, 1399, 0, 'full', 'Rayon Crepe', 'Relaxed Fit', 'Round Neck', 'Brunch, travel',
                ['co-ord-sets-women', 'women', 'new-arrivals'], ['Black', 'Wine', 'Beige', 'Sage Green', 'Navy Blue'], 0, '',
            ],
            'women-kurta-2' => [
                'Women Straight Kurta Set - Printed', 2999, 1599, 0, 'full', 'Cotton Blend', 'Straight Fit', 'Round Neck', 'Festive and family functions',
                ['women-kurta-set', 'women', 'best-selling-products'], ['Maroon', 'Navy Blue', 'Pista Green', 'Orange', 'Lavender'], 0, 'floral', 'kurta',
            ],
            'cotton-satin-2' => [
                'Cotton Satin Formal Shirt - Plus Size Edition', 2099, 1099, 150, 'full', 'Cotton Satin', 'Comfort Fit', 'Spread Collar', 'Office wear',
                ['plus-size-shirts', 'formal-shirts-for-men', 'men-formal'], ['White', 'Sky Blue', 'Light Grey', 'Navy Blue', 'Black', 'Cream'], 0, '',
            ],
            'popcorn-2' => [
                'Popcorn Half Sleeve Shirt - Bold Colours', 1699, 799, 100, 'half', 'Popcorn Knit', 'Regular Fit', 'Cuban Collar', 'Casual outings',
                ['popcorn-half-shirts', 'half-shirts', 'men-half-sleeve', 'best-selling-products'], ['Royal Blue', 'Wine', 'Teal Blue', 'Yellow', 'Orange', 'Lavender'], 0, 'dots',
            ],

            // ---- T-shirts & polos
            'polo-pique' => [
                'Pique Cotton Polo T-Shirt', 1399, 699, 100, 'half', 'Cotton Pique', 'Regular Fit', 'Polo Collar', 'Casual days, golf, weekends',
                ['polo-t-shirts', 'men-tshirts', 'best-selling-products'], ['Navy Blue', 'White', 'Forest Green', 'Burgundy', 'Mustard', 'Sky Blue', 'Charcoal'], 1, '', 'polo',
            ],
            'polo-stripe' => [
                'Striped Polo T-Shirt - Soft Cotton', 1499, 749, 100, 'half', 'Soft Cotton Jersey', 'Regular Fit', 'Polo Collar', 'Casual and smart-casual',
                ['polo-t-shirts', 'men-tshirts'], ['Navy Blue', 'Burgundy', 'Teal Blue', 'Charcoal', 'Forest Green', 'Rust'], 0, 'stripe', 'polo',
            ],
            'oversized-tee' => [
                'Oversized Drop Shoulder T-Shirt', 1299, 649, 100, 'half', '240 GSM Cotton', 'Oversized Fit', 'Round Neck', 'Streetwear, everyday',
                ['oversized-t-shirts', 'men-tshirts', 'new-arrivals'], ['Off White', 'Black', 'Stone', 'Forest Green', 'Rust', 'Ice Blue', 'Lavender'], 1, '', 'tee',
            ],
            'premium-tee' => [
                'Premium Combed Cotton Round Neck T-Shirt', 999, 499, 100, 'half', 'Combed Cotton', 'Regular Fit', 'Round Neck', 'Everyday wear',
                ['premium-t-shirts', 'men-tshirts', 'best-selling-products'], ['White', 'Black', 'Indigo', 'Mint', 'Peach', 'Charcoal', 'Mustard'], 0, '', 'tee',
            ],

            // ---- Bottomwear
            'jeans-slim' => [
                'Slim Fit Stretch Denim Jeans', 2299, 1199, 0, 'none', 'Stretch Denim', 'Slim Fit', 'Mid Rise', 'Everyday, college, outings',
                ['jeans-for-men', 'men-bottomwear', 'best-selling-products'], ['Denim Blue', 'Indigo', 'Black', 'Charcoal', 'Ice Blue', 'Light Grey'], 1, '', 'jeans',
            ],
            'jeans-straight' => [
                'Straight Fit Rigid Denim Jeans', 2499, 1299, 0, 'none', 'Rigid Denim', 'Straight Fit', 'Mid Rise', 'Everyday wear',
                ['jeans-for-men', 'men-bottomwear'], ['Indigo', 'Denim Blue', 'Black', 'Stone', 'Charcoal', 'Ice Blue'], 0, '', 'jeans',
            ],
            'trouser-formal' => [
                'Slim Fit Formal Trousers - Poly Viscose', 2199, 1099, 0, 'none', 'Poly Viscose', 'Slim Fit', 'Flat Front', 'Office, meetings, events',
                ['formal-trousers', 'men-bottomwear', 'men-formal'], ['Black', 'Navy Blue', 'Charcoal', 'Light Grey', 'Coffee Brown', 'Khaki'], 1, '', 'trouser',
            ],
            'chino' => [
                'Stretch Cotton Chinos', 1999, 999, 0, 'none', 'Stretch Cotton Twill', 'Tapered Fit', 'Mid Rise', 'Smart casual, travel',
                ['chinos-for-men', 'men-bottomwear', 'new-arrivals'], ['Khaki', 'Navy Blue', 'Dark Olive', 'Stone', 'Black', 'Rust', 'Burgundy'], 0, '', 'chino',
            ],
            'jogger' => [
                'Cotton Fleece Joggers', 1599, 799, 0, 'none', 'Cotton Fleece', 'Relaxed Fit', 'Elastic Waist', 'Gym, travel, lounging',
                ['joggers-for-men', 'men-bottomwear'], ['Charcoal', 'Black', 'Navy Blue', 'Dark Olive', 'Light Grey', 'Maroon'], 0, '', 'jogger',
            ],

            // ---- Winterwear & jackets
            'hoodie' => [
                'Heavyweight Fleece Hoodie', 2299, 1199, 100, 'full', '320 GSM Fleece', 'Regular Fit', 'Hooded', 'Winter, travel, casual',
                ['hoodies-for-men', 'men-winter', 'best-selling-products'], ['Black', 'Charcoal', 'Forest Green', 'Burgundy', 'Navy Blue', 'Stone', 'Rust'], 1, '', 'hoodie',
            ],
            'sweatshirt' => [
                'Crew Neck Cotton Sweatshirt', 1899, 949, 100, 'full', 'Brushed Cotton Fleece', 'Regular Fit', 'Crew Neck', 'Winter casuals',
                ['sweatshirts-for-men', 'men-winter'], ['Stone', 'Navy Blue', 'Charcoal', 'Mint', 'Burgundy', 'Mustard'], 0, '', 'sweat',
            ],
            'bomber' => [
                'Lightweight Bomber Jacket', 3499, 1899, 100, 'full', 'Nylon Twill', 'Regular Fit', 'Ribbed Collar', 'Winter, travel, evening outings',
                ['jackets-for-men', 'men-winter', 'new-arrivals'], ['Black', 'Forest Green', 'Navy Blue', 'Khaki', 'Burgundy', 'Charcoal'], 1, '', 'jacket',
            ],
            'denim-jacket' => [
                'Classic Denim Jacket', 3299, 1799, 100, 'full', 'Washed Denim', 'Regular Fit', 'Spread Collar', 'Layering, casual outings',
                ['jackets-for-men', 'men-winter'], ['Denim Blue', 'Indigo', 'Black', 'Ice Blue', 'Charcoal', 'Stone'], 0, '', 'jacket',
            ],
            'blazer' => [
                'Slim Fit Casual Blazer', 4999, 2499, 100, 'full', 'Textured Poly Wool', 'Slim Fit', 'Notch Lapel', 'Parties, weddings, office',
                ['blazers-for-men', 'men-winter', 'men-formal'], ['Navy Blue', 'Black', 'Charcoal', 'Burgundy', 'Forest Green', 'Stone'], 1, '', 'blazer',
            ],

            // ---- Ethnic
            'kurta-men' => [
                'Cotton Straight Kurta For Men', 1799, 899, 100, 'full', 'Cotton Blend', 'Straight Fit', 'Mandarin Collar', 'Festive wear, family functions',
                ['kurtas-for-men', 'men-ethnic', 'best-selling-products'], ['White', 'Off White', 'Mint', 'Peach', 'Ice Blue', 'Rust', 'Forest Green'], 1, '', 'kurta',
            ],
            'kurta-pyjama' => [
                'Kurta Pyjama Set - Festive Edition', 3299, 1799, 100, 'full', 'Silk Blend', 'Regular Fit', 'Mandarin Collar', 'Weddings, festivals, pujas',
                ['kurta-pyjama-sets', 'men-ethnic', 'new-arrivals'], ['Burgundy', 'Pale Gold', 'Navy Blue', 'Forest Green', 'Off White', 'Mustard'], 0, '', 'kurta',
            ],

            // ---- Women
            'women-dress-floral' => [
                'Floral Printed A-Line Midi Dress', 2499, 1299, 0, 'none', 'Rayon Crepe', 'A-Line', 'Scoop Neck', 'Brunch, travel, parties',
                ['women-dresses', 'women', 'new-arrivals'], ['Lavender', 'Peach', 'Mint', 'Light Pink', 'Sky Blue', 'Mustard'], 1, 'floral', 'dress',
            ],
            'women-dress-solid' => [
                'Solid Fit & Flare Dress', 2299, 1199, 0, 'none', 'Cotton Blend', 'Fit & Flare', 'Scoop Neck', 'Office, dinners, parties',
                ['women-dresses', 'women'], ['Black', 'Burgundy', 'Navy Blue', 'Forest Green', 'Rust', 'Off White'], 0, '', 'dress',
            ],
            'women-top' => [
                'Ribbed Cotton Round Neck Top', 899, 449, 0, 'half', 'Ribbed Cotton', 'Regular Fit', 'Round Neck', 'Everyday wear',
                ['women-tops', 'women', 'best-selling-products'], ['White', 'Black', 'Peach', 'Mint', 'Lavender', 'Rust'], 0, '', 'tee',
            ],
            'women-palazzo' => [
                'Wide Leg Palazzo Pants', 1699, 849, 0, 'none', 'Rayon Crepe', 'Wide Leg', 'High Rise', 'Casual, office, festive',
                ['women-bottomwear', 'women'], ['Black', 'Off White', 'Burgundy', 'Navy Blue', 'Khaki', 'Forest Green'], 0, '', 'trouser',
            ],
            'women-hoodie' => [
                'Women Oversized Fleece Hoodie', 2099, 1099, 0, 'full', '320 GSM Fleece', 'Oversized Fit', 'Hooded', 'Winter, travel',
                ['women-hoodies', 'women'], ['Lavender', 'Stone', 'Black', 'Mint', 'Peach', 'Burgundy'], 0, '', 'hoodie',
            ],
        ];
    }

    public function run(PDO $db): void
    {
        $this->db = $db;
        mt_srand(42); // deterministic demo data

        $ids = $this->seedCategories();
        $this->seedProducts($ids);
        $this->seedLegacyProducts($ids);

        echo "Catalog categories, products, variants and reviews seeded." . PHP_EOL;
    }

    private function seedCategories(): array
    {
        $ids = [];
        $find = $this->db->prepare('SELECT id FROM categories WHERE slug = ?');
        $insert = $this->db->prepare(
            "INSERT INTO categories (parent_id, name, slug, status, sort_order) VALUES (?, ?, ?, 'active', ?)"
        );
        $update = $this->db->prepare(
            "UPDATE categories SET parent_id = ?, name = ?, sort_order = ?, status = 'active' WHERE id = ?"
        );

        foreach ($this->categories as $slug => [$name, $parentSlug, $sort]) {
            $parentId = $parentSlug ? $ids[$parentSlug] : null;
            $find->execute([$slug]);
            $id = $find->fetchColumn();

            if ($id === false) {
                $insert->execute([$parentId, $name, $slug, $sort]);
                $id = $this->db->lastInsertId();
            } else {
                $update->execute([$parentId, $name, $sort, $id]);
            }

            $ids[$slug] = (int) $id;
        }

        // Pre-existing demo categories from the first version stay available.
        foreach (['ethnic-wear', 'accessories', 'mens-shirts', 'mens-tshirts', 'kurtas'] as $slug) {
            $find->execute([$slug]);
            $id = $find->fetchColumn();
            if ($id !== false) {
                $ids[$slug] = (int) $id;
            }
        }

        return $ids;
    }

    private function seedProducts(array $catIds): void
    {
        $findProduct = $this->db->prepare('SELECT id FROM products WHERE slug = ?');
        $insertProduct = $this->db->prepare(
            "INSERT INTO products (category_id, name, slug, sku, short_description, description, price, sale_price, status, is_featured, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)"
        );
        $insertMeta = $this->db->prepare(
            'INSERT INTO product_meta (product_id, group_key, color, color_hex, fabric, fit, sleeve, collar, occasion, care, badge, sold_count)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insertVariant = $this->db->prepare(
            'INSERT INTO product_variants (product_id, size, sku, price, sale_price, stock, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $insertImage = $this->db->prepare(
            'INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (?, ?, ?, ?, ?)'
        );
        $linkCat = $this->db->prepare(
            'INSERT INTO category_product (category_id, product_id, sort_order) VALUES (?, ?, ?)'
        );
        $insertReview = $this->db->prepare(
            "INSERT INTO reviews (product_id, name, rating, title, body, status, created_at) VALUES (?, ?, ?, ?, ?, 'approved', ?)"
        );

        $sizes = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'];
        $reviewTitles = [
            5 => ['Excellent', 'Superb shirt', 'Love the fabric', 'Great value for money', 'Perfect fit'],
            4 => ['Very good product', 'Nice quality', 'Good, slightly loose'],
            3 => ['Decent for the price'],
        ];
        $reviewBodies = [
            'Fabric feels premium and the stitching is neat. Delivered on time.',
            'Colour is exactly like the photos. Will order again.',
            'Fits true to size. Washed twice and no colour fade.',
            'Looks great in person, got many compliments.',
            'Good quality, a little long in the sleeves for me.',
        ];
        $names = ['Rahul S.', 'Aman K.', 'Vikas P.', 'Sandeep R.', 'Neha T.', 'Arjun M.', 'Karan D.', 'Mohit G.', 'Priya N.', 'Deepak Y.'];

        $i = 0;
        foreach ($this->styles() as $group => $s) {
            [$title, $price, $sale, $plus, $sleeve, $fabric, $fit, $collar, $occasion, $cats, $colors, $featured] = $s;
            $pattern = $s[12] ?? '';
            $kind = $s[13] ?? null;
            $waist = in_array($kind, ['jeans', 'trouser', 'chino', 'jogger', 'shorts'], true) && !str_starts_with($group, 'women');
            $noun = ['tee' => 't-shirt', 'polo' => 'polo t-shirt', 'hoodie' => 'hoodie', 'sweat' => 'sweatshirt', 'jacket' => 'jacket',
                'blazer' => 'blazer', 'kurta' => 'kurta', 'dress' => 'dress', 'trouser' => 'trousers', 'chino' => 'chinos',
                'jeans' => 'jeans', 'jogger' => 'joggers', 'shorts' => 'shorts'][$kind] ?? 'shirt';
            $imgKind = $kind === 'shorts' ? 'none' : ($kind ?? $sleeve);

            // Make sure every style shows at least six colours.
            foreach (['Royal Blue', 'Sage Green', 'Light Grey', 'Teal Blue', 'Wine', 'Navy Blue', 'Black', 'Maroon'] as $extra) {
                if (count($colors) >= 6) {
                    break;
                }
                if (!in_array($extra, $colors, true)) {
                    $colors[] = $extra;
                }
            }

            foreach ($colors as $color) {
                $i++;
                $slug = slugify($title . ' ' . $color);
                $findProduct->execute([$slug]);

                if ($findProduct->fetchColumn() !== false) {
                    continue;
                }

                $hex = $this->colors[$color];
                $name = $title . ' | ' . $color;
                $sku = 'TW-' . strtoupper(substr(md5($group), 0, 4)) . '-' . strtoupper(substr(md5($color), 0, 3));
                $primaryCat = $catIds[$cats[0]];
                $createdAt = date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 90) . ' days'));

                $short = ucfirst("{$fabric} {$noun} in {$color}. {$fit}, {$collar}.");
                $desc = "Made from {$fabric} for all-day comfort, this {$fit} piece in {$color} is cut to sit sharp "
                    . "and stay comfortable. Reinforced stitching and quality buttons make it a dependable "
                    . "pick for: {$occasion}.";

                $insertProduct->execute([
                    $primaryCat, $name, $slug, $sku, $short, $desc, $price, $sale, $featured, $createdAt,
                ]);
                $pid = (int) $this->db->lastInsertId();

                $badge = in_array('best-selling-products', $cats, true) ? 'MOST POPULAR' : null;
                $insertMeta->execute([
                    $pid, $group, $color, $hex, $fabric, $fit, $sleeve, $collar, $occasion,
                    'Cold machine wash, gentle drying, iron on medium heat.', $badge, mt_rand(20, 600),
                ]);

                foreach ($cats as $n => $cslug) {
                    $linkCat->execute([$catIds[$cslug], $pid, $n]);
                }

                $maxSizes = $plus > 0 ? 8 : 5;
                $sizeList = $plus > 0 ? $sizes : ['XS', 'S', 'M', 'L', 'XL'];
                if ($waist) {
                    $sizeList = ['28', '30', '32', '34', '36', '38', '40'];
                    $maxSizes = 7;
                }
                foreach (array_slice($sizeList, 0, $maxSizes) as $k => $size) {
                    $isPlus = $plus > 0 && $k >= 4;
                    $vp = $price + ($isPlus ? $plus : 0);
                    $vs = $sale + ($isPlus ? $plus : 0);
                    $stock = mt_rand(0, 9) === 0 ? 0 : mt_rand(4, 40);
                    $insertVariant->execute([$pid, $size, $sku . '-' . $size, $vp, $vs, $stock, $k]);
                }

                foreach (['front', 'back', 'detail', 'folded'] as $k => $view) {
                    $insertImage->execute([
                        $pid, "shirt:{$hex}:{$view}:{$imgKind}" . ($pattern ? ":{$pattern}" : ''), "{$title} {$color} - {$view}", $k === 0 ? 1 : 0, $k,
                    ]);
                }

                // 3-6 approved reviews
                $count = mt_rand(3, 6);
                for ($r = 0; $r < $count; $r++) {
                    $rating = [5, 5, 5, 4, 4, 3][mt_rand(0, 5)];
                    $t = $reviewTitles[$rating][array_rand($reviewTitles[$rating])];
                    $insertReview->execute([
                        $pid,
                        $names[array_rand($names)],
                        $rating,
                        $t,
                        $reviewBodies[array_rand($reviewBodies)],
                        date('Y-m-d H:i:s', strtotime('-' . mt_rand(1, 120) . ' days')),
                    ]);
                }

                $this->refreshRating($pid);
            }
        }
    }

    /** The four demo products (with real photos) from the first version. */
    private function seedLegacyProducts(array $catIds): void
    {
        $map = [
            'classic-linen-shirt' => ['images/products/classic-linen-shirt.jpg', 'casual-shirts', 'Linen', 'Relaxed Fit'],
            'premium-casual-shirt' => ['images/products/premium-casual-shirt.jpg', 'casual-shirts', 'Cotton', 'Regular Fit'],
            'essential-cotton-tshirt' => ['images/products/essential-cotton-tshirt.jpg', 'half-shirts', 'Cotton', 'Regular Fit'],
            'classic-ethnic-kurta' => ['images/products/classic-ethnic-kurta.jpg', 'women-kurta-set', 'Cotton Blend', 'Straight Fit'],
        ];

        $find = $this->db->prepare('SELECT id, price, sale_price FROM products WHERE slug = ?');
        $hasImg = $this->db->prepare('SELECT COUNT(*) FROM product_images WHERE product_id = ?');
        $hasMeta = $this->db->prepare('SELECT COUNT(*) FROM product_meta WHERE product_id = ?');

        foreach ($map as $slug => [$img, $cat, $fabric, $fit]) {
            $find->execute([$slug]);
            $p = $find->fetch();

            if (!$p) {
                continue;
            }

            $pid = (int) $p['id'];

            $hasImg->execute([$pid]);
            if ((int) $hasImg->fetchColumn() === 0) {
                $this->db->prepare(
                    'INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (?, ?, ?, 1, 0)'
                )->execute([$pid, $img, $slug]);
            }

            $hasMeta->execute([$pid]);
            if ((int) $hasMeta->fetchColumn() === 0) {
                $this->db->prepare(
                    'INSERT INTO product_meta (product_id, group_key, color, color_hex, fabric, fit, sleeve, collar, occasion, care, badge, sold_count)
                     VALUES (?, ?, NULL, NULL, ?, ?, ?, NULL, ?, ?, NULL, ?)'
                )->execute([$pid, $slug, $fabric, $fit, 'full', 'Everyday wear', 'Machine wash cold. Iron on medium heat.', 50]);

                foreach (['S', 'M', 'L', 'XL'] as $k => $size) {
                    $this->db->prepare(
                        'INSERT INTO product_variants (product_id, size, sku, price, sale_price, stock, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)'
                    )->execute([$pid, $size, strtoupper($slug) . '-' . $size, $p['price'], $p['sale_price'], 25, $k]);
                }

                $this->db->prepare(
                    'INSERT INTO category_product (category_id, product_id, sort_order) VALUES (?, ?, 0)'
                )->execute([$catIds[$cat], $pid]);
            }
        }
    }

    private function refreshRating(int $pid): void
    {
        $st = $this->db->prepare("SELECT COUNT(*), COALESCE(AVG(rating), 0) FROM reviews WHERE product_id = ? AND status = 'approved'");
        $st->execute([$pid]);
        [$count, $avg] = $st->fetch(PDO::FETCH_NUM);

        $this->db->prepare('UPDATE product_meta SET rating_count = ?, rating_avg = ? WHERE product_id = ?')
            ->execute([(int) $count, round((float) $avg, 2), $pid]);
    }
}
