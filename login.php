<?php

require_once 'includes/functions.php';

if (loggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT * FROM users WHERE username = ? LIMIT 1"
    );

    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'full_name' => $user['full_name'],
            'username' => $user['username'],
            'role' => $user['role']
        ];

        $_SESSION['cart'] = [];

        flash('Welcome, ' . $user['full_name'] . '!');

        if ($user['role'] === 'admin') {
            redirect('admin/dashboard.php');
        }

        if ($user['role'] === 'staff') {
            redirect('staff/dashboard.php');
        }

        redirect('index.php');

    } else {
        flash('Incorrect username or password.', 'error');
        redirect('login.php');
    }
}

require 'includes/header.php';
?>

<div class="login-bg">
    <div class="login-card">

        <div class="eyebrow">Welcome to MADWAR</div>

        <h1>Log in</h1>

        <p>Enter your account information.</p>

        <form method="POST">
            <?= csrfField() ?>

            <div class="field">
                <label>Username</label>
                <input name="username" required>
            </div>

            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button class="pill full-button">Log in</button>
        </form>

        <p>
            New customer?
            <a href="register.php">Create account</a>
        </p>

    </div>
</div>

<?php require 'includes/footer.php'; ?>