<?php

require_once 'includes/auth.php';
requireCustomer();

$stmt = $pdo->prepare(
    "SELECT * FROM orders
     WHERE customer_id = ?
     ORDER BY created_at DESC"
);

$stmt->execute([currentUser()['user_id']]);
$orders = $stmt->fetchAll();

require 'includes/header.php';
?>

<div class="wrap">

    <div class="section-head">
        <div>
            <h2>My orders</h2>
            <p>Track your order confirmation and pickup time.</p>
        </div>

        <a href="index.php" class="pill">+ Order more</a>
    </div>

    <?php if ($orders): ?>

        <?php foreach ($orders as $order): ?>

            <section class="panel">

                <div class="order-heading">

                    <div>
                        <h3><?= e($order['order_code']) ?></h3>

                        <span class="muted">
                            <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>
                        </span>
                    </div>

                    <span class="status <?= e($order['order_status']) ?>">
                        <?= e($order['order_status']) ?>
                    </span>

                </div>

                <?php

                $stmt = $pdo->prepare(
                    "SELECT oi.*, m.item_name
                     FROM order_items oi
                     JOIN menu_items m ON m.item_id = oi.item_id
                     WHERE oi.order_id = ?"
                );

                $stmt->execute([$order['order_id']]);
                $lines = $stmt->fetchAll();

                ?>

                <?php foreach ($lines as $line): ?>

                    <div class="cart-line">
                        <span>
                            <?= e($line['item_name']) ?>
                            × <?= $line['quantity'] ?>
                        </span>

                        <strong>
                            <?= money($line['subtotal']) ?>
                        </strong>
                    </div>

                <?php endforeach; ?>

                <div class="two-col order-details">

                    <div>
                        <span class="muted">Pickup time</span>

                        <strong>
                            <?= date('M d, Y h:i A', strtotime($order['pickup_time'])) ?>
                        </strong>
                    </div>

                    <div>
                        <span class="muted">Payment method</span>

                        <strong>
                            <?= e($order['payment_method']) ?>
                        </strong>
                    </div>

                </div>

                <div class="total">
                    <span>Total</span>
                    <span><?= money($order['total_amount']) ?></span>
                </div>

                <?php if ($order['order_notes']): ?>
                    <p class="muted">
                        Note: <?= e($order['order_notes']) ?>
                    </p>
                <?php endif; ?>

                <?php if ($order['order_status'] === 'Pending'): ?>
                    <div class="notice">
                        Waiting for MADWAR staff to confirm your order.
                    </div>
                <?php endif; ?>

                <details>
                    <summary>Order status history</summary>

                    <?php
                    $stmt = $pdo->prepare(
                        "SELECT status, changed_at
                         FROM order_status_history
                         WHERE order_id = ?
                         ORDER BY changed_at DESC"
                    );

                    $stmt->execute([$order['order_id']]);

                    foreach ($stmt->fetchAll() as $history):
                    ?>

                        <p>
                            <strong><?= e($history['status']) ?></strong>
                            <span class="muted">
                                <?= date('M d, Y h:i A', strtotime($history['changed_at'])) ?>
                            </span>
                        </p>

                    <?php endforeach; ?>
                </details>

            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="panel empty">
            You have no orders yet.
            <p><a href="index.php">Browse our menu</a></p>
        </section>

    <?php endif; ?>

</div>

<?php require 'includes/footer.php'; ?>