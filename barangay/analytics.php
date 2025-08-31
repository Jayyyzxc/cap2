<?php
session_start();
require_once 'config.php'; // Database connection

// Check if user is logged in
$is_logged_in = isset($_SESSION['user']);

// Get analytics data
function getAnalyticsData($purok_id = null) {
    global $conn;
    
    $data = [];
    
    // Age distribution
    $ageQuery = "SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) < 18 THEN '0-17'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
            WHEN TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) BETWEEN 45 AND 59 THEN '45-59'
            ELSE '60+'
        END AS age_group,
        COUNT(*) AS count
        FROM residents
        WHERE 1=1";
    
    if ($purok_id) {
        $ageQuery .= " AND purok_id = ?";
    }
    
    $ageQuery .= " GROUP BY age_group ORDER BY age_group";
    
    $stmt = $conn->prepare($ageQuery);
    if ($purok_id) {
        $stmt->bind_param("i", $purok_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data['age_distribution'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Employment status
    $employmentQuery = "SELECT employment_status, COUNT(*) AS count FROM residents WHERE 1=1";
    if ($purok_id) {
        $employmentQuery .= " AND purok_id = ?";
    }
    $employmentQuery .= " GROUP BY employment_status";
    
    $stmt = $conn->prepare($employmentQuery);
    if ($purok_id) {
        $stmt->bind_param("i", $purok_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data['employment_status'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Gender ratio
    $genderQuery = "SELECT gender, COUNT(*) AS count FROM residents WHERE 1=1";
    if ($purok_id) {
        $genderQuery .= " AND purok_id = ?";
    }
    $genderQuery .= " GROUP BY gender";
    
    $stmt = $conn->prepare($genderQuery);
    if ($purok_id) {
        $stmt->bind_param("i", $purok_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data['gender_ratio'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Household income
    $incomeQuery = "SELECT 
        CASE 
            WHEN monthly_income < 10000 THEN '<10k'
            WHEN monthly_income BETWEEN 10000 AND 30000 THEN '10k-30k'
            WHEN monthly_income BETWEEN 30001 AND 50000 THEN '30k-50k'
            WHEN monthly_income > 50000 THEN '>50k'
            ELSE 'Not Specified'
        END AS income_range,
        COUNT(*) AS count
        FROM residents
        WHERE employment_status = 'Employed'";
    
    if ($purok_id) {
        $incomeQuery .= " AND purok_id = ?";
    }
    
    $incomeQuery .= " GROUP BY income_range ORDER BY income_range";
    
    $stmt = $conn->prepare($incomeQuery);
    if ($purok_id) {
        $stmt->bind_param("i", $purok_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data['household_income'] = $result->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

// Get purok filter if set
$selected_purok = isset($_GET['purok']) ? intval($_GET['purok']) : null;

// Get all puroks for dropdown
$puroks = [];
$purokQuery = "SELECT * FROM puroks ORDER BY purok_name";
$result = $conn->query($purokQuery);
if ($result) {
    $puroks = $result->fetch_all(MYSQLI_ASSOC);
}

// Get analytics data
$analyticsData = getAnalyticsData($selected_purok);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demographic Analytics - Barangay Profiling System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <link rel="stylesheet" href="analytics.css">
    <style>
        /* Analytics Page Specific Styles */
        .analytics-container {
            padding: 20px;
            margin-left: var(--sidebar-width);
        }
        
        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .filter-section {
            background-color: var(--white);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-sm);
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .filter-row select, .filter-row button {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid var(--gray-300);
        }
        
        .filter-row button {
            background-color: var(--primary-blue);
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .analytics-card {
            background-color: var(--white);
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        
        .card-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .stat-item {
            background-color: var(--gray-100);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary-blue);
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--gray-600);
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
                <li><a href="analytics.php" class="active"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="predictive.php"><i class="fas fa-brain"></i> Predictive Models</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="analytics-container">
        <div class="analytics-header">
            <h1><i class="fas fa-chart-bar"></i> Demographic Analytics</h1>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="get" action="analytics.php">
                <div class="filter-row">
                    <label for="purok">Filter by Purok:</label>
                    <select name="purok" id="purok">
                        <option value="">All Puroks</option>
                        <?php foreach ($puroks as $purok): ?>
                            <option value="<?php echo $purok['purok_id']; ?>" <?php echo $selected_purok == $purok['purok_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($purok['purok_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Apply Filter</button>
                </div>
            </form>
        </div>
        
        <!-- Analytics Grid -->
        <div class="analytics-grid">
            <!-- Age Distribution Card -->
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Age Distribution</h3>
                </div>
                <div class="chart-container">
                    <canvas id="ageChart"></canvas>
                </div>
            </div>
            
            <!-- Employment Status Card -->
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Employment Status</h3>
                </div>
                <div class="chart-container">
                    <canvas id="employmentChart"></canvas>
                </div>
            </div>
            
            <!-- Gender Ratio Card -->
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Gender Ratio</h3>
                </div>
                <div class="chart-container">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>
            
            <!-- Household Income Card -->
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Household Income</h3>
                </div>
                <div class="chart-container">
                    <canvas id="incomeChart"></canvas>
                </div>
            </div>
            
            <!-- Quick Stats Card -->
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Quick Statistics</h3>
                </div>
                <div class="stats-grid">
                    <?php
                    // Calculate total residents
                    $totalResidents = array_sum(array_column($analyticsData['gender_ratio'], 'count'));
                    
                    // Calculate gender percentages
                    $maleCount = 0;
                    $femaleCount = 0;
                    foreach ($analyticsData['gender_ratio'] as $gender) {
                        if ($gender['gender'] == 'Male') $maleCount = $gender['count'];
                        if ($gender['gender'] == 'Female') $femaleCount = $gender['count'];
                    }
                    $malePercent = $totalResidents > 0 ? round(($maleCount / $totalResidents) * 100, 1) : 0;
                    $femalePercent = $totalResidents > 0 ? round(($femaleCount / $totalResidents) * 100, 1) : 0;
                    
                    // Employment stats
                    $employedCount = 0;
                    $unemployedCount = 0;
                    $notInLaborCount = 0;
                    foreach ($analyticsData['employment_status'] as $employment) {
                        if ($employment['employment_status'] == 'Employed') $employedCount = $employment['count'];
                        if ($employment['employment_status'] == 'Unemployed') $unemployedCount = $employment['count'];
                        if ($employment['employment_status'] == 'Not in Labor Force') $notInLaborCount = $employment['count'];
                    }
                    ?>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $totalResidents; ?></div>
                        <div class="stat-label">Total Residents</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $malePercent; ?>%</div>
                        <div class="stat-label">Male</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $femalePercent; ?>%</div>
                        <div class="stat-label">Female</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $employedCount; ?></div>
                        <div class="stat-label">Employed</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $unemployedCount; ?></div>
                        <div class="stat-label">Unemployed</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $notInLaborCount; ?></div>
                        <div class="stat-label">Not in Labor Force</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
    // Prepare data for charts
    const ageData = {
        labels: <?php echo json_encode(array_column($analyticsData['age_distribution'], 'age_group')); ?>,
        values: <?php echo json_encode(array_column($analyticsData['age_distribution'], 'count')); ?>
    };
    
    const employmentData = {
        labels: <?php echo json_encode(array_column($analyticsData['employment_status'], 'employment_status')); ?>,
        values: <?php echo json_encode(array_column($analyticsData['employment_status'], 'count')); ?>
    };
    
    const genderData = {
        labels: <?php echo json_encode(array_column($analyticsData['gender_ratio'], 'gender')); ?>,
        values: <?php echo json_encode(array_column($analyticsData['gender_ratio'], 'count')); ?>
    };
    
    const incomeData = {
        labels: <?php echo json_encode(array_column($analyticsData['household_income'], 'income_range')); ?>,
        values: <?php echo json_encode(array_column($analyticsData['household_income'], 'count')); ?>
    };
    
    // Colors
    const chartColors = {
        blue: 'rgba(54, 162, 235, 0.7)',
        red: 'rgba(255, 99, 132, 0.7)',
        yellow: 'rgba(255, 206, 86, 0.7)',
        green: 'rgba(75, 192, 192, 0.7)',
        purple: 'rgba(153, 102, 255, 0.7)',
        orange: 'rgba(255, 159, 64, 0.7)',
        gray: 'rgba(201, 203, 207, 0.7)'
    };

    // Initialize charts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Age Distribution Chart (Bar)
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ageData.labels,
                datasets: [{
                    label: 'Number of Residents',
                    data: ageData.values,
                    backgroundColor: [
                        chartColors.blue,
                        chartColors.red,
                        chartColors.yellow,
                        chartColors.green,
                        chartColors.purple,
                        chartColors.orange,
                        chartColors.gray
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Residents'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Age Group'
                        }
                    }
                }
            }
        });
        
        // Employment Status Chart (Doughnut)
        const employmentCtx = document.getElementById('employmentChart').getContext('2d');
        new Chart(employmentCtx, {
            type: 'doughnut',
            data: {
                labels: employmentData.labels,
                datasets: [{
                    data: employmentData.values,
                    backgroundColor: [
                        chartColors.green,
                        chartColors.red,
                        chartColors.purple
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        
        // Gender Ratio Chart (Pie)
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: genderData.labels,
                datasets: [{
                    data: genderData.values,
                    backgroundColor: [
                        chartColors.blue,
                        chartColors.red,
                        chartColors.purple
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        
        // Household Income Chart (Bar)
        const incomeCtx = document.getElementById('incomeChart').getContext('2d');
        new Chart(incomeCtx, {
            type: 'bar',
            data: {
                labels: incomeData.labels,
                datasets: [{
                    label: 'Number of Households',
                    data: incomeData.values,
                    backgroundColor: [
                         chartColors.blue,
                        chartColors.red,
                        chartColors.yellow,
                        chartColors.green,
                        chartColors.purple,
                        chartColors.orange,
                        chartColors.gray
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Households'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Monthly Income Range'
                        }
                    }
                }
            }
        });
    });
</script>
</body>
</html>