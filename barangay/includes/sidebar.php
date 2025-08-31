
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Reports</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo-link">
                    <img src="dhvsu.png" alt="Admin & Staff Logo" class="header-logo">
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
                    <li><a href="reports.php" class="active"><i class="fas fa-file-alt"></i> Reports</a></li>
                    <?php if ($is_logged_in): ?>
                        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
