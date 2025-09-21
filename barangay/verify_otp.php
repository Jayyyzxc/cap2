<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Import PHPMailer manually
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
$success = false;

// Check if user just registered
if (!isset($_SESSION['pending_verification'])) {
    die("No pending verification. Please register first. <a href='register.php'>Register here</a>");
}

$email = $_SESSION['pending_verification'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');

    if (empty($otp)) {
        $errors['otp'] = "OTP is required";
    } else {
        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if OTP is valid
            $stmt = $conn->prepare("SELECT * FROM barangay_officials WHERE email = ? AND otp = ? AND otp_expiry > NOW()");
            $stmt->execute([$email, $otp]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Mark verified
                $update = $conn->prepare("UPDATE barangay_officials SET is_verified = 1, otp = NULL, otp_expiry = NULL WHERE email = ?");
                $update->execute([$email]);

                unset($_SESSION['pending_verification']);
                $success = true;
            } else {
                $errors['otp'] = "Invalid or expired OTP.";
            }
        } catch (PDOException $e) {
            $errors['database'] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
<div class="registration-container">
    <h1>OTP Verification</h1>
    <p>We sent a 6-digit OTP code to your email <strong><?php echo htmlspecialchars($email); ?></strong></p>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ Your account has been verified! <a href="login.php">Login here</a></div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="registration-form">
            <label for="otp">Enter OTP:</label>
            <input type="text" name="otp" maxlength="6" required>
            <button type="submit">Verify</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
