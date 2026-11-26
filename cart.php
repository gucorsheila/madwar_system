<?php

require_once 'includes/auth.php';
requireCustomer();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['item_id'] ?? 0);

    if ($action === 'add') {

        $stmt = $pdo->prepare(
            "SELECT m.item_id, i.stock_quantity
             FROM menu_items m
             JOIN inventory i ON i.item_id = m.item_id
             WHERE m.item_id = ? AND m.is_available = 1"
        );

        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item || $item['stock_quantity'] < 1) {
            flash('This item is unavailable.', 'error');
        } else {
            $_SESSION['cart'][$id] =
                ($_SESSION['cart'][$id] ?? 0) + 1;

            flash('Item added to cart!');
        }

    } elseif ($action === 'update') {

        $qty = (int)($_POST['quantity'] ?? 1);

        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id] = min($qty, 100);
        }

    } elseif ($action === 'remove') {

        unset($_SESSION['cart'][$id]);
    }

    redirect('cart.php');
}

$cartItems = [];
$total = 0;

foreach ($_SESSION['cart'] as $id => $qty) {

    $stmt = $pdo->prepare(
        "SELECT m.*, i.stock_quantity
         FROM menu_items m
         JOIN inventory i ON i.item_id = m.item_id
         WHERE m.item_id = ? AND m.is_available = 1"
    );

    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        unset($_SESSION['cart'][$id]);
        continue;
    }

    $item['quantity'] = min((int)$qty, (int)$item['stock_quantity']);
    $item['subtotal'] = $item['price'] * $item['quantity'];

    $cartItems[] = $item;
    $total += $item['subtotal'];
}

require 'includes/header.php';
?>

<div class="wrap">

    <div class="section-head">
        <div>
            <h2>Your cart</h2>
            <p>Review your selected food items.</p>
        </div>

        <a href="index.php" class="outline">← Back to menu</a>
    </div>

    <section class="panel">

        <?php if ($cartItems): ?>

            <?php foreach ($cartItems as $item): ?>

                <div class="cart-line">

                    <div>
                        <h3><?= e($item['item_name']) ?></h3>

                        <p><?= money($item['price']) ?> each</p>

                        <form method="POST" class="actions">
                            <?= csrfField() ?>

                            <input type="hidden" name="action"
                                   value="update">

                            <input type="hidden" name="item_id"
                                   value="<?= $item['item_id'] ?>">

                            <input type="number" name="quantity"
                                   min="0"
                                   max="<?= $item['stock_quantity'] ?>"
                                   value="<?= $item['quantity'] ?>"
                                   style="width:75px">

                            <button class="small-btn">Update</button>
                        </form>
                    </div>

                    <div>
                        <strong><?= money($item['subtotal']) ?></strong>

                        <form method="POST">
                            <?= csrfField() ?>

                            <input type="hidden" name="action"
                                   value="remove">

                            <input type="hidden" name="item_id"
                                   value="<?= $item['item_id'] ?>">

                            <button class="small-btn red">Remove</button>
                        </form>
                    </div>

                </div>

            <?php endforeach; ?>

            <div class="total">
                <span>Total</span>
                <span><?= money($total) ?></span>
            </div>

            <a href="checkout.php" class="pill full-button">
                Proceed to checkout
            </a>

        <?php else: ?>

            <div class="empty">
                Your cart is empty.

                <p><a href="index.php">Browse our menu</a></p>
            </div>

        <?php endif; ?>

    </section>

</div>

<?php require 'includes/footer.php'; ?>