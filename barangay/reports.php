<?php
session_start();
require_once 'config.php';

// Check login
$is_logged_in = isset($_SESSION['user']); // staff = logged in
$success = "";

// Database connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// Create table if not exists
$conn->exec("
    CREATE TABLE IF NOT EXISTS reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        starred TINYINT(1) DEFAULT 0,
        pinned TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Handle new report submission (for residents only, not logged in)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report']) && !$is_logged_in) {
    $stmt = $conn->prepare("INSERT INTO reports (message) VALUES (:msg)");
    $stmt->execute([':msg' => trim($_POST['message'])]);
    $success = "Your report has been sent anonymously.";
}

// Handle staff actions (only if logged in)
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['id'])) {
        $id = intval($_POST['id']);
        switch ($_POST['action']) {
            case 'star':
                $conn->prepare("UPDATE reports SET starred = 1 - starred WHERE id = :id")->execute([':id' => $id]);
                break;
            case 'pin':
                $conn->prepare("UPDATE reports SET pinned = 1 - pinned WHERE id = :id")->execute([':id' => $id]);
                break;
            case 'delete':
                $conn->prepare("DELETE FROM reports WHERE id = :id")->execute([':id' => $id]);
                break;
        }
    }
}

// Fetch reports for staff (if logged in)
$reports = [];
if ($is_logged_in) {
    $stmt = $conn->query("SELECT * FROM reports ORDER BY pinned DESC, created_at DESC");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Barangay Reports</title>
<link rel="stylesheet" href="reports.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                    <a href="logout.php" class="logout-btn" style="color:#fff;">Logout</a>
                </div>
            <?php else: ?>
                <div class="welcome">
                    <a href="login.php" class="login-btn" style="color:#fff;">Staff Login</a>
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
                <li><a href="reports.php" class="active"><i class="fas fa-file-alt"></i> Reports</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if ($is_logged_in): ?>
            <h1><i class="fas fa-inbox"></i> Staff Inbox</h1>
            <?php if (empty($reports)): ?>
                <p>No reports submitted yet.</p>
            <?php else: ?>
                <div class="inbox-container">
                    <?php foreach ($reports as $report): ?>
                        <div class="message-card <?php echo $report['starred'] ? 'starred' : ''; ?> <?php echo $report['pinned'] ? 'pinned' : ''; ?>">
                            <div class="message-content">
                                <?php if ($report['pinned']): ?><span class="pinned-icon">📌 Pinned</span><?php endif; ?>
                                <p><?php echo nl2br(htmlspecialchars($report['message'])); ?></p>
                                <span class="timestamp"><?php echo $report['created_at']; ?></span>
                            </div>
                            <div class="message-actions">
                                <form method="POST" style="display:flex; gap:8px;">
                                    <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                    <button class="action-btn" name="action" value="star" title="Star"><i class="fas fa-star"></i></button>
                                    <button class="action-btn" name="action" value="pin" title="Pin"><i class="fas fa-thumbtack"></i></button>
                                    <button class="action-btn" name="action" value="delete" title="Delete" onclick="return confirm('Delete this report?');"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h1><i class="fas fa-comment-dots"></i> Submit a Report</h1>
            <?php if (!empty($success)): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
            <form method="POST">
                <textarea name="message" rows="6" placeholder="Type your report or message here..." required></textarea>
                <br>
                <button type="submit" name="submit_report"><i class="fas fa-paper-plane"></i> Send Report</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
