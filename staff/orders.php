<?php

require_once '../includes/auth.php';
requireStaff();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $id = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $allowed = [
        'Confirmed',
        'Ready',
        'Completed',
        'Cancelled'
    ];

    try {

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "SELECT * FROM orders WHERE order_id = ? FOR UPDATE"
        );

        $stmt->execute([$id]);
        $order = $stmt->fetch();

        if (!$order) {
            throw new Exception('Order not found.');
        }

        if ($action === 'save_pickup') {

            $pickup = $_POST['pickup_time'] ?? '';
            $date = DateTime::createFromFormat('Y-m-d\TH:i', $pickup);

            if (
                !$date ||
                $date->format('Y-m-d\TH:i') !== $pickup ||
                $date->getTimestamp() < time()
            ) {
                throw new Exception('Invalid pickup time.');
            }

            if (in_array($order['order_status'], ['Completed', 'Cancelled'], true)) {
                throw new Exception('This order is already closed.');
            }

            $stmt = $pdo->prepare(
                "UPDATE orders SET pickup_time = ? WHERE order_id = ?"
            );

            $stmt->execute([
                $date->format('Y-m-d H:i:s'),
                $id
            ]);

            $pdo->commit();

            flash('Pickup time updated.');
            redirect('orders.php');
        }

        if (!in_array($action, $allowed, true)) {
            throw new Exception('Invalid order action.');
        }

        $oldStatus = $order['order_status'];

        if (in_array($oldStatus, ['Completed', 'Cancelled'], true)) {
            throw new Exception('This order is already closed.');
        }

        $validTransitions = [
            'Pending' => ['Confirmed', 'Cancelled'],
            'Confirmed' => ['Ready', 'Cancelled'],
            'Ready' => ['Completed', 'Cancelled']
        ];

        if (!in_array($action, $validTransitions[$oldStatus] ?? [], true)) {
            throw new Exception('Invalid order status transition.');
        }

        if ($action === 'Cancelled') {

            $stmt = $pdo->prepare(
                "SELECT item_id, quantity
                 FROM order_items WHERE order_id = ?"
            );

            $stmt->execute([$id]);

            foreach ($stmt->fetchAll() as $line) {

                $stmt2 = $pdo->prepare(
                    "UPDATE inventory
                     SET stock_quantity = stock_quantity + ?
                     WHERE item_id = ?"
                );

                $stmt2->execute([
                    $line['quantity'],
                    $line['item_id']
                ]);
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE orders SET order_status = ? WHERE order_id = ?"
        );

        $stmt->execute([$action, $id]);

        recordHistory(
            $pdo,
            $id,
            $action,
            currentUser()['user_id']
        );

        $pdo->commit();

        flash('Order updated successfully.');

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            $e instanceof PDOException
                ? 'Unable to update the order.'
                : $e->getMessage(),
            'error'
        );
    }

    redirect('orders.php');
}

$orders = $pdo->query(
    "SELECT o.*, u.full_name, u.username
     FROM orders o
     JOIN users u ON u.user_id = o.customer_id
     ORDER BY o.created_at DESC"
)->fetchAll();

require '../includes/header.php';
?>

<div class="wrap">

    <div class="section-head">
        <div>
            <h2>Customer orders</h2>
            <p>Review and manage all customer orders.</p>
        </div>
    </div>

    <section class="panel">

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Pickup</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($orders as $order): ?>

                    <?php
                    $stmt = $pdo->prepare(
                        "SELECT m.item_name, oi.quantity
                         FROM order_items oi
                         JOIN menu_items m ON m.item_id = oi.item_id
                         WHERE oi.order_id = ?"
                    );

                    $stmt->execute([$order['order_id']]);
                    $lines = $stmt->fetchAll();
                    ?>

                    <tr>

                        <td>
                            <strong><?= e($order['order_code']) ?></strong>
                            <br>
                            <small><?= e($order['created_at']) ?></small>
                        </td>

                        <td>
                            <?= e($order['full_name']) ?>
                            <br>
                            <small><?= e($order['username']) ?></small>
                        </td>

                        <td>
                            <?= date('M d, Y h:i A', strtotime($order['pickup_time'])) ?>
                        </td>

                        <td><?= money($order['total_amount']) ?></td>

                        <td>
                            <span class="status <?= e($order['order_status']) ?>">
                                <?= e($order['order_status']) ?>
                            </span>
                        </td>

                        <td>
                            <?php foreach ($lines as $line): ?>
                                <?= e($line['item_name']) ?>
                                × <?= $line['quantity'] ?><br>
                            <?php endforeach; ?>
                        </td>

                        <td>

                            <?php if (!in_array($order['order_status'], ['Completed', 'Cancelled'], true)): ?>

                                <form method="POST" class="action-form">
                                    <?= csrfField() ?>

                                    <input type="hidden" name="order_id"
                                           value="<?= $order['order_id'] ?>">

                                    <select name="action" required>
                                        <option value="">Update status</option>

                                        <?php
                                        $next = [
                                            'Pending' => ['Confirmed', 'Cancelled'],
                                            'Confirmed' => ['Ready', 'Cancelled'],
                                            'Ready' => ['Completed', 'Cancelled']
                                        ];
                                        ?>

                                        <?php foreach ($next[$order['order_status']] ?? [] as $status): ?>
                                            <option value="<?= e($status) ?>">
                                                <?= e($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button class="small-btn gold">
                                        Update
                                    </button>
                                </form>

                                <form method="POST" class="pickup-form">
                                    <?= csrfField() ?>

                                    <input type="hidden" name="order_id"
                                           value="<?= $order['order_id'] ?>">

                                    <input type="hidden" name="action"
                                           value="save_pickup">

                                    <input type="datetime-local"
                                           name="pickup_time"
                                           value="<?= date('Y-m-d\TH:i', strtotime($order['pickup_time'])) ?>"
                                           required>

                                    <button class="small-btn">
                                        Save time
                                    </button>
                                </form>

                            <?php else: ?>

                                <span class="muted">Closed</span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$orders): ?>
                    <tr>
                        <td colspan="7" class="empty">No orders yet.</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</div>

<?php require '../includes/footer.php'; ?>