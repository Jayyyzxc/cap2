<?php
session_start();
require_once 'config.php'; // Your database configuration file

// Check if user is logged in and is a barangay official (captain or official)
$is_logged_in = isset($_SESSION['user']);
$is_official = $is_logged_in && isset($_SESSION['user']['role']) && in_array($_SESSION['user']['role'], ['captain', 'official']);

// Initialize variables
$filters = [
    'date_from' => '',
    'date_to' => '',
    'age_group' => '',
    'purok_id' => ''
];
$residents = [];
$report_data = [];

// Database connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all puroks for filter dropdown - FIXED column name
    $puroks = $conn->query("SELECT * FROM puroks ORDER BY purok_id")->fetchAll(PDO::FETCH_ASSOC);
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get filter values
        $filters = [
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'age_group' => $_POST['age_group'] ?? '',
            'purok_id' => $_POST['purok_id'] ?? ''
        ];
        
        // Build SQL query based on filters
        $sql = "SELECT r.*, p.purok_name FROM residents r LEFT JOIN puroks p ON r.purok_id = p.purok_id WHERE 1=1";
        $params = [];
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $sql .= " AND r.date_registered >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND r.date_registered <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        // Age group filter
        if (!empty($filters['age_group'])) {
            switch ($filters['age_group']) {
                case 'child':
                    $sql .= " AND r.age < 13";
                    break;
                case 'teen':
                    $sql .= " AND r.age BETWEEN 13 AND 19";
                    break;
                case 'adult':
                    $sql .= " AND r.age BETWEEN 20 AND 59";
                    break;
                case 'senior':
                    $sql .= " AND r.age >= 60";
                    break;
            }
        }
        
        // Purok filter
        if (!empty($filters['purok_id'])) {
            $sql .= " AND r.purok_id = :purok_id";
            $params[':purok_id'] = $filters['purok_id'];
        }
        
        $sql .= " ORDER BY r.last_name, r.first_name";
        
        // Prepare and execute query
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Handle report paragraph submission
        $submitted_report_paragraph = '';
        if (isset($_POST['submit_report_paragraph']) && isset($_POST['report_paragraph'])) {
            $submitted_report_paragraph = trim($_POST['report_paragraph']);
        }
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Reports</title>
    <link rel="stylesheet" href="reports.css">
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
            <div class="content-header">
                <h1><i class="fas fa-file-alt"></i> Generate Reports</h1>
            </div>
            
            <div class="reports-container">
                <form method="POST" action="reports.php">
                    <div class="export-options">
                        <?php if ($is_logged_in): ?>
                        <div class="export-buttons">
                            <!-- PDF and CSV export buttons removed -->
                            <?php if (isset($_POST['make_a_report'])): ?>
                                <div class="make-report-paragraph" style="margin-top: 16px;">
                                    <label for="report_purok" style="font-weight: bold; display: block; margin-bottom: 6px;">
                                        <i class="fas fa-map-marker-alt"></i> Select Purok for this Report
                                    </label>
                                    <select id="report_purok" name="report_purok" style="width:100%;max-width:100%;padding:8px;border-radius:6px;border:1px solid #ccc;margin-bottom:12px;">
                                        <option value="">-- Select Purok --</option>
                                        <?php for ($i = 1; $i <= 7; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo (isset($_POST['report_purok']) && $_POST['report_purok'] == $i) ? 'selected' : ''; ?>><?php echo 'Purok ' . $i; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <label for="report_paragraph" style="font-weight: bold; display: block; margin-bottom: 6px;">
                                        <i class="fas fa-file-alt"></i> Write Your Report
                                    </label>
                                    <textarea id="report_paragraph" name="report_paragraph" rows="10" style="width:100%;max-width:100%;padding:12px;border-radius:6px;border:1.5px solid #1d3b71;background:#f9f9f9;font-family:monospace;font-size:16px;resize:vertical;" placeholder="Type your report here..."><?php echo isset($_POST['report_paragraph']) ? htmlspecialchars($_POST['report_paragraph']) : ''; ?></textarea>
                                    <button type="submit" name="submit_report_paragraph" value="submit_report_paragraph" class="export-btn" style="background:#1d3b71;color:#fff;margin-top:8px;">
                                        <i class="fas fa-paper-plane"></i> Submit Report
                                    </button>
                                </div>
                            <?php else: ?>
                                <button type="submit" name="make_a_report" value="make_a_report" class="export-btn" style="background:#1d3b71;color:#fff;margin-top:8px;">
                                    <i class="fas fa-file-alt"></i> Make a Report
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="export-buttons" style="margin-bottom: 16px;">
                            <span style="color: #888; font-size: 15px;">Please log in to generate or export reports.</span>
                        </div>
                        <?php endif; ?>
                        <div class="filters-section">
                            <h2>Select Filters</h2>
                            <div class="filter-group">
                                <label for="date_from">Date From</label>
                                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="date_to">Date To</label>
                                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="age_group">Age Group</label>
                                <select id="age_group" name="age_group">
                                    <option value="">All Ages</option>
                                    <option value="child" <?php echo $filters['age_group'] === 'child' ? 'selected' : ''; ?>>Child (0-12)</option>
                                    <option value="teen" <?php echo $filters['age_group'] === 'teen' ? 'selected' : ''; ?>>Teen (13-19)</option>
                                    <option value="adult" <?php echo $filters['age_group'] === 'adult' ? 'selected' : ''; ?>>Adult (20-59)</option>
                                    <option value="senior" <?php echo $filters['age_group'] === 'senior' ? 'selected' : ''; ?>>Senior (60+)</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="purok_id">Barangay Purok</label>
                                <select id="purok_id" name="purok_id">
                                    <option value="">All Puroks</option>
                                    <?php for ($i = 1; $i <= 7; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $filters['purok_id'] == $i ? 'selected' : ''; ?> >
                                            <?php echo 'Purok ' . $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" name="apply_filters" class="apply-filters">Apply Filters</button>
                        </div>
                    </div>
                    <div class="report-preview">
                        <h2>Report Preview</h2>
                        <?php if (!empty($submitted_report_paragraph)): ?>
                            <div class="submitted-report" style="background:#f5f7fa;border:1.5px solid #1d3b71;padding:18px 20px;margin-bottom:18px;border-radius:8px;">
                                <h3 style="margin-top:0;margin-bottom:8px;color:#1d3b71;font-size:20px;"><i class="fas fa-sticky-note"></i> Submitted Report</h3>
                                <pre style="white-space:pre-wrap;font-family:inherit;font-size:16px;margin:0;"><?php echo htmlspecialchars($submitted_report_paragraph); ?></pre>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($residents)): ?>
                            <div class="report-table-container">
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Purok</th>
                                            <th>Address</th>
                                            <th>Contact</th>
                                            <th>Civil Status</th>
                                            <th>Occupation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($residents as $resident): ?>
                                            <tr>
                                                <td><?php echo $resident['id']; ?></td>
                                                <td><?php echo htmlspecialchars($resident['last_name'] . ', ' . $resident['first_name']); ?></td>
                                                <td><?php echo $resident['age']; ?></td>
                                                <td><?php echo htmlspecialchars($resident['gender']); ?></td>
                                                <td><?php echo htmlspecialchars($resident['purok'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($resident['address']); ?></td>
                                                <td><?php echo htmlspecialchars($resident['contact_number']); ?></td>
                                                <td><?php echo htmlspecialchars($resident['civil_status']); ?></td>
                                                <td><?php echo htmlspecialchars($resident['occupation']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No residents found matching your filters. Try adjusting your criteria.</p>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>