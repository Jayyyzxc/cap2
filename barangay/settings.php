<?php
session_start();
require_once 'config.php';

// Session Check
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

// Extract session data
$user = $_SESSION['user'];
$userId = $user['user_id'] ?? $user['id'] ?? null;
$full_name = $user['full_name'] ?? 'User';
$message = "";

// Database Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch user settings
$stmt = $conn->prepare("SELECT font_style, notification_pref, language_pref, password FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$userSettings = $stmt->get_result()->fetch_assoc();

$font_style = $userSettings['font_style'] ?? 'default';
$notification_pref = $userSettings['notification_pref'] ?? 'on';
$language_pref = $userSettings['language_pref'] ?? 'English';
$current_password_hash = $userSettings['password'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update Personal Information
    if (isset($_POST['update_personal'])) {
        $stmt = $conn->prepare("
            UPDATE users 
            SET first_name=?, last_name=?, middle_name=?, birthdate=?, gender=?, civil_status=?, 
                address=?, contact_number=?, username=?, email=?, position=?, official_id=?, start_term=?, end_term=? 
            WHERE user_id=?
        ");
        $stmt->bind_param(
            "sssssssssssssi",
            $_POST['first_name'], $_POST['last_name'], $_POST['middle_name'],
            $_POST['birthdate'], $_POST['gender'], $_POST['civil_status'],
            $_POST['address'], $_POST['contact_number'], $_POST['username'],
            $_POST['email'], $_POST['position'], $_POST['official_id'],
            $_POST['start_term'], $_POST['end_term'], $userId
        );
        $message = $stmt->execute() ? "✅ Personal information updated successfully." : "⚠️ Failed to update personal information.";
    }

    // Change Password
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $current_password_hash)) {
            $message = "❌ Current password is incorrect.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "⚠️ New password and confirmation do not match.";
        } elseif (strlen($newPassword) < 8) {
            $message = "⚠️ New password must be at least 8 characters long.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            $message = $stmt->execute() ? "✅ Password changed successfully!" : "⚠️ Failed to update password.";
        }
    }

    // Update Preferences
    if (isset($_POST['update_preferences'])) {
        $notification_pref = $_POST['notification_pref'];
        $language_pref = $_POST['language_pref'];
        $stmt = $conn->prepare("UPDATE users SET notification_pref=?, language_pref=? WHERE user_id=?");
        $stmt->bind_param("ssi", $notification_pref, $language_pref, $userId);
        $stmt->execute();
        $message = "⚙️ Preferences updated.";
    }

    // Refresh user settings
    $stmt = $conn->prepare("SELECT font_style, notification_pref, language_pref, password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $userSettings = $stmt->get_result()->fetch_assoc();
    $font_style = $userSettings['font_style'] ?? 'default';
    $notification_pref = $userSettings['notification_pref'] ?? 'on';
    $language_pref = $userSettings['language_pref'] ?? 'English';
    $current_password_hash = $userSettings['password'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - Barangay System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="settings.css">
<style>
:root {
    --light-blue: #7da2ce;
}
body {
    background-color: var(--light-blue);
    color: #222;
    font-family: <?= $font_style === 'default' ? "'Segoe UI', Arial, sans-serif" : $font_style; ?>;
}
.main-content {
    padding: 40px 0;
    min-height: 100vh;
}
.settings-content {
    background: #fff;
    border-radius: 18px;
    margin: 0 auto;
    padding: 48px 60px 40px 60px;
    box-shadow: 0 4px 32px rgba(44,62,80,0.07);
    min-width: 420px;
    max-width: 700px;
    display: flex;
    flex-direction: column;
}
.settings-content h2 {
    color: #222;
    font-size: 1.7rem;
    font-weight: 700;
    margin-bottom: 28px;
    letter-spacing: -0.5px;
}
.settings-form {
    display: flex;
    flex-direction: column;
}
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 18px;
}
.form-group {
    flex: 1;
    min-width: 180px;
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-weight: 500;
    margin-bottom: 7px;
    color: #222;
    font-size: 15px;
    letter-spacing: 0.1px;
}
.settings-form input,
.settings-form select {
    border: 1.5px solid #e4e7ec;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 15px;
    background: #f8fafc;
    transition: border 0.2s;
}
.settings-form input:focus,
.settings-form select:focus {
    border-color: #1d3b71;
    background: #fff;
    outline: none;
}
.btn-update {
    background: #1d3b71;
    color: #fff;
    padding: 12px 32px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    width: fit-content;
    margin-top: 18px;
    border: none;
    transition: background 0.2s;
}
.btn-update:hover {
    background: #16305a;
}
.alert {
    background: #e3f2fd;
    border-left: 4px solid #1d3b71;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 22px;
    color: #333;
    font-size: 15px;
}
@media (max-width: 900px) {
    .settings-content {
        padding: 30px 10px;
        min-width: 0;
        max-width: 100%;
    }
    .form-row {
        flex-direction: column;
        gap: 0;
    }
}
</style>
<script>
function toggleDropdown(id) {
    document.querySelectorAll('.dropdown-section').forEach(el => el.style.display = 'none');
    document.getElementById(id).style.display = 'block';
}
document.addEventListener('DOMContentLoaded', function() {
    toggleDropdown('account-settings');
});
</script>
</head>
<body>
<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php"><img src="logo.png" alt="Logo" class="header-logo"></a>
            <h2>A Web-based Barangay Demographic Profiling System</h2>
            <p>Welcome, <?= htmlspecialchars($full_name) ?></p>
            <a href="logout.php" class="logout-btn">Logout</a>
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
        <div class="settings-content">
            <?php if ($message): ?>
                <div class="alert"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- Personal Info -->
            <div id="account-settings" class="dropdown-section">
                <h2>Personal information</h2>
                <form method="POST" class="settings-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Contact number</label>
                            <input type="text" name="contact_number" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Birthdate</label>
                            <input type="date" name="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="Male" <?= ($user['gender'] ?? '')=='Male'?'selected':'' ?>>Male</option>
                                <option value="Female" <?= ($user['gender'] ?? '')=='Female'?'selected':'' ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Civil status</label>
                            <select name="civil_status">
                                <option value="Single" <?= ($user['civil_status'] ?? '')=='Single'?'selected':'' ?>>Single</option>
                                <option value="Married" <?= ($user['civil_status'] ?? '')=='Married'?'selected':'' ?>>Married</option>
                                <option value="Widowed" <?= ($user['civil_status'] ?? '')=='Widowed'?'selected':'' ?>>Widowed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Position</label>
                            <input type="text" name="position" value="<?= htmlspecialchars($user['position'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Official ID</label>
                            <input type="text" name="official_id" value="<?= htmlspecialchars($user['official_id'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Start term</label>
                            <input type="date" name="start_term" value="<?= htmlspecialchars($user['start_term'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>End term</label>
                            <input type="date" name="end_term" value="<?= htmlspecialchars($user['end_term'] ?? '') ?>">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:24px;">
                        <button type="reset" class="btn-update" style="background:#f5f5f5; color:#333; border:1px solid #e4e7ec;">Cancel</button>
                        <button type="submit" name="update_personal" class="btn-update">Save</button>
                    </div>
                </form>
            </div>

            <!-- Password -->
            <div id="password-settings" class="dropdown-section" style="display:none;">
                <h2>Security</h2>
                <form method="POST" class="settings-form">
                    <label>Current password</label>
                    <input type="password" name="current_password" required>

                    <label>New password</label>
                    <input type="password" name="new_password" minlength="8" required>

                    <label>Confirm new password</label>
                    <input type="password" name="confirm_password" minlength="8" required>

                    <button type="submit" name="change_password" class="btn-update">Change Password</button>
                </form>
            </div>

            <!-- Preferences -->
            <div id="other-settings" class="dropdown-section" style="display:none;">
                <h2>Preferences</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Notifications</label>
                            <select name="notification_pref">
                                <option value="on" <?= $notification_pref==='on'?'selected':'' ?>>Enable</option>
                                <option value="off" <?= $notification_pref==='off'?'selected':'' ?>>Disable</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Language</label>
                            <select name="language_pref">
                                <option value="English" <?= $language_pref==='English'?'selected':'' ?>>English</option>
                                <option value="Filipino" <?= $language_pref==='Filipino'?'selected':'' ?>>Filipino</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; margin-top:24px;">
                        <button type="reset" class="btn-update" style="background:#f5f5f5; color:#333; border:1px solid #e4e7ec;">Cancel</button>
                        <button type="submit" name="update_preferences" class="btn-update">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
<script>
    // Only show one section at a time
    function toggleDropdown(id) {
        document.querySelectorAll('.dropdown-section').forEach(el => el.style.display = 'none');
        document.getElementById(id).style.display = 'block';
    }
    document.addEventListener('DOMContentLoaded', function() {
        toggleDropdown('account-settings');
    });
</script>
</body>
</html>
