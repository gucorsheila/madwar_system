<?php

require_once 'includes/functions.php';

$pageTitle = 'MADWAR | Burger & More';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? 'All');

$sql = "SELECT m.*, COALESCE(i.stock_quantity, 0) AS stock
        FROM menu_items m
        LEFT JOIN inventory i ON i.item_id = m.item_id
        WHERE m.is_available = 1
        AND COALESCE(i.stock_quantity, 0) > 0";

$params = [];

if ($category !== 'All') {
    $sql .= " AND m.category = ?";
    $params[] = $category;
}

if ($search !== '') {
    $sql .= " AND (m.item_name LIKE ? OR m.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY m.category, m.item_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$categories = $pdo->query(
    "SELECT DISTINCT category FROM menu_items ORDER BY category"
)->fetchAll(PDO::FETCH_COLUMN);

require 'includes/header.php';
?>

<div class="wrap">

    <section class="hero">
        <div>
            <div class="eyebrow">Good food. Good mood.</div>

            <h1>Your cravings,<br>our specialty.</h1>

            <p>
                Fresh burgers, tasty sides and refreshing drinks
                from MADWAR. Order ahead and pick up when it works for you.
            </p>

            <a href="#menu" class="pill">Explore the menu ↓</a>
        </div>

        <div class="hero-art">🍔</div>
    </section>

    <div class="section-head" id="menu">
        <div>
            <h2>Made fresh for you</h2>
            <p>Find your next favorite bite.</p>
        </div>

        <form method="GET">
            <input
                class="search"
                type="search"
                name="search"
                placeholder="Search the menu..."
                value="<?= e($search) ?>"
            >
            <input type="hidden" name="category"
                   value="<?= e($category) ?>">
        </form>
    </div>

    <div class="filters">
        <a href="index.php" class="filter-link <?= $category === 'All' ? 'active' : '' ?>">
            All
        </a>

        <?php foreach ($categories as $cat): ?>
            <a
                href="?category=<?= urlencode($cat) ?>"
                class="filter-link <?= $category === $cat ? 'active' : '' ?>"
            >
                <?= e($cat) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="grid">

        <?php if ($items): ?>

            <?php foreach ($items as $item): ?>

                <article class="food-card">

                    <div class="food-img">
                        <?php
                        $emojis = [
                            'Burgers' => '🍔',
                            'Meals' => '🍗',
                            'Sides' => '🍟',
                            'Drinks' => '🥤',
                            'Desserts' => '🍰'
                        ];

                        if (!empty($item['image'])):
                        ?>
                            <img src="<?= e($item['image']) ?>"
                                 alt="<?= e($item['item_name']) ?>">
                        <?php else: ?>
                            <?= $emojis[$item['category']] ?? '🍽️' ?>
                        <?php endif; ?>
                    </div>

                    <h3><?= e($item['item_name']) ?></h3>

                    <p><?= e($item['description']) ?></p>

                    <div class="food-bottom">

                        <span class="price">
                            <?= money($item['price']) ?>
                        </span>

                        <?php if (currentUser() && currentUser()['role'] === 'customer'): ?>

                            <form method="POST" action="cart.php">
                                <?= csrfField() ?>

                                <input type="hidden" name="item_id"
                                       value="<?= $item['item_id'] ?>">

                                <input type="hidden" name="action"
                                       value="add">

                                <button class="add">+</button>
                            </form>

                        <?php else: ?>

                            <a href="login.php" class="add">+</a>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                No available menu items found.
            </div>

        <?php endif; ?>

    </div>

    <section class="panel">
        <h2>Order with ease</h2>

        <div class="two-col">
            <div>
                <h3>🍔 Freshly prepared</h3>
                <p>Our team confirms your order.</p>
            </div>

            <div>
                <h3>🕒 Pick up on your time</h3>
                <p>Track your order through your account.</p>
            </div>
        </div>
    </section>

</div>

<?php require 'includes/footer.php'; ?>