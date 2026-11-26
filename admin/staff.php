
<?php

require_once '../includes/auth.php';
requireAdmin();

require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {

        $name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (
            $name === '' ||
            $username === '' ||
            strlen($password) < 8
        ) {
            flash('Complete all fields. Password must have at least 8 characters.', 'error');
            redirect('staff.php');
        }

        $check = $pdo->prepare(
            "SELECT user_id FROM users WHERE username = ?"
        );

        $check->execute([$username]);

        if ($check->fetch()) {
            flash('Username already exists.', 'error');
            redirect('staff.php');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users
            (full_name, username, password, role)
            VALUES (?, ?, ?, 'staff')
        ");

        $stmt->execute([$name, $username, $hash]);

        flash('Staff account created successfully.', 'success');
        redirect('staff.php');
    }

    if ($action === 'update') {

        $id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$id || $id <= 0 || $name === '' || $username === '') {
            flash('Invalid staff information.', 'error');
            redirect('staff.php');
        }

        $check = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE username = ? AND user_id != ?
        ");

        $check->execute([$username, $id]);

        if ($check->fetch()) {
            flash('Username already exists.', 'error');
            redirect('staff.php');
        }

        $check = $pdo->prepare("
            SELECT user_id
            FROM users
            WHERE user_id = ? AND role = 'staff'
        ");

        $check->execute([$id]);

        if (!$check->fetch()) {
            flash('Staff account not found.', 'error');
            redirect('staff.php');
        }

        if ($password !== '') {

            if (strlen($password) < 8) {
                flash('Password must have at least 8 characters.', 'error');
                redirect('staff.php');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, username = ?, password = ?
                WHERE user_id = ? AND role = 'staff'
            ");

            $stmt->execute([$name, $username, $hash, $id]);

        } else {

            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, username = ?
                WHERE user_id = ? AND role = 'staff'
            ");

            $stmt->execute([$name, $username, $id]);
        }

        flash('Staff account updated successfully.', 'success');
        redirect('staff.php');
    }

    if ($action === 'delete') {

        $id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            flash('Invalid staff account.', 'error');
            redirect('staff.php');
        }

        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE user_id = ? AND role = 'staff'
        ");

        $stmt->execute([$id]);

        flash(
            $stmt->rowCount() ? 'Staff account deleted.' : 'Staff account not found.',
            $stmt->rowCount() ? 'success' : 'error'
        );

        redirect('staff.php');
    }
}

$stmt = $pdo->query("
    SELECT user_id, full_name, username
    FROM users
    WHERE role = 'staff'
    ORDER BY full_name ASC
");

$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>