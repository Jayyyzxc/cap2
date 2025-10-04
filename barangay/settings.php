<?php
session_start();
require_once 'config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user']);
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user']['user_id'] ?? null;
$email = $_SESSION['user']['email'] ?? '';
$position = $_SESSION['user']['position'] ?? 'Staff';
$full_name = $_SESSION['user']['full_name'] ?? 'User';
$message = "";

// Simulated profile completion
$profileCompletion = 75;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Email update
    if (isset($_POST['update_email']) && !empty($_POST['email'])) {
        $newEmail = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt->bind_param("si", $newEmail, $userId);
            if ($stmt->execute()) {
                $_SESSION['user']['email'] = $newEmail;
                $email = $newEmail;
                $message = "Email updated successfully.";
            }
        } else {
            $message = "Invalid email address.";
        }
    }

    // Password update
    if (isset($_POST['update_password']) && !empty($_POST['password'])) {
        if (strlen($_POST['password']) >= 8) {
            $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $newPassword, $userId);
            if ($stmt->execute()) {
                $message = "Password updated successfully.";
            }
        } else {
            $message = "Password must be at least 8 characters.";
        }
    }

    // Position update
    if (isset($_POST['update_position']) && !empty($_POST['position'])) {
        $allowed_positions = ['Barangay Captain','Barangay Kagawad','SK Chairperson','SK Kagawad','Secretary','Staff'];
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

    // Delete account
    if (isset($_POST['delete_account'])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        if ($stmt->execute()) {
            session_destroy();
            header("Location: login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Barangay System</title>
    <link rel="stylesheet" href="settings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .settings-form .form-group { margin-bottom: 24px; }
        .settings-form label { display: block; font-weight: 600; margin-bottom: 6px; }
        .settings-form input, .settings-form select { width: 100%; padding: 8px; margin-bottom: 8px; border-radius: 4px; border: 1px solid #ccc; }
        .settings-form .btn-update { background: #1d3b71; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; margin-top: 4px; }
        .settings-form .btn-update:hover { background: #274b99; }
        .settings-form .danger-zone { border-top: 1px solid #eee; padding-top: 18px; }
        .settings-form .btn-danger { background: #e74c3c; color: #fff; border: none; padding: 8px 18px; border-radius: 4px; cursor: pointer; }
        .settings-form .btn-danger:hover { background: #c0392b; }
        .alert { background: #eaf6e7; color: #2d7a2d; padding: 10px 18px; border-radius: 4px; margin-bottom: 18px; }
        .settings-header { display: flex; align-items: center; gap: 18px; margin-bottom: 24px; }
        .settings-header .profile-pic { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #1d3b71; }
        @media (max-width: 900px) {
            .dashboard-container { flex-direction: column; }
            .sidebar { width: 100%; }
            .main-content { padding: 12px; }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo-link">
                <img src="logo.png" alt="Logo" class="header-logo">
            </a>
            <h2>A Web-based Barangay Demographic Profiling System</h2>
            <div class="welcome">
                <p>Welcome, <?php echo htmlspecialchars($full_name); ?></p>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-house-user"></i> Dashboard</a></li>
                <li><a href="resident.php"><i class="fas fa-users"></i> Residents</a></li>
                <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="predictive.php"><i class="fas fa-brain"></i> Predictive Models</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="settings-container" style="display:flex;gap:32px;">
            <!-- LEFT: Settings Sidebar -->
            <div class="settings-sidebar" style="min-width:220px;">
                <div class="profile-progress" style="text-align:center;margin-bottom:32px;">
                    <svg class="progress-ring" width="80" height="80">
                        <circle class="progress-ring__circle" stroke="#ccc" stroke-width="6" fill="transparent" r="34" cx="40" cy="40"/>
                        <circle class="progress-ring__circle--value" stroke="#6c5ce7" stroke-width="6" fill="transparent" r="34" cx="40" cy="40"
                            stroke-dasharray="<?php echo 2 * pi() * 34; ?>"
                            stroke-dashoffset="<?php echo (1 - $profileCompletion/100) * (2 * pi() * 34); ?>"/>
                    </svg>
                    <p style="margin:8px 0 0;"><?php echo $profileCompletion; ?>% Complete</p>
                    <button class="btn-complete" style="margin-top:8px;background:#6c5ce7;color:#fff;border:none;padding:6px 14px;border-radius:4px;cursor:pointer;">Complete My Profile</button>
                </div>
                <div class="settings-list" style="display:flex;flex-direction:column;gap:8px;">
                    <button class="active" style="background:#f5f6fa;border:none;padding:10px 0;border-radius:4px;cursor:pointer;text-align:left;"><i class="fas fa-user-cog"></i> Account Settings</button>
                    <button style="background:#f5f6fa;border:none;padding:10px 0;border-radius:4px;cursor:pointer;text-align:left;"><i class="fas fa-paint-brush"></i> Appearance</button>
                    <button style="background:#f5f6fa;border:none;padding:10px 0;border-radius:4px;cursor:pointer;text-align:left;"><i class="fas fa-shield-alt"></i> Security</button>
                    <button style="background:#f5f6fa;border:none;padding:10px 0;border-radius:4px;cursor:pointer;text-align:left;"><i class="fas fa-cogs"></i> Other Settings</button>
                </div>
            </div>

            <!-- RIGHT: Settings Panel -->
            <div class="settings-content" style="flex:1;">
                <div class="settings-header">
                    <img src="avatar.png" alt="Profile" class="profile-pic">
                    <div>
                        <h2><?php echo htmlspecialchars($full_name); ?></h2>
                        <p><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <button type="submit" name="update_email" class="btn-update">Update Email</button>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" placeholder="Enter new password (min 8 chars)">
                        <button type="submit" name="update_password" class="btn-update">Update Password</button>
                    </div>
                    <div class="form-group">
                        <label>Barangay Position</label>
                        <select name="position">
                            <option value="Barangay Captain" <?= $position==='Barangay Captain'?'selected':'' ?>>Barangay Captain</option>
                            <option value="Barangay Kagawad" <?= $position==='Barangay Kagawad'?'selected':'' ?>>Barangay Kagawad</option>
                            <option value="SK Chairperson" <?= $position==='SK Chairperson'?'selected':'' ?>>SK Chairperson</option>
                            <option value="SK Kagawad" <?= $position==='SK Kagawad'?'selected':'' ?>>SK Kagawad</option>
                            <option value="Secretary" <?= $position==='Secretary'?'selected':'' ?>>Secretary</option>
                            <option value="Staff" <?= $position==='Staff'?'selected':'' ?>>Staff</option>
                        </select>
                        <button type="submit" name="update_position" class="btn-update">Update Position</button>
                    </div>
                    <div class="form-group danger-zone">
                        <h3 style="color:#e74c3c;">Delete</h3>
                        <button type="submit" name="delete_account" class="btn-danger">Delete Account</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
