<?php
require_once 'config.php';
require_once 'functions.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Session checks
$public_access = $settings['public_access'] ?? 1;
$is_logged_in  = isLoggedIn();

$residents     = [];
$search_term   = $_GET['search'] ?? '';
$selected_purok = isset($_GET['purok']) ? intval($_GET['purok']) : null;

// Allowed puroks (1–7)
$puroks = [];
for ($i = 1; $i <= 7; $i++) {
    $puroks[] = ['purok_id' => $i, 'purok_name' => 'Purok ' . $i];
}

// Delete resident
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $is_logged_in) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
    $stmt->bind_param("i", $delete_id);

    if ($stmt->execute()) {
        echo "<script>alert('Resident deleted successfully.'); window.location.href='resident.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to delete resident.');</script>";
    }
    $stmt->close();
}

// Search + filter
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query  = "SELECT r.*, p.purok_name, p.purok_id AS actual_purok_id 
               FROM residents r
               LEFT JOIN puroks p ON r.purok_id = p.purok_id
               WHERE 1=1";

    $params = [];
    $types  = '';

    if (!empty($search_term)) {
        $query .= " AND (r.first_name LIKE ? OR r.last_name LIKE ? OR r.address LIKE ?)";
        $search_param = "%" . $conn->real_escape_string($search_term) . "%";
        $params = [$search_param, $search_param, $search_param];
        $types  = 'sss';
    }

    if ($selected_purok) {
        $query .= " AND r.purok_id = ?";
        $params[] = $selected_purok;
        $types   .= 'i';
    }

    $query .= " ORDER BY r.last_name, r.first_name";
    $stmt = $conn->prepare($query);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result    = $stmt->get_result();
    $residents = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Information</title>
    <link rel="stylesheet" href="resident.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .filter-container select {
            padding: 10px 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 10px;
        }
        .filter-container select:hover { border-color: #1d3b71; }
        .filter-container select:focus {
            outline: none; border-color: #1d3b71;
            box-shadow: 0 0 0 2px rgba(29, 59, 113, 0.2);
        }
        .action-buttons { display: flex; gap: 10px; margin-bottom: 20px; }
        .action-buttons .add-resident-btn {
            background-color: #1d3b71; color: white; border: none;
            padding: 10px 15px; border-radius: 4px; cursor: pointer;
            display: flex; align-items: center; gap: 5px; font-size: 14px;
            transition: background-color 0.3s;
        }
        .action-buttons .add-resident-btn:hover { background-color: #2c4d8a; }
        .purok-badge {
            display: inline-block; padding: 3px 8px;
            border-radius: 12px; background-color: #e0e0e0;
            color: #333; font-size: 12px; font-weight: 500;
        }
        .no-results { text-align: center; padding: 20px; color: #666; }
        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0;
                 width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content {
            background: #fff; margin: 10% auto; padding: 20px;
            border-radius: 8px; width: 500px; max-width: 90%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .close-btn { float: right; font-size: 20px; cursor: pointer; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo-link"><img src="logo.png" alt="Logo" class="header-logo"></a>
            <h2>A Web-based Barangay Demographic Profiling System</h2>
            <div class="welcome">
                <?php if ($is_logged_in): ?>
                    <p>Welcome, <?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'User'); ?></p>
                    <a href="logout.php" class="logout-btn">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">Staff Login</a>
                <?php endif; ?>
            </div>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-house-user"></i> Dashboard</a></li>
                <li><a href="resident.php" class="active"><i class="fas fa-users"></i> Residents</a></li>
                <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="predictive.php"><i class="fas fa-brain"></i> Predictive Models</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                <?php if ($is_logged_in): ?><li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li><?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <main class="main-content" style="margin-left: 280px; padding: 20px;">
        <div class="resident-header">
            <h1><i class="fas fa-users"></i> Residents</h1>
        </div>

        <!-- Search -->
        <div class="search-container">
            <form method="GET" action="resident.php">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search residents..."
                           value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit">Search</button>
                </div>
            </form>
        </div>

        <!-- Actions -->
        <div class="action-buttons">
            <div class="filter-container">
                <form method="GET" action="resident.php">
                    <select name="purok" onchange="this.form.submit()">
                        <option value="">All Puroks</option>
                        <?php foreach ($puroks as $purok): ?>
                            <option value="<?php echo $purok['purok_id']; ?>"
                                <?php echo $selected_purok == $purok['purok_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($purok['purok_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                </form>
            </div>
            <?php if ($is_logged_in): ?>
                <button class="add-resident-btn" onclick="window.location.href='census.php'">
                    <i class="fas fa-user-plus"></i> Add Resident
                </button>
            <?php endif; ?>
        </div>

        <!-- Residents Table -->
        <div class="resident-table-container">
            <table class="resident-table">
                <thead>
                    <tr>
                        <th>Resident ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Purok</th>
                        <?php if ($is_logged_in): ?><th>Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($residents)): ?>
                        <?php foreach ($residents as $resident): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($resident['id']); ?></td>
                                <td><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></td>
                                <td><?php echo calculateAge($resident['birthdate']); ?></td>
                                <td><?php echo htmlspecialchars($resident['gender']); ?></td>
                                <td><span class="purok-badge"><?php echo htmlspecialchars($resident['purok_name'] ?? 'N/A'); ?></span></td>
                                <?php if ($is_logged_in): ?>
                                    <td class="actions">
                                        <a href="edit-resident.php?id=<?php echo $resident['id']; ?>" class="action-btn edit" title="Update"><i class="fas fa-edit"></i></a>
                                        <a href="javascript:void(0);" class="action-btn view" title="View Census Answers"
                                           onclick="showAnswers(<?php echo $resident['id']; ?>, '<?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?>')">
                                           <i class="fas fa-eye"></i></a>
                                        <form method="POST" action="resident.php" onsubmit="return confirm('Are you sure you want to delete this resident?');" style="display:inline;">
                                            <input type="hidden" name="delete_id" value="<?php echo $resident['id']; ?>">
                                            <button type="submit" class="action-btn delete" title="Delete Resident"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $is_logged_in ? 6 : 5; ?>" class="no-results">
                                <?php echo empty($search_term) && empty($selected_purok) ? 'No residents found in database' : 'No matching residents found'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Census Answers Modal -->
<div id="answersModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h4 id="residentName"></h4>
    <div id="answersContainer"><!-- Answers will load here --></div>
  </div>
</div>

<script>
function showAnswers(id, name) {
    document.getElementById("residentName").innerText = name;
    document.getElementById("answersContainer").innerHTML = "<p>Loading answers...</p>";
    fetch("get_answers.php?id=" + id)
        .then(response => response.text())
        .then(data => { document.getElementById("answersContainer").innerHTML = data; })
        .catch(() => { document.getElementById("answersContainer").innerHTML = "<p>Failed to load answers.</p>"; });
    document.getElementById("answersModal").style.display = "block";
}
function closeModal() {
    document.getElementById("answersModal").style.display = "none";
}
</script>
</body>
</html>
