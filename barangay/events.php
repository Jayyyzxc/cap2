<?php
session_start();
require_once 'config.php'; // Your database configuration file

// Check if user is logged in
$is_logged_in = isset($_SESSION['user']);
$is_captain = $is_logged_in && ($_SESSION['user']['role'] ?? '') === 'captain';

// Database connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Predictive Analytics - Suggest Events
    $event_suggestions = [];
    $query = "SELECT
                SUM(sports_interest = 'Yes') AS sports,
                SUM(health_interest = 'Yes') AS health,
                SUM(nutrition_interest = 'Yes') AS nutrition,
                SUM(assistive_interest = 'Yes') AS assistive,
                SUM(livelihood_interest = 'Yes') AS livelihood,
                SUM(aid_interest = 'Yes') AS aid,
                SUM(disaster_interest = 'Yes') AS disaster,
                SUM(road_clearing_interest = 'Yes') AS road,
                SUM(cleanup_interest = 'Yes') AS cleanup,
                SUM(waste_mgmt_interest = 'Yes') AS waste
              FROM census_submissions";

    $analytics = $conn->query($query)->fetch(PDO::FETCH_ASSOC);

    $currentMonth = date('m');
    $currentYear = date('Y');
    $today = date('Y-m-d');

    if ($analytics) {
        $suggestions_map = [
            'sports' => ["Basketball League for the Youths", "Volleyball Tournaments"],
            'health' => ["Medical Check-ups", "Vaccination Drives"],
            'nutrition' => ["Health and Nutrition Feeding Program"],
            'assistive' => ["Distribution of Assistive Devices"],
            'aid' => ["Financial Assistance / Ayuda for PWDs", "Financial Aid or Assistance for PWDs"],
            'livelihood' => ["Livelihood Training Programs"],
            'disaster' => ["Preparations for Disasters or Calamities"],
            'road' => ["Road Clearing Operations"],
            'cleanup' => ["Community Clean-Up Drives"],
            'waste' => ["Waste Management Programs"]
        ];

        foreach ($analytics as $key => $count) {
            if ($count > 0 && isset($suggestions_map[$key])) {
                foreach ($suggestions_map[$key] as $eventTitle) {
                    // Check if event already exists this month
                    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE title = :title AND MONTH(event_date) = :month AND YEAR(event_date) = :year");
                    $checkStmt->execute([
                        ':title' => $eventTitle,
                        ':month' => $currentMonth,
                        ':year' => $currentYear
                    ]);

                    $exists = $checkStmt->fetchColumn();
                    if (!$exists) {
                        // Insert predicted event for next available day in current month
                        $suggestedDate = date('Y-m-d', strtotime("+3 days"));
                        $insertStmt = $conn->prepare("INSERT INTO events (event_id, title, description, event_date, location, organizer) 
                                                     VALUES (:event_id, :title, :description, :event_date, :location, :organizer)");
                        $insertStmt->execute([
                            ':event_id' => uniqid(),
                            ':title' => $eventTitle,
                            ':description' => 'Automatically predicted event based on census data.',
                            ':event_date' => $suggestedDate,
                            ':location' => 'Barangay Hall',
                            ':organizer' => 'System Prediction'
                        ]);
                    }
                }
            }
        }
    }

    // Handle form submission for new events
    if ($is_captain && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
        $title = $_POST['title'];
        $date = $_POST['date'];
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';

        $stmt = $conn->prepare("INSERT INTO events (event_id, title, description, event_date, location, organizer) 
                               VALUES (:event_id, :title, :description, :event_date, :location, :organizer)");
        $stmt->execute([
            ':event_id' => uniqid(),
            ':title' => $title,
            ':description' => $description,
            ':event_date' => $date,
            ':location' => $location,
            ':organizer' => $_SESSION['user']['full_name']
        ]);

        $_SESSION['message'] = "Event created successfully!";
        header("Location: events.php");
        exit();
    }

    // Handle event deletion
    if ($is_captain && isset($_GET['delete'])) {
        $stmt = $conn->prepare("DELETE FROM events WHERE id = :id");
        $stmt->execute([':id' => $_GET['delete']]);
        $_SESSION['message'] = "Event deleted successfully!";
        header("Location: events.php");
        exit();
    }

    // Get all events
    $stmt = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fix: $monthEvents must be defined for calendar highlighting
    $monthEvents = $events;

    // --- Rank and filter top 3 predicted events for Upcoming Events ---
    $predicted_titles = [
        "Basketball League for the Youths",
        "Volleyball Tournaments",
        "Medical Check-ups",
        "Vaccination Drives",
        "Health and Nutrition Feeding Program",
        "Distribution of Assistive Devices",
        "Financial Assistance / Ayuda for PWDs",
        "Financial Aid or Assistance for PWDs",
        "Livelihood Training Programs",
        "Preparations for Disasters or Calamities",
        "Road Clearing Operations",
        "Community Clean-Up Drives",
        "Waste Management Programs"
    ];

    // Count predicted event occurrences
    $predicted_counts = [];
    foreach ($events as $event) {
        if (in_array($event['title'], $predicted_titles)) {
            $predicted_counts[$event['title']] = ($predicted_counts[$event['title']] ?? 0) + 1;
        }
    }

    // Get top 3 predicted event titles by count
    arsort($predicted_counts);
    $top3_titles = array_slice(array_keys($predicted_counts), 0, 3);

    // Filter events to only show top 3 predicted events in Upcoming Events
    $top3_events = array_filter($events, function($event) use ($top3_titles) {
        return in_array($event['title'], $top3_titles);
    });

} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Events</title>
    <link rel="stylesheet" href="events.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                    <li><a href="events.php" class="active"><i class="fas fa-calendar-alt"></i> Events</a></li>
                    <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                    <?php if ($is_logged_in): ?>
                        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1> <i class="fas fa-calendar-alt"></i> Event and Program Planning</h1>
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="events-container">
                <div class="calendar-section">
                    <div class="calendar-header">
                        <h2><?php echo date('F Y'); ?></h2>
                    </div>
                    <div class="calendar">
                        <?php
                        // Generate calendar
                        $firstDay = date('N', strtotime(date('Y-m-01')));
                        $daysInMonth = date('t');
                        $currentDay = 1;
                        
                        echo '<div class="calendar-grid">';
                        // Day names
                        $dayNames = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
                        foreach ($dayNames as $day) {
                            echo '<div class="calendar-day-name">' . $day . '</div>';
                        }
                        
                        // Empty cells for days before the first day of month
                        for ($i = 1; $i < $firstDay; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        
                        // Days of month
                        while ($currentDay <= $daysInMonth) {
                            $currentDate = date('Y-m-') . str_pad($currentDay, 2, '0', STR_PAD_LEFT);
                            $hasEvent = false;
                            $eventTitles = [];
                            
                            foreach ($monthEvents as $event) {
                                if (date('Y-m-d', strtotime($event['event_date'])) === $currentDate) {
                                    $hasEvent = true;
                                    $eventTitles[] = $event['title'];
                                }
                            }
                            
                            $class = $hasEvent ? 'has-event' : '';
                            if (date('Y-m-d') === $currentDate) {
                                $class .= ' current-day';
                            }
                            
                            echo '<div class="calendar-day ' . $class . '">';
                            echo '<div class="day-number">' . $currentDay . '</div>';
                            
                            if ($hasEvent) {
                                echo '<div class="event-indicator" title="' . htmlspecialchars(implode(', ', $eventTitles)) . '">';
                                echo '<i class="fas fa-circle"></i>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                            
                            $currentDay++;
                            if ((($firstDay + $currentDay - 2) % 7) == 0 && $currentDay <= $daysInMonth) {
                                echo '</div><div class="calendar-grid">';
                            }
                        }
                        
                        // Empty cells after last day of month
                        $remainingCells = 7 - (($firstDay + $daysInMonth - 1) % 7);
                        if ($remainingCells < 7) {
                            for ($i = 0; $i < $remainingCells; $i++) {
                                echo '<div class="calendar-day empty"></div>';
                            }
                        }
                        
                        echo '</div>';
                        ?>
                    </div>
                </div>
                
                <div class="events-section">    
                    <div class="events-list">
                        <h2>Upcoming Events</h2>
                        <?php if (empty($top3_events)): ?>
                            <p>No upcoming events scheduled.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($top3_events as $event): ?>
                                    <li>
                                        <div class="event-item">
                                            <div class="event-date">
                                                <?php echo date('M j', strtotime($event['event_date'])); ?>
                                            </div>
                                            <div class="event-details">
                                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                                <p><?php echo htmlspecialchars($event['description']); ?></p>
                                                <div class="event-meta">
                                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($event['organizer']); ?></span>
                                                </div>
                                            </div>
                                            <?php if ($is_captain): ?>
                                                <div class="event-actions">
                                                    <a href="?delete=<?php echo $event['id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this event?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($is_captain): ?>
                        <div class="create-event">
                            <h2>Create New Event</h2>
                            <form method="POST" action="events.php">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="date">Date</label>
                                    <input type="date" id="date" name="date" required>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="location">Location</label>
                                    <input type="text" id="location" name="location">
                                </div>
                                <button type="submit" class="create-btn">Create</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="events.js"></script>
</body>
</html>
