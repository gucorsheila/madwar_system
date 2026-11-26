
<?php
require_once 'includes/auth.php';

// Only logged-in customers can submit feedback.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $category = trim($_POST['category'] ?? '');
    $comments = trim($_POST['comments'] ?? '');

    if (!$rating || $rating < 1 || $rating > 5) {
        $message = 'Please select a valid star rating.';
        $messageType = 'error';

    } elseif ($category === '' || $comments === '') {
        $message = 'Please complete all required fields.';
        $messageType = 'error';

    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO customer_feedback
                    (user_id, rating, category, comments, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $_SESSION['user_id'],
                $rating,
                $category,
                $comments
            ]);

            $message = 'Thank you! Your feedback has been submitted.';
            $messageType = 'success';

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $message = 'Unable to submit feedback. Please try again.';
            $messageType = 'error';
        }
    }
}
?>