<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Services\MailService;
use Rk\Threadwell\Services\OrderService;

/** Order tracking, exchange requests, contact form, newsletter. */
class SupportController extends Controller
{
    /* ------------------------------------------------------------ track */

    public function track(Request $request): string
    {
        $number = strtoupper(trim((string) $request->query('order', '')));
        $contact = strtolower(trim((string) $request->query('contact', '')));
        $order = $items = $timeline = null;
        $error = null;

        if ($number !== '' || $contact !== '') {
            $svc = new OrderService(db());
            $found = $svc->find(ltrim($number, '#'));
            $digits = preg_replace('/\D/', '', $contact);

            // Order number + (email or phone) must both match: prevents order guessing.
            if ($found && ($found['email'] === $contact || ($digits !== '' && substr($digits, -10) === $found['phone']))) {
                $order = $found;
                $items = $svc->items((int) $found['id']);
                $timeline = $svc->timeline((int) $found['id']);
            } else {
                $error = 'We could not find an order with those details. Check the order number and the email or phone used at checkout.';
            }
        }

        return $this->view('frontend/track', [
            'title' => 'Track your order',
            'number' => $number,
            'contact' => $contact,
            'order' => $order,
            'items' => $items,
            'timeline' => $timeline,
            'error' => $error,
        ]);
    }

    /* --------------------------------------------------------- exchange */

    public function exchangeForm(Request $request): string
    {
        return $this->view('frontend/exchange', [
            'title' => 'Raise exchange request',
            'reasons' => self::REASONS,
            'sizes' => ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL', '5XL'],
        ]);
    }

    public function exchangeStore(Request $request): void
    {
        $number = strtoupper(ltrim($this->str($request, 'order_number', 30), '#'));
        $email = strtolower($this->str($request, 'email', 191));
        $reason = $this->str($request, 'reason', 120);
        $newSize = $this->str($request, 'new_size', 20);
        $details = $this->str($request, 'details', 1500);
        $note = $this->str($request, 'item_note', 255);

        $fail = function (string $m) use ($request): never {
            flash('error', $m);
            $this->remember($request->all());
            redirect('/exchange-request');
        };

        if (!in_array($reason, self::REASONS, true)) {
            $fail('Please choose a reason for the exchange.');
        }

        $svc = new OrderService(db());
        $order = $svc->find($number);

        if (!$order || $order['email'] !== $email) {
            $fail('We could not match that order number with that email address.');
        }

        if ($order['status'] !== 'delivered') {
            $fail($order['status'] === 'cancelled'
                ? 'This order was cancelled, so an exchange is not possible.'
                : 'Exchanges can be raised once your order has been delivered.');
        }

        $delivered = null;
        foreach ($svc->timeline((int) $order['id']) as $h) {
            if ($h['status'] === 'delivered') {
                $delivered = strtotime($h['created_at']);
            }
        }

        if ($delivered && $delivered < strtotime('-7 days')) {
            $fail('The 7-day exchange window for this order has closed.');
        }

        $dup = db()->prepare("SELECT COUNT(*) FROM exchange_requests WHERE order_number = ? AND status IN ('pending','approved')");
        $dup->execute([$number]);
        if ((int) $dup->fetchColumn() > 0) {
            $fail('An exchange request for this order is already in progress.');
        }

        db()->prepare(
            'INSERT INTO exchange_requests (order_number, email, item_note, reason, new_size, details) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$number, $email, $note ?: null, $reason, $newSize ?: null, $details ?: null]);

        $this->forget();
        flash('success', 'Your exchange request has been submitted. Our team will contact you within 1-2 working days.');
        redirect('/exchange-request');
    }

    private const REASONS = [
        'Size too small', 'Size too large', 'Fit issue', 'Received wrong item', 'Manufacturing defect', 'Colour not as expected',
    ];

    /* ---------------------------------------------------------- contact */

    public function contactForm(Request $request): string
    {
        return $this->view('frontend/contact', ['title' => 'Contact us']);
    }

    public function contactStore(Request $request): void
    {
        $name = $this->str($request, 'name', 150);
        $email = strtolower($this->str($request, 'email', 191));
        $phone = preg_replace('/\D/', '', $this->str($request, 'phone', 20));
        $message = $this->str($request, 'message', 3000);

        // Honeypot field - bots fill it, humans never see it.
        if ($this->str($request, 'website') !== '') {
            redirect('/pages/contact');
        }

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 5) {
            flash('error', 'Please enter your name, a valid email and your message.');
            $this->remember($request->all());
            redirect('/pages/contact');
        }

        db()->prepare('INSERT INTO contact_messages (name, email, phone, message) VALUES (?, ?, ?, ?)')
            ->execute([$name, $email, $phone ?: null, $message]);

        (new MailService())->send(
            config('store.email'),
            'New contact message from ' . $name,
            '<p>' . nl2br(e($message)) . '</p><p>' . e($name) . ' - ' . e($email) . ' - ' . e($phone) . '</p>'
        );

        $this->forget();
        flash('success', 'Thanks for reaching out! We will reply within one working day.');
        redirect('/pages/contact');
    }

    /* ------------------------------------------------------- newsletter */

    public function subscribe(Request $request): void
    {
        $email = strtolower($this->str($request, 'email', 191));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'message' => 'Please enter a valid email address.'], 422);

            return;
        }

        $st = db()->prepare('SELECT 1 FROM newsletter_subscribers WHERE email = ?');
        $st->execute([$email]);

        if (!$st->fetchColumn()) {
            db()->prepare('INSERT INTO newsletter_subscribers (email) VALUES (?)')->execute([$email]);
        }

        $this->json(['success' => true, 'message' => 'Thanks for subscribing!']);
    }
}
