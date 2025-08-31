<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = trim($_POST["username"]);
    $password = $_POST["password"];

    // Use PDO for compatibility with registration
    require_once 'config.php';
    try {
        $connPDO = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $connPDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $connPDO->prepare("SELECT * FROM barangay_officials WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'user_id'   => $user['id'],
                'username'  => $user['username'],
                'full_name' => ($user['first_name'] . ' ' . $user['last_name']),
                'position'  => $user['position'],
                'role'      => $user['position'] // or use a separate role column if available
            ];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username/email or password!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Login</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <main class="login-screen">
        <img src="logo.png" alt="Barangay Logo" class="logo">
        <h1>Barangay Demographic Profiling System</h1>
        <h2>Login to your account</h2>

        <form method="POST" class="login-form">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <div class="form-options">
                <label>
                    <input type="checkbox" name="remember"> Remember me
                </label>
                <a href="forgot.php">Forgot password?</a>
            </div>

            <button type="submit" class="login-btn">Login</button>
            <a href="register.php" class="register-link">Don't have an account? Register</a>

            <?php if (isset($error)): ?>
                <p class="error-message"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </form>
    </main>
</body>
</html>