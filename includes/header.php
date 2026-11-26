<?php
require_once __DIR__ . '/functions.php';

$user = currentUser();
$pageTitle = $pageTitle ?? 'MADWAR | Burger & More';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?></title>

    <link rel="stylesheet" href="/madwar/assets/css/style.css">
    <script src="/madwar/assets/js/script.js" defer></script>
</head>
<body>

<header class="topbar">
    <a href="/madwar/index.php" class="brand">
        MAD<span>WAR</span>
        <small>BURGER & MORE</small>
    </a>

    <nav class="nav">
        <?php if (!$user): ?>

            <a href="/madwar/index.php">Menu</a>
            <a href="/madwar/login.php" class="pill">Log in</a>

        <?php elseif ($user['role'] === 'customer'): ?>

            <a href="/madwar/index.php">Menu</a>
            <a href="/madwar/my_orders.php">My orders</a>
            <a href="/madwar/cart.php" class="pill">
                Cart (<?= array_sum($_SESSION['cart'] ?? []) ?>)
            </a>

        <?php elseif ($user['role'] === 'staff'): ?>

            <a href="/madwar/staff/dashboard.php">Dashboard</a>
            <a href="/madwar/staff/orders.php">Orders</a>
            <a href="/madwar/staff/menu_items.php">Menu items</a>
            <a href="/madwar/staff/sales.php">Sales</a>

        <?php elseif ($user['role'] === 'admin'): ?>

            <a href="/madwar/admin/dashboard.php">Dashboard</a>
            <a href="/madwar/admin/inventory.php">Inventory</a>
            <a href="/madwar/admin/staff.php">Staff accounts</a>

        <?php endif; ?>

        <?php if ($user): ?>
            <span class="role-label">
                <?= e(strtoupper($user['role'])) ?>
            </span>

            <form method="POST" action="/madwar/logout.php">
                <?= csrfField() ?>
                <button class="outline">Log out</button>
            </form>
        <?php endif; ?>
    </nav>
</header>

<main>
    <?php showFlash(); ?>