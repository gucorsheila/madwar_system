<?php

require_once 'includes/functions.php';

if (loggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrf();

    $name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        strlen($username) < 3 ||
        strlen($password) < 8
    ) {
        flash('Enter valid information. Password must be at least 8 characters.', 'error');
        redirect('register.php');
    }

    $stmt = $pdo->prepare(
        "SELECT user_id FROM users WHERE username = ?"
    );

    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        flash('Username already exists.', 'error');
        redirect('register.php');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, username, password, role)
         VALUES (?, ?, ?, 'customer')"
    );

    $stmt->execute([$name, $username, $hash]);

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'user_id' => $pdo->lastInsertId(),
        'full_name' => $name,
        'username' => $username,
        'role' => 'customer'
    ];

    $_SESSION['cart'] = [];

    flash('Your MADWAR account has been created!');

    redirect('index.php');
}

require 'includes/header.php';
?>

<div class="login-bg">
    <div class="login-card">

        <div class="eyebrow">Welcome to MADWAR</div>

        <h1>Create account</h1>

        <p>Register as a MADWAR customer.</p>

        <form method="POST">
            <?= csrfField() ?>

            <div class="field">
                <label>Full name</label>
                <input name="full_name" required maxlength="100">
            </div>

            <div class="field">
                <label>Username</label>
                <input name="username" required minlength="3" maxlength="50">
            </div>

            <div class="field">
                <label>Password</label>
                <input type="password" name="password"
                       required minlength="8">
            </div>

            <button class="pill full-button">Create account</button>
        </form>

        <p>
            Already registered?
            <a href="login.php">Log in</a>
        </p>

    </div>
</div>

<?php require 'includes/footer.php'; ?>