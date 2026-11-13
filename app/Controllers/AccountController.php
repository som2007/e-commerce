<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Services\OrderService;

class AccountController extends Controller
{
    public function index(Request $request): string
    {
        $orders = (new OrderService(db()))->forUser((int) auth_id());

        return $this->view('frontend/account/index', [
            'title' => 'My account',
            'user' => auth_user(),
            'orders' => array_slice($orders, 0, 5),
            'orderCount' => count($orders),
        ]);
    }

    public function orders(Request $request): string
    {
        return $this->view('frontend/account/orders', [
            'title' => 'My orders',
            'orders' => (new OrderService(db()))->forUser((int) auth_id()),
        ]);
    }

    public function order(Request $request): string
    {
        $svc = new OrderService(db());
        $order = $svc->find((string) $request->param('number'));

        if (!$order || (int) $order['user_id'] !== auth_id()) {
            abort(404);
        }

        return $this->view('frontend/account/order', [
            'title' => 'Order ' . $order['order_number'],
            'order' => $order,
            'items' => $svc->items((int) $order['id']),
            'timeline' => $svc->timeline((int) $order['id']),
        ]);
    }

    public function cancel(Request $request): void
    {
        $svc = new OrderService(db());
        $order = $svc->find((string) $request->param('number'));

        if (!$order || (int) $order['user_id'] !== auth_id()) {
            abort(404);
        }

        if (in_array($order['status'], ['placed', 'confirmed'], true)) {
            $svc->cancel($order['order_number'], 'Cancelled by customer');
            flash('success', 'Your order has been cancelled.');
        } else {
            flash('error', 'This order can no longer be cancelled. You can raise an exchange request instead.');
        }

        redirect('/account/orders/' . $order['order_number']);
    }

    public function addresses(Request $request): string
    {
        $st = db()->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
        $st->execute([auth_id()]);

        return $this->view('frontend/account/addresses', [
            'title' => 'My addresses',
            'addresses' => $st->fetchAll(),
            'states' => CheckoutController::STATES,
        ]);
    }

    public function saveAddress(Request $request): void
    {
        $d = [
            'name' => $this->str($request, 'name', 150),
            'phone' => preg_replace('/\D/', '', $this->str($request, 'phone', 20)),
            'line1' => $this->str($request, 'line1'),
            'line2' => $this->str($request, 'line2'),
            'city' => $this->str($request, 'city', 100),
            'state' => $this->str($request, 'state', 100),
            'pincode' => preg_replace('/\D/', '', $this->str($request, 'pincode', 10)),
        ];

        if (!$d['name'] || !$d['line1'] || !$d['city'] || !$d['state']
            || !preg_match('/^[1-9]\d{5}$/', $d['pincode'])
            || !preg_match('/^(?:91)?[6-9]\d{9}$/', $d['phone'])) {
            flash('error', 'Please fill in all address fields with valid details.');
            redirect('/account/addresses');
        }

        $uid = (int) auth_id();
        $first = db()->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = ?');
        $first->execute([$uid]);
        $isDefault = (int) $first->fetchColumn() === 0 ? 1 : 0;

        db()->prepare(
            'INSERT INTO addresses (user_id, name, phone, line1, line2, city, state, pincode, is_default)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$uid, $d['name'], substr($d['phone'], -10), $d['line1'], $d['line2'] ?: null, $d['city'], $d['state'], $d['pincode'], $isDefault]);

        flash('success', 'Address saved.');
        redirect('/account/addresses');
    }

    public function deleteAddress(Request $request): void
    {
        db()->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?')
            ->execute([(int) $request->param('id'), auth_id()]);

        flash('success', 'Address removed.');
        redirect('/account/addresses');
    }

    public function defaultAddress(Request $request): void
    {
        $uid = (int) auth_id();
        db()->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$uid]);
        db()->prepare('UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?')
            ->execute([(int) $request->param('id'), $uid]);

        redirect('/account/addresses');
    }

    public function updateProfile(Request $request): void
    {
        $first = $this->str($request, 'first_name', 100);
        $last = $this->str($request, 'last_name', 100);
        $phone = preg_replace('/\D/', '', $this->str($request, 'phone', 20));

        if ($first === '') {
            flash('error', 'First name is required.');
            redirect('/account');
        }

        if ($phone !== '' && !preg_match('/^(?:91)?[6-9]\d{9}$/', $phone)) {
            flash('error', 'Enter a valid mobile number.');
            redirect('/account');
        }

        try {
            db()->prepare('UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?')
                ->execute([$first, $last ?: null, $phone !== '' ? substr($phone, -10) : null, auth_id()]);
            flash('success', 'Profile updated.');
        } catch (\PDOException $e) {
            flash('error', 'That mobile number is already used by another account.');
        }

        redirect('/account');
    }
}
