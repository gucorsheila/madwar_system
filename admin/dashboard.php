<?php

require_once '../includes/auth.php';
requireAdmin();

$totalItems = $pdo->query(
    "SELECT COUNT(*) FROM menu_items"
)->fetchColumn();

$availableItems = $pdo->query(
    "SELECT COUNT(*) FROM menu_items WHERE is_available = 1"
)->fetchColumn();

$totalStaff = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'staff'"
)->fetchColumn();

$totalOrders = $pdo->query(
    "SELECT COUNT(*) FROM orders"
)->fetchColumn();

$lowStock = $pdo->query(
    "SELECT COUNT(*) FROM inventory WHERE stock_quantity <= 5"
)->fetchColumn();

require '../includes/header.php';
?>

<div class="wrap">

    <div class="eyebrow">Administrator workspace</div>

    <div class="section-head">
        <div>
            <h2>Admin dashboard</h2>
            <p>Welcome, <?= e(currentUser()['full_name']) ?>.</p>
        </div>

        <span class="role-label">ADMIN ACCESS</span>
    </div>

    <div class="stat-grid">

        <div class="stat">
            <span>Menu items</span>
            <strong><?= $totalItems ?></strong>
        </div>

        <div class="stat">
            <span>Available items</span>
            <strong><?= $availableItems ?></strong>
        </div>

        <div class="stat">
            <span>Staff accounts</span>
            <strong><?= $totalStaff ?></strong>
        </div>

        <div class="stat">
            <span>All orders</span>
            <strong><?= $totalOrders ?></strong>
        </div>

    </div>

    <section class="panel">
        <h3>Inventory alert</h3>

        <p>
            Products with low stock (5 or fewer):
            <strong><?= $lowStock ?></strong>
        </p>

        <a href="inventory.php" class="pill">
            View inventory
        </a>
    </section>

    <div class="two-col">

        <a href="inventory.php" class="panel">
            <h3>📦 Inventory management</h3>
            <p>Monitor stock quantities and availability.</p>
        </a>

        <a href="staff.php" class="panel">
            <h3>👥 Staff accounts</h3>
            <p>Create and manage staff accounts.</p>
        </a>

    </div>

</div>

<?php require '../includes/footer.php'; ?>