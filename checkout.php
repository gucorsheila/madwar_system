<?php

require_once 'includes/auth.php';
requireCustomer();

$cart = $_SESSION['cart'] ?? [];

if (!$cart) {
    flash('Your cart is empty.', 'error');
    redirect('cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $pickup = $_POST['pickup_time'] ?? '';
    $payment = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['order_notes'] ?? '');

    $date = DateTime::createFromFormat('Y-m-d\TH:i', $pickup);

    if (
        !$date ||
        $date->format('Y-m-d\TH:i') !== $pickup ||
        $date->getTimestamp() < time() + 900
    ) {
        flash('Pickup time must be at least 15 minutes from now.', 'error');
        redirect('checkout.php');
    }

    if (!in_array($payment, ['Cash on Pickup', 'GCash'], true)) {
        flash('Invalid payment method.', 'error');
        redirect('checkout.php');
    }

    try {

        $pdo->beginTransaction();

        $total = 0;
        $orderLines = [];

        foreach ($cart as $id => $qty) {

            $stmt = $pdo->prepare(
                "SELECT m.item_id, m.item_name, m.price,
                        m.is_available, i.stock_quantity
                 FROM menu_items m
                 JOIN inventory i ON i.item_id = m.item_id
                 WHERE m.item_id = ?
                 FOR UPDATE"
            );

            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if (
                !$item ||
                !$item['is_available'] ||
                $item['stock_quantity'] < $qty
            ) {
                throw new Exception(
                    'Insufficient stock or unavailable item: ' .
                    ($item['item_name'] ?? 'Unknown item')
                );
            }

            $subtotal = $item['price'] * $qty;
            $total += $subtotal;

            $orderLines[] = [
                'id' => $item['item_id'],
                'price' => $item['price'],
                'quantity' => $qty
            ];
        }

        $orderCode = 'MW-' . date('ymd') . '-' .
            strtoupper(bin2hex(random_bytes(3)));

        $stmt = $pdo->prepare(
            "INSERT INTO orders
            (order_code, customer_id, pickup_time,
             payment_method, total_amount, order_notes)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $orderCode,
            currentUser()['user_id'],
            $date->format('Y-m-d H:i:s'),
            $payment,
            $total,
            $notes
        ]);

        $orderId = $pdo->lastInsertId();

        foreach ($orderLines as $line) {

            $stmt = $pdo->prepare(
                "INSERT INTO order_items
                (order_id, item_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $orderId,
                $line['id'],
                $line['quantity'],
                $line['price']
            ]);

            $stmt = $pdo->prepare(
                "UPDATE inventory
                 SET stock_quantity = stock_quantity - ?
                 WHERE item_id = ?"
            );

            $stmt->execute([
                $line['quantity'],
                $line['id']
            ]);
        }

        recordHistory(
            $pdo,
            $orderId,
            'Pending',
            currentUser()['user_id']
        );

        $pdo->commit();

        $_SESSION['cart'] = [];

        flash('Order placed successfully! Your order code is ' . $orderCode);

        redirect('my_orders.php');

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash($e instanceof PDOException
            ? 'Unable to place your order. Please try again.'
            : $e->getMessage(), 'error');

        redirect('checkout.php');
    }
}

require 'includes/header.php';
?>

<div class="wrap">

    <div class="section-head">
        <div>
            <h2>Checkout</h2>
            <p>Choose your preferred pickup schedule.</p>
        </div>
    </div>

    <div class="two-col">

        <section class="panel">
            <h3>Order summary</h3>

            <?php
            $total = 0;

            foreach ($cart as $id => $qty):

                $stmt = $pdo->prepare(
                    "SELECT item_name, price FROM menu_items WHERE item_id = ?"
                );

                $stmt->execute([$id]);
                $item = $stmt->fetch();

                if (!$item) continue;

                $subtotal = $item['price'] * $qty;
                $total += $subtotal;
            ?>

                <div class="cart-line">
                    <span>
                        <?= e($item['item_name']) ?> × <?= $qty ?>
                    </span>

                    <strong><?= money($subtotal) ?></strong>
                </div>

            <?php endforeach; ?>

            <div class="total">
                <span>Total</span>
                <span><?= money($total) ?></span>
            </div>

        </section>

        <section class="panel">
            <h3>Pickup details</h3>

            <div class="notice">
                Wait for staff confirmation before picking up your order.
            </div>

            <form method="POST">
                <?= csrfField() ?>

                <div class="field">
                    <label>Preferred pickup time</label>

                    <input type="datetime-local"
                           name="pickup_time"
                           min="<?= date('Y-m-d\TH:i', time() + 900) ?>"
                           required>
                </div>

                <div class="field">
                    <label>Payment method</label>

                    <select name="payment_method" required>
                        <option value="Cash on Pickup">
                            Cash on Pickup
                        </option>

                        <option value="GCash">
                            GCash
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label>Order notes</label>

                    <textarea name="order_notes"
                              placeholder="Special instructions..."></textarea>
                </div>

                <button class="pill full-button">
                    Place order · <?= money($total) ?>
                </button>

            </form>
        </section>

    </div>

</div>

<?php require 'includes/footer.php'; ?>