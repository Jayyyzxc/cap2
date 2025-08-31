<?php
require_once 'config.php';
require_once 'functions.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Check if public access is enabled
$public_access = $settings['public_access'] ?? 1;
$is_logged_in = isLoggedIn();

// Get events from database
$events = [];
$event_query = "SELECT event_id, title FROM events ORDER BY title";
$result = $conn->query($event_query);
if ($result) {
    $events = $result->fetch_all(MYSQLI_ASSOC);
}

// Process form submission
$forecast_data = [];
// Map census event interests to event titles (adjust as needed)
$event_interest_map = [
    'sports_interest' => 'Sports Event',
    'health_interest' => 'Health Event',
    'nutrition_interest' => 'Nutrition Program',
    'assistive_interest' => 'Assistive Devices',
    'livelihood_interest' => 'Livelihood Training',
    'aid_interest' => 'Financial Aid',
    'disaster_interest' => 'Disaster Preparedness',
    'road_clearing_interest' => 'Road Clearing',
    'cleanup_interest' => 'Clean-up Drive',
    'waste_mgmt_interest' => 'Waste Management',
];

// For each event type, get count of "Yes" from census_submissions
$auto_forecasts = [];
$interest_counts = [];
// Gather counts for each event interest
foreach ($event_interest_map as $interest_col => $event_name) {
    $stmt = $conn->prepare("SELECT COUNT(*) as yes_count FROM census_submissions WHERE $interest_col = 'Yes'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : ['yes_count' => 0];
    $interest_counts[] = [
        'interest_col' => $interest_col,
        'event_name' => $event_name,
        'yes_count' => (int)$row['yes_count']
    ];
}
// Sort by yes_count descending and take top 3
usort($interest_counts, function($a, $b) {
    return $b['yes_count'] <=> $a['yes_count'];
});
$top_interests = array_slice($interest_counts, 0, 3);
$auto_forecasts = [];
// If Show Top 3 Events button is pressed, ignore event selection
$show_top3 = isset($_POST['show_top3']);
$selected_event = ($show_top3) ? '' : (isset($_POST['event']) ? $_POST['event'] : '');
// Get selected time period, default to 6 if not set or invalid
$valid_periods = ['3', '6', '12'];
$selected_period = (isset($_POST['time_period']) && in_array($_POST['time_period'], $valid_periods)) ? (int)$_POST['time_period'] : 6;
// Build a map of event_name => yes_count for all event interests
$all_interest_counts = [];
foreach ($event_interest_map as $interest_col => $event_name) {
    $stmt = $conn->prepare("SELECT COUNT(*) as yes_count FROM census_submissions WHERE $interest_col = 'Yes'");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : ['yes_count' => 0];
    $all_interest_counts[$event_name] = (int)$row['yes_count'];
}
if (!$show_top3 && $selected_event && isset($all_interest_counts[$selected_event])) {
    // Show only the selected event, use selected period
    $base = $all_interest_counts[$selected_event];
    $months = $selected_period;
    $labels = [];
    $values = [];
    for ($i = 1; $i <= $months; $i++) {
        $labels[] = "Month $i";
        $values[] = max(0, $base + rand(-5, 10));
    }
    $auto_forecasts[] = [
        'labels' => $labels,
        'values' => $values,
        'event_name' => $selected_event,
        'time_period' => $months
    ];
} else {
    // Show top 3 events by yes_count, use selected period
    $interest_counts_sorted = $interest_counts;
    usort($interest_counts_sorted, function($a, $b) {
        return $b['yes_count'] <=> $a['yes_count'];
    });
    $top3 = array_slice($interest_counts_sorted, 0, 3);
    foreach ($top3 as $interest) {
        $base = $interest['yes_count'];
        $event_name = $interest['event_name'];
        $months = $selected_period;
        $labels = [];
        $values = [];
        for ($i = 1; $i <= $months; $i++) {
            $labels[] = "Month $i";
            $values[] = max(0, $base + rand(-5, 10));
        }
        $auto_forecasts[] = [
            'labels' => $labels,
            'values' => $values,
            'event_name' => $event_name,
            'time_period' => $months
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictive Models - Barangay Profiling System</title>
    <link rel="stylesheet" href="predictive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li><a href="predictive.php" class="active"><i class="fas fa-brain"></i> Predictive Models</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (!$is_logged_in && !$public_access): ?>
            <div class="access-denied">
                <i class="fas fa-lock"></i>
                <h2>Public Access Disabled</h2>
                <p>Please login to view this page</p>
                <a href="login.php" class="login-btn">Login</a>
            </div>
        <?php else: ?>
            <div class="dashboard-header">
                <h1><i class="fas fa-brain"></i> Predictive Analytics</h1>
            </div>

            <div class="predictive-form-container">
                <form method="POST" action="predictive.php">
                    <div class="form-group">
                        <label for="event"><i class="fas fa-calendar-alt"></i> Select Event</label>
                        <select id="event" name="event" <?php if (isset($_POST['show_top3'])) { ?><?php } else { echo 'required'; } ?>>
                            <option value="">-- Select an Event --</option>
                            <?php foreach ($event_interest_map as $interest_col => $event_name): ?>
                                <option value="<?php echo htmlspecialchars($event_name); ?>" <?php if ($selected_event === $event_name) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($event_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="time_period"><i class="fas fa-clock"></i> Select Time Period</label>
                        <select id="time_period" name="time_period" <?php if (isset($_POST['show_top3'])) { ?><?php } else { echo 'required'; } ?>>
                            <option value="">-- Select Time Period --</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12">1 Year</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                        <button type="submit" name="generate_forecast" class="generate-btn">
                            <i class="fas fa-chart-line"></i> Generate Forecast
                        </button>
                        <button type="submit" name="show_top3" class="generate-btn">
                            <i class="fas fa-star"></i> Show Top 3 Events
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!empty($auto_forecasts)): ?>
                <?php foreach ($auto_forecasts as $idx => $forecast): ?>
                    <div class="forecast-results" style="padding-bottom: 20px;">
                        <h2>Forecast Results for <?php echo htmlspecialchars($forecast['event_name']); ?></h2>
                        <p>Time Period: <?php echo $forecast['time_period']; ?> Months</p>
                        <div class="chart-container" style="padding-bottom: 20px;">
                            <canvas id="forecastChart<?php echo $idx; ?>"></canvas>
                        </div>
                    </div>
                <?php endforeach; ?>
                <script>
                <?php foreach ($auto_forecasts as $idx => $forecast): ?>
                    const forecastCtx<?php echo $idx; ?> = document.getElementById('forecastChart<?php echo $idx; ?>').getContext('2d');
                    new Chart(forecastCtx<?php echo $idx; ?>, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($forecast['labels']); ?>,
                            datasets: [{
                                label: 'Predicted Attendance',
                                data: <?php echo json_encode($forecast['values']); ?>,
                                backgroundColor: 'rgba(29, 59, 113, 0.2)',
                                borderColor: 'rgba(29, 59, 113, 1)',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Event Attendance Forecast',
                                    font: {
                                        size: 16,
                                        weight: 'bold'
                                    }
                                },
                                legend: {
                                    position: 'top',
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Number of Attendees'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Time Period'
                                    }
                                }
                            }
                        }
                    });
                <?php endforeach; ?>
                </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>