<?php

require_once '../includes/auth.php';
requireStaff();

$categories = ['Burgers', 'Meals', 'Sides', 'Drinks', 'Desserts'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $action = $_POST['action'] ?? '';

    try {

        if ($action === 'add' || $action === 'edit') {

            $id = (int)($_POST['item_id'] ?? 0);
            $name = trim($_POST['item_name'] ?? '');
            $category = $_POST['category'] ?? '';
            $description = trim($_POST['description'] ?? '');
            $price = (float)($_POST['price'] ?? 0);
            $stock = (int)($_POST['stock'] ?? 0);
            $image = trim($_POST['image'] ?? '');
            $available = isset($_POST['is_available']) ? 1 : 0;

            if (
                $name === '' ||
                !in_array($category, $categories, true) ||
                $price < 0 ||
                $stock < 0
            ) {
                throw new Exception('Please enter valid menu information.');
            }

            if (strlen($image) > 255) {
                throw new Exception('Image path is too long.');
            }

            $pdo->beginTransaction();

            if ($action === 'add') {

                $stmt = $pdo->prepare(
                    "INSERT INTO menu_items
                    (item_name, category, description, price, image, is_available)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name, $category, $description,
                    $price, $image, $available
                ]);

                $id = $pdo->lastInsertId();

                $stmt = $pdo->prepare(
                    "INSERT INTO inventory (item_id, stock_quantity)
                     VALUES (?, ?)"
                );

                $stmt->execute([$id, $stock]);

            } else {

                $stmt = $pdo->prepare(
                    "UPDATE menu_items
                     SET item_name=?, category=?, description=?,
                         price=?, image=?, is_available=?
                     WHERE item_id=?"
                );

                $stmt->execute([
                    $name, $category, $description,
                    $price, $image, $available, $id
                ]);

                $stmt = $pdo->prepare(
                    "UPDATE inventory
                     SET stock_quantity=?
                     WHERE item_id=?"
                );

                $stmt->execute([$stock, $id]);

                if ($stmt->rowCount() === 0) {
                    $stmt = $pdo->prepare(
                        "INSERT INTO inventory (item_id, stock_quantity)
                         VALUES (?, ?)"
                    );
                    $stmt->execute([$id, $stock]);
                }
            }

            $pdo->commit();

            flash($action === 'add'
                ? 'Menu item added successfully.'
                : 'Menu item updated successfully.');

        } elseif ($action === 'delete') {

            $id = (int)$_POST['item_id'];

            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM order_items WHERE item_id = ?"
            );

            $stmt->execute([$id]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception(
                    'This item has order history and cannot be deleted. Hide it instead.'
                );
            }

            $stmt = $pdo->prepare(
                "DELETE FROM menu_items WHERE item_id = ?"
            );

            $stmt->execute([$id]);

            flash('Menu item deleted.');

        } else {
            throw new Exception('Invalid action.');
        }

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            $e instanceof PDOException
                ? 'Unable to save the menu item.'
                : $e->getMessage(),
            'error'
        );
    }

    redirect('menu_items.php');
}

$items = $pdo->query(
    "SELECT m.*, COALESCE(i.stock_quantity,0) AS stock
     FROM menu_items m
     LEFT JOIN inventory i ON i.item_id = m.item_id
     ORDER BY m.item_id DESC"
)->fetchAll();

require '../includes/header.php';
?>

<div class="wrap">

    <div class="section-head">
        <div>
            <h2>Menu management</h2>
            <p>Add and manage MADWAR food products.</p>
        </div>
    </div>

    <section class="panel">

        <h3>Add menu item</h3>

        <form method="POST">
            <?= csrfField() ?>

            <input type="hidden" name="action" value="add">

            <div class="form-grid">

                <div class="field">
                    <label>Item name</label>
                    <input name="item_name" required>
                </div>

                <div class="field">
                    <label>Category</label>
                    <select name="category">
                        <?php foreach ($categories as $cat): ?>
                            <option><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label>Price (₱)</label>
                    <input type="number" name="price"
                           min="0" step=".01" required>
                </div>

                <div class="field">
                    <label>Stock quantity</label>
                    <input type="number" name="stock"
                           min="0" value="0" required>
                </div>

                <div class="field full">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>

                <div class="field full">
                    <label>Image path (optional)</label>
                    <input name="image"
                           placeholder="assets/images/burger.jpg">
                </div>

                <div class="field">
                    <label>
                        <input type="checkbox" name="is_available" checked>
                        Available to customers
                    </label>
                </div>

            </div>

            <button class="pill">Add item</button>

        </form>

    </section>

    <section class="panel">

        <h3>Existing menu items</h3>

        <div class="table-wrap">
            <table>

                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Availability</th>
                        <th>Management</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($items as $item): ?>

                    <tr>
                        <td><?= e($item['item_name']) ?></td>
                        <td><?= e($item['category']) ?></td>
                        <td><?= money($item['price']) ?></td>
                        <td><?= $item['stock'] ?></td>

                        <td>
                            <?= $item['is_available'] ? 'Available' : 'Hidden' ?>
                        </td>

                        <td>

                            <details>
                                <summary>Edit</summary>

                                <form method="POST">
                                    <?= csrfField() ?>

                                    <input type="hidden" name="action"
                                           value="edit">

                                    <input type="hidden" name="item_id"
                                           value="<?= $item['item_id'] ?>">

                                    <div class="field">
                                        <label>Name</label>
                                        <input name="item_name"
                                               value="<?= e($item['item_name']) ?>"
                                               required>
                                    </div>

                                    <div class="field">
                                        <label>Category</label>
                                        <select name="category">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= e($cat) ?>"
                                                    <?= $cat === $item['category'] ? 'selected' : '' ?>>
                                                    <?= e($cat) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field">
                                        <label>Price</label>
                                        <input type="number" name="price"
                                               value="<?= e($item['price']) ?>"
                                               min="0" step=".01" required>
                                    </div>

                                    <div class="field">
                                        <label>Stock</label>
                                        <input type="number" name="stock"
                                               value="<?= $item['stock'] ?>"
                                               min="0" required>
                                    </div>

                                    <div class="field">
                                        <label>Description</label>
                                        <textarea name="description"><?= e($item['description']) ?></textarea>
                                    </div>

                                    <div class="field">
                                        <label>Image path</label>
                                        <input name="image"
                                               value="<?= e($item['image']) ?>">
                                    </div>

                                    <label>
                                        <input type="checkbox"
                                               name="is_available"
                                               <?= $item['is_available'] ? 'checked' : '' ?>>
                                        Available
                                    </label>

                                    <button class="small-btn gold">
                                        Save changes
                                    </button>
                                </form>
                            </details>

                            <form method="POST" class="inline-form">
                                <?= csrfField() ?>

                                <input type="hidden" name="action"
                                       value="delete">

                                <input type="hidden" name="item_id"
                                       value="<?= $item['item_id'] ?>">

                                <button class="small-btn red"
                                        onclick="return confirm('Delete this item?')">
                                    Delete
                                </button>
                            </form>

                        </td>
                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>
        </div>

    </section>

</div>

<?php require '../includes/footer.php'; ?>