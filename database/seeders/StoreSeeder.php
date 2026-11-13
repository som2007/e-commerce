<?php

/** CMS pages, blog posts, coupons and the first admin account. */
class StoreSeeder
{
    public function run(PDO $db): void
    {
        $this->pages($db);
        $this->blog($db);
        $this->coupons($db);
        $this->admin($db);

        echo "Store pages, blog, coupons and admin seeded." . PHP_EOL;
    }

    private function pages(PDO $db): void
    {
        $brand = \Rk\Threadwell\Core\Config::get('app.name');

        $pages = [
            'about-us' => ['About Us', "<p>{$brand} is a menswear label focused on shirts that look sharp, feel comfortable and stay affordable. From office-ready formals to relaxed half sleeves and statement club wear, every piece is designed in-house and checked at each stage of production.</p><p>Our team has been working with fabrics for over two decades, and that experience shows in the fit, the finish and the details you notice after the tenth wash.</p><h3>What we stand for</h3><ul><li>Honest pricing with no inflated mark-ups</li><li>Fabrics chosen for comfort in Indian weather</li><li>Easy 7-day exchange on every order</li></ul>"],
            'why-choose-us' => ['Why Choose Us', "<ul><li><strong>Quality checked:</strong> every shirt passes a multi-point inspection before it ships.</li><li><strong>Wide size range:</strong> S to 5XL so more people find their fit.</li><li><strong>Secure payments:</strong> pay online by UPI, card or net banking, or choose cash on delivery.</li><li><strong>Easy exchange:</strong> wrong size? Raise an exchange within 7 days.</li><li><strong>Fast delivery:</strong> most orders arrive in 3-6 days.</li></ul>"],
            'our-story' => ['Our Story', "<p>What started as a small garment unit has grown into a brand trusted by thousands of customers across India. We began by making shirts for other labels; the day we started making them for ourselves, we promised to build the kind of shirt we would be proud to wear.</p><p>Trends fade, but style remains. That idea guides every collection we release.</p>"],
            'awards-recognition' => ['Awards & Recognition', "<p>We are grateful to customers, partners and industry bodies who have recognised our work. This page will be updated with new milestones as they come.</p>"],
            'return-and-exchange-policy' => ['Return and Exchange Policy', "<h3>7-day exchange</h3><p>We accept exchanges within 7 days of delivery for size or fit issues, and for manufacturing defects or wrong items received. Products must be unused, unwashed and have original tags attached.</p><h3>How to exchange</h3><ol><li>Open <a href=\"/exchange-request\">Raise Exchange Request</a> and enter your order number and email.</li><li>Choose the reason and the size you want.</li><li>Our team will approve the request and arrange the pickup.</li></ol><h3>Refunds</h3><p>Refunds are issued only when the exchange is not possible (for example, the size is out of stock) and go back to the original payment method within 7-10 working days.</p>"],
            'privacy-policy' => ['Privacy Policy', "<p>We collect only the information needed to process your orders: name, contact details, delivery address and order history. Payments are handled by the payment gateway; we never store your card details.</p><p>We do not sell your personal data. You can ask us to delete your account at any time by writing to our support email.</p>"],
            'terms-of-service' => ['Terms of Service', "<p>By using this website you agree to these terms. Prices and availability can change without notice. We reserve the right to cancel orders in case of pricing errors, suspected fraud or stock issues, in which case any amount paid is refunded in full.</p><p>All content, images and designs on this site belong to the store and may not be reused without permission.</p>"],
            'shipping-policy' => ['Shipping Policy', "<p>Orders are dispatched within 24-48 hours. Delivery usually takes 3-6 working days depending on your location. Shipping is free above the free-shipping threshold; otherwise a small fee applies. Cash on delivery carries an extra handling fee.</p>"],
            'size-chart' => ['Size Chart', "<table class=\"size-table\"><thead><tr><th>Size</th><th>Chest (in)</th><th>Shoulder (in)</th><th>Length (in)</th></tr></thead><tbody><tr><td>S (36)</td><td>40</td><td>17.5</td><td>28</td></tr><tr><td>M (38)</td><td>42</td><td>18</td><td>29</td></tr><tr><td>L (40)</td><td>44</td><td>18.5</td><td>30</td></tr><tr><td>XL (42)</td><td>46</td><td>19</td><td>30.5</td></tr><tr><td>XXL (44)</td><td>48</td><td>19.5</td><td>31</td></tr><tr><td>3XL (46)</td><td>50</td><td>20</td><td>31.5</td></tr><tr><td>4XL (48)</td><td>52</td><td>20.5</td><td>32</td></tr><tr><td>5XL (50)</td><td>54</td><td>21</td><td>32.5</td></tr></tbody></table><p class=\"muted\">Measurements are body sizes. Allow 1-2 inches for a comfortable fit.</p>"],
        ];

        $exists = $db->prepare('SELECT id FROM pages WHERE slug = ?');
        $insert = $db->prepare('INSERT INTO pages (slug, title, body) VALUES (?, ?, ?)');

        foreach ($pages as $slug => [$title, $body]) {
            $exists->execute([$slug]);
            if ($exists->fetchColumn() === false) {
                $insert->execute([$slug, $title, $body]);
            }
        }
    }

    private function blog(PDO $db): void
    {
        $posts = [
            ['how-to-pick-the-right-shirt-size', 'How to pick the right shirt size', 'A two-minute guide to measuring yourself and choosing between regular and slim fits.', '<p>Start with your chest: wrap a tape around the fullest part, keep it snug but not tight, and compare it with our size chart. If you are between sizes, choose the larger one for regular fits and the smaller for slim fits.</p><p>Next, check shoulder and sleeve length. A good shirt has the shoulder seam on the edge of your shoulder bone.</p>'],
            ['styling-satin-shirts-for-parties', '5 ways to style a satin shirt for parties', 'Satin catches the light and instantly lifts an outfit. Here is how to wear it.', '<p>Pair a black satin shirt with tailored trousers and loafers for evening events. Go tonal with a dark navy shirt and charcoal chinos. Roll the sleeves for a relaxed look, or layer under an unstructured blazer.</p>'],
            ['caring-for-your-shirts', 'Caring for your shirts so they last', 'Simple washing and ironing habits that keep colours bright and collars crisp.', '<p>Wash dark colours inside out in cold water, skip the dryer, and iron while slightly damp. Button the top button before washing to keep the collar shape.</p>'],
        ];

        $exists = $db->prepare('SELECT id FROM blog_posts WHERE slug = ?');
        $insert = $db->prepare('INSERT INTO blog_posts (slug, title, excerpt, body) VALUES (?, ?, ?, ?)');

        foreach ($posts as $p) {
            $exists->execute([$p[0]]);
            if ($exists->fetchColumn() === false) {
                $insert->execute($p);
            }
        }
    }

    private function coupons(PDO $db): void
    {
        $rows = [
            ['WELCOME10', 'percent', 10, 999, 500, 0],
            ['FLAT200', 'flat', 200, 1999, null, 0],
            ['UPI10', 'percent', 10, 0, 400, 1],
        ];

        $exists = $db->prepare('SELECT id FROM coupons WHERE code = ?');
        $insert = $db->prepare(
            'INSERT INTO coupons (code, type, value, min_amount, max_discount, prepaid_only) VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($rows as $r) {
            $exists->execute([$r[0]]);
            if ($exists->fetchColumn() === false) {
                $insert->execute($r);
            }
        }
    }

    private function admin(PDO $db): void
    {
        $email = $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'admin@example.com';
        $password = $_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?: 'Admin@12345';

        $exists = $db->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$email]);

        if ($exists->fetchColumn() !== false) {
            return;
        }

        $role = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetchColumn();

        $db->prepare(
            "INSERT INTO users (role_id, first_name, last_name, email, password, status, email_verified_at)
             VALUES (?, 'Store', 'Admin', ?, ?, 'active', ?)"
        )->execute([$role, $email, password_hash($password, PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);

        echo "Admin created: {$email} / {$password}  (change this password!)" . PHP_EOL;
    }
}
