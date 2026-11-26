<?php

require_once '../includes/auth.php';
requireStaff();

$today = date('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_orders,
        SUM(order_status = 'Completed') AS completed_orders,
        SUM(order_status = 'Pending') AS pending_orders,
        COALESCE(
            SUM(CASE
                WHEN order_status = 'Completed'
                THEN total_amount ELSE 0
            END), 0
        ) AS total_sales
     FROM orders
     WHERE DATE(created_at) = ?"
);

$stmt->execute([$today]);
$summary = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT m.item_name, SUM(oi.quantity) AS quantity_sold,
            SUM(oi.subtotal) AS item_sales
     FROM order_items oi
     JOIN orders o ON o.order_id = oi.order_id
     JOIN menu_items m ON m.item_id = oi.item_id
     WHERE DATE(o.created_at) = ?
     AND o.order_status = 'Completed'
     GROUP BY oi.item_id, m.item_name
     ORDER BY quantity_sold DESC"
);

$stmt->execute([$today]);
$products = $stmt->fetchAll();

require '../includes/header.php';
?>

<div class="wrap">

    <div class="section-head">
        <div>
            <h2>Today's sales</h2>
            <p><?= date('F d, Y') ?></p>
        </div>
    </div>

    <div class="stat-grid">

        <div class="stat">
            <span>Total orders</span>
            <strong><?= $summary['total_orders'] ?></strong>
        </div>

        <div class="stat">
            <span>Completed orders</span>
            <strong><?= $summary['completed_orders'] ?></strong>
        </div>

        <div class="stat">
            <span>Pending orders</span>
            <strong><?= $summary['pending_orders'] ?></strong>
        </div>

        <div class="stat">
            <span>Completed sales</span>
            <strong><?= money($summary['total_sales']) ?></strong>
        </div>

    </div>

    <section class="panel">

        <h3>Products sold today</h3>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity sold</th>
                        <th>Sales amount</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($products as $product): ?>

                    <tr>
                        <td><?= e($product['item_name']) ?></td>
                        <td><?= $product['quantity_sold'] ?></td>
                        <td><?= money($product['item_sales']) ?></td>
                    </tr>

                <?php endforeach; ?>

                <?php if (!$products): ?>
                    <tr>
                        <td colspan="3" class="empty">
                            No completed sales today.
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </section>

</div>

<?php require '../includes/footer.php'; ?>