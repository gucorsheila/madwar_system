<?php
require_once '../includes/auth.php';
requireAdmin();

require_once '../includes/db.php';

$stmt = $pdo->query("
    SELECT
        m.item_id,
        m.item_name,
        m.category,
        m.price,
        m.emoji,
        m.is_available,
        COALESCE(i.stock_quantity, 0) AS stock_quantity
    FROM menu_items m
    LEFT JOIN inventory i ON m.item_id = i.item_id
    ORDER BY m.item_name ASC
");

$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $stock = filter_input(
        INPUT_POST,
        'stock_quantity',
        FILTER_VALIDATE_INT
    );

    if (!$id || $id <= 0 || $stock === false || $stock === null || $stock < 0) {
        flash('Invalid inventory information.', 'error');
        redirect('inventory.php');
    }

    $check = $pdo->prepare(
        "SELECT item_id FROM menu_items WHERE item_id = ?"
    );
    $check->execute([$id]);

    if (!$check->fetch()) {
        flash('Menu item does not exist.', 'error');
        redirect('inventory.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO inventory (item_id, stock_quantity)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE
        stock_quantity = VALUES(stock_quantity)
    ");

    $stmt->execute([$id, $stock]);

    flash('Inventory updated successfully.', 'success');
    redirect('inventory.php');
}

$totalItems = count($inventory);
$totalStock = array_sum(array_column($inventory, 'stock_quantity'));

$lowStock = count(array_filter(
    $inventory,
    fn($item) => $item['stock_quantity'] > 0 && $item['stock_quantity'] <= 5
));

$outOfStock = count(array_filter(
    $inventory,
    fn($item) => $item['stock_quantity'] == 0
));

?>