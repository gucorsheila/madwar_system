<?php

require_once '../includes/auth.php';
requireStaff();

$today = date('Y-m-d');

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?"
);
$stmt->execute([$today]);
$orderCount = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM orders
     WHERE DATE(created_at) = ? AND order_status = 'Pending'"
);
$stmt->execute([$today]);
$pending = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM orders
     WHERE DATE(created_at) = ? AND order_status = 'Completed'"
);
$stmt->execute([$today]);
$completed = $stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(total_amount),0) FROM orders
     WHERE DATE(created_at) = ? AND order_status = 'Completed'"
);
$stmt->execute([$today]);
$sales = $stmt->fetchColumn();

require '../includes/header.php';
?>

<div class="wrap">

    <div class="eyebrow">Staff workspace</div>

    <div class="section-head">
        <div>
            <h2>Good day, <?= e(currentUser()['full_name']) ?></h2>
            <p>Welcome to the MADWAR staff dashboard.</p>
        </div>

        <span class="role-label">STAFF ACCESS</span>
    </div>

    <div class="stat-grid">

        <div class="stat">
            <span>Orders today</span>
            <strong><?= $orderCount ?></strong>
        </div>

        <div class="stat">
            <span>Pending orders</span>
            <strong><?= $pending ?></strong>
        </div>

        <div class="stat">
            <span>Completed orders</span>
            <strong><?= $completed ?></strong>
        </div>

        <div class="stat">
            <span>Today's sales</span>
            <strong><?= money($sales) ?></strong>
        </div>

    </div>

    <div class="grid dashboard-links">

        <a class="panel" href="orders.php">
            <h3>📦 Manage orders</h3>
            <p>Confirm and update customer orders.</p>
        </a>

        <a class="panel" href="menu_items.php">
            <h3>🍔 Menu management</h3>
            <p>Add, edit, hide and delete menu items.</p>
        </a>

        <a class="panel" href="sales.php">
            <h3>💰 Daily sales</h3>
            <p>Review completed sales and sold products.</p>
        </a>

    </div>

</div>

<?php require '../includes/footer.php'; ?>