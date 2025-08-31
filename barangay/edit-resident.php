<?php
require_once 'config.php';
require_once 'functions.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

$is_logged_in = isLoggedIn();
if (!$is_logged_in) {
    header('Location: login.php');
    exit();
}

// Get resident ID
$resident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($resident_id <= 0) {
    die('Invalid resident ID.');
}

// Fetch resident data
$stmt = $conn->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->bind_param("i", $resident_id);
$stmt->execute();
$resident = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$resident) {
    die('Resident not found.');
}

// Fetch puroks for dropdown
$puroks = [];
$purokQuery = "SELECT * FROM puroks ORDER BY purok_name";
$result = $conn->query($purokQuery);
if ($result) {
    $puroks = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle update
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $purok_id = intval($_POST['purok_id']);
    $household_id = trim($_POST['household_id']);

    if ($first_name && $last_name && $birthdate && $gender && $address && $purok_id) {
        $stmt = $conn->prepare("UPDATE residents SET first_name=?, last_name=?, birthdate=?, gender=?, address=?, purok_id=?, household_id=? WHERE id=?");
        $stmt->bind_param("ssssssii", $first_name, $last_name, $birthdate, $gender, $address, $purok_id, $household_id, $resident_id);
if ($stmt->execute()) {
    header('Location: resident.php');
    exit();
} else {
    $error = 'Failed to update resident.';
}
 $stmt->close();
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Resident</title>
    <link rel="stylesheet" href="census.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .update-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
            padding: 32px 28px 24px 28px;
        }
        .update-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .update-header h2 {
            color: #1d3b71;
            font-size: 2rem;
            margin-bottom: 8px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #222;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 5px;
            border: 1px solid #cfd8dc;
            font-size: 1rem;
            background: #f9fbfd;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #1d3b71;
            outline: none;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-primary {
            background: #1d3b71;
            color: #fff;
        }
        .btn-primary:hover {
            background: #274b8a;
        }
        .btn-cancel {
            background: #e0e0e0;
            color: #333;
        }
        .success-message {
            color: #2ecc71;
            text-align: center;
            margin-bottom: 16px;
        }
        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="update-container">
        <div class="update-header">
            <h2><i class="fas fa-user-edit"></i> Update Resident</h2>
        </div>
        <?php if ($success): ?>
            <div class="success-message">Resident updated successfully!</div>
        <?php elseif ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($resident['first_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($resident['last_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="birthdate">Birthdate</label>
                <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($resident['birthdate']); ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="Male" <?php if ($resident['gender'] === 'Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if ($resident['gender'] === 'Female') echo 'selected'; ?>>Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($resident['address']); ?>" required>
            </div>
            <div class="form-group">
                <label for="purok_id">Purok</label>
                <select id="purok_id" name="purok_id" required>
                    <option value="">Select Purok</option>
                    <option value="1" <?php if ($resident['purok_id'] == 1) echo 'selected'; ?>>Purok 1</option>
                    <option value="2" <?php if ($resident['purok_id'] == 2) echo 'selected'; ?>>Purok 2</option>
                    <option value="3" <?php if ($resident['purok_id'] == 3) echo 'selected'; ?>>Purok 3</option>
                    <option value="4" <?php if ($resident['purok_id'] == 4) echo 'selected'; ?>>Purok 4</option>
                    <option value="5" <?php if ($resident['purok_id'] == 5) echo 'selected'; ?>>Purok 5</option>
                    <option value="6" <?php if ($resident['purok_id'] == 6) echo 'selected'; ?>>Purok 6</option>
                    <option value="7" <?php if ($resident['purok_id'] == 7) echo 'selected'; ?>>Purok 7</option>
                </select>
            </div>
            <div class="form-group">
                <label for="household_id">Household No.</label>
                <input type="text" id="household_id" name="household_id" value="<?php echo htmlspecialchars($resident['household_id']); ?>">
            </div>
            <div class="form-actions">
                <a href="resident.php" class="btn btn-cancel">Cancel</a>
                <button href="resident.php" "submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</body>
</html>
