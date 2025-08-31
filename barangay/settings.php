<?php
session_start();
require_once 'config.php';

// Check if user is logged in and set $is_logged_in
$is_logged_in = isset($_SESSION['user']);

if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Set user data with default values if not set
$userId = $_SESSION['user']['user_id'] ?? null;
$email = $_SESSION['user']['email'] ?? '';
$position = $_SESSION['user']['position'] ?? 'Staff';
$full_name = $_SESSION['user']['full_name'] ?? 'User';
$message = "";

// If user_id is not set, redirect to login
if (!$userId) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        // Update Email
        if (!empty($_POST['email'])) {
            $newEmail = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $message = "Invalid email format";
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
                $stmt->bind_param("si", $newEmail, $userId);
                if ($stmt->execute()) {
                    $_SESSION['user']['email'] = $newEmail;
                    $email = $newEmail;
                    $message = "Email updated successfully.";
                }
            }
        }

        // Update Password
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8) {
                $message = "Password must be at least 8 characters";
            } else {
                $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param("si", $newPassword, $userId);
                if ($stmt->execute()) {
                    $message = "Password updated successfully.";
                }
            }
        }

        // Update Position
        if (!empty($_POST['position'])) {
            $allowed_positions = ['Barangay Captain', 'Barangay Kagawad', 'SK Chairperson', 'SK Kagawad', 'Secretary', 'Staff'];
            $newPosition = $_POST['position'];
            if (in_array($newPosition, $allowed_positions)) {
                $stmt = $conn->prepare("UPDATE users SET position = ? WHERE user_id = ?");
                $stmt->bind_param("si", $newPosition, $userId);
                if ($stmt->execute()) {
                    $_SESSION['user']['position'] = $newPosition;
                    $position = $newPosition;
                    $message = "Position updated successfully.";
                }
            }
        }

        // Delete Account
        if (isset($_POST['delete_account'])) {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            if ($stmt->execute()) {
                session_destroy();
                header("Location: login.php");
                exit();
            }
        }

        $conn->close();
    } catch(Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Barangay System</title>
    <link rel="stylesheet" href="settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 20px;
        }
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .settings-form .form-group {
            margin-bottom: 20px;
        }
        .btn-update {
            background-color: #1d3b71;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .danger-zone {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
   <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo-link">
                    <img src="logo.png" alt="Admin & Staff Logo" class="header-logo">
                </a>
                <h2>A Web-based Barangay Demographic Profiling System</h2>
                <?php if ($is_logged_in): ?>
                    <div class="welcome">
                        <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'User'); ?></p>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="welcome">
                        <a href="login.php" class="login-btn">Staff Login</a>
                    </div>
                <?php endif; ?>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="dashboard.php"><i class="fas fa-house-user"></i> Dashboard</a></li>
                    <li><a href="resident.php"><i class="fas fa-users"></i> Residents</a></li>
                    <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                    <li><a href="predictive.php"><i class="fas fa-brain"></i> Predictive Models</a></li>
                    <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                    <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                    <?php if ($is_logged_in): ?>
                        <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="settings-container">
                <h2><i class="fas fa-cog"></i> Account Settings</h2>

                <?php if ($message): ?>
                    <div class="alert <?= strpos($message, 'Error') === 0 ? 'alert-error' : 'alert-success' ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <button type="submit" class="btn-update">Update Email</button>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" placeholder="Enter new password (min 8 chars)">
                        <button type="submit" class="btn-update">Update Password</button>
                    </div>

                    <div class="form-group">
                        <label>Barangay Position</label>
                        <select name="position" required>
                            <option value="Barangay Captain" <?= $position === 'Barangay Captain' ? 'selected' : '' ?>>Barangay Captain</option>
                            <option value="Barangay Kagawad" <?= $position === 'Barangay Kagawad' ? 'selected' : '' ?>>Barangay Kagawad</option>
                            <option value="SK Chairperson" <?= $position === 'SK Chairperson' ? 'selected' : '' ?>>SK Chairperson</option>
                            <option value="SK Kagawad" <?= $position === 'SK Kagawad' ? 'selected' : '' ?>>SK Kagawad</option>
                            <option value="Secretary" <?= $position === 'Secretary' ? 'selected' : '' ?>>Secretary</option>
                            <option value="Staff" <?= $position === 'Staff' ? 'selected' : '' ?>>Staff</option>
                        </select>
                        <button type="submit" class="btn-update">Update Position</button>
                    </div>

                    <div class="form-group danger-zone">
                        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                        <button type="submit" name="delete_account" class="btn-danger" onclick="return confirm('Are you sure? This will delete your account permanently.')">
                            <i class="fas fa-trash-alt"></i> Delete Account
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>