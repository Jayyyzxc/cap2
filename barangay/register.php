<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Default empty values
$first_name = $last_name = $middle_name = $username = $email = $password = $confirm_password = '';
$birthdate = $gender = $civil_status = $address = $contact_number = '';
$position = $official_id = $start_term = $end_term = '';

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $position = $_POST['position'] ?? '';
    $official_id = trim($_POST['official_id'] ?? '');
    $start_term = $_POST['start_term'] ?? '';
    $end_term = $_POST['end_term'] ?? '';

    // Validation
    if (empty($first_name)) $errors['first_name'] = 'First name is required';
    if (empty($last_name)) $errors['last_name'] = 'Last name is required';
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        $errors['username'] = 'Username must be 4-20 characters, letters, numbers, or underscores only';
    }
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    if (empty($birthdate)) $errors['birthdate'] = 'Birthdate is required';
    if (empty($gender)) $errors['gender'] = 'Gender is required';
    if (empty($civil_status)) $errors['civil_status'] = 'Civil status is required';
    if (empty($address)) $errors['address'] = 'Address is required';
    if (empty($contact_number)) $errors['contact_number'] = 'Contact number is required';
    if (empty($position)) $errors['position'] = 'Position is required';
    if (empty($official_id)) $errors['official_id'] = 'Official ID is required';
    if (empty($start_term)) $errors['start_term'] = 'Start of term is required';
    if (empty($end_term)) $errors['end_term'] = 'End of term is required';

    // If no errors, insert into DB
    if (empty($errors)) {
        try {
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id, username, email FROM barangay_officials WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                if (isset($existing['username']) && $existing['username'] === $username) {
                    $errors['username'] = 'Username already taken';
                }
                if (isset($existing['email']) && $existing['email'] === $email) {
                    $errors['email'] = 'Email already registered';
                }
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("INSERT INTO barangay_officials 
                    (first_name, last_name, middle_name, username, email, password, birthdate, 
                    gender, civil_status, address, contact_number, position, 
                    official_id, start_term, end_term, date_registered) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                if ($stmt->execute([
                    $first_name, $last_name, $middle_name, $username, $email, $hashed_password,
                    $birthdate, $gender, $civil_status, $address, $contact_number,
                    $position, $official_id, $start_term, $end_term
                ])) {
                    $_SESSION['register_success'] = true;
                    header('Location: register.php?success=1');
                    exit();
                } else {
                    $errors['database'] = 'Registration failed. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $errors['database'] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Check success flag
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Official Registration</title>
    <link rel="stylesheet" href="register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Add these styles if not already in your register.css */
        .registration-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .registration-header h1 {
            color: #1d3b71;
            margin-bottom: 10px;
        }
        .logo {
            height: 80px;
            margin-bottom: 15px;
        }
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .required {
            color: #e74c3c;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        textarea {
            min-height: 80px;
        }
        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .submit-btn {
            background-color: #1d3b71;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .submit-btn:hover {
            background-color: #2c4d8a;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            <img src="logo.png" alt="Barangay Logo" class="logo">
            <h1>Barangay Saguin Official Registration</h1>
            <p>Please fill out all required fields to register as a barangay official</p>
        </div>

        <?php if (isset($_SESSION['register_success']) && $success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Registration successful! You can now login to the system.
            </div>
            <?php unset($_SESSION['register_success']); ?>
        <?php elseif (!empty($errors['database'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $errors['database']; ?>
            </div>
        <?php endif; ?>

      <form method="POST" class="registration-form">
            <div class="form-section">
                <h2><i class="fas fa-user"></i> Personal Information</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($first_name); ?>" required>
                        <?php if (!empty($errors['first_name'])): ?>
                            <span class="error-message"><?php echo $errors['first_name']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($last_name); ?>" required>
                        <?php if (!empty($errors['last_name'])): ?>
                            <span class="error-message"><?php echo $errors['last_name']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" 
                               value="<?php echo htmlspecialchars($middle_name); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="birthdate">Birthdate <span class="required">*</span></label>
                        <input type="date" id="birthdate" name="birthdate" 
                               value="<?php echo htmlspecialchars($birthdate); ?>" required>
                        <?php if (!empty($errors['birthdate'])): ?>
                            <span class="error-message"><?php echo $errors['birthdate']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender <span class="required">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <?php if (!empty($errors['gender'])): ?>
                            <span class="error-message"><?php echo $errors['gender']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="civil_status">Civil Status <span class="required">*</span></label>
                        <select id="civil_status" name="civil_status" required>
                            <option value="">Select Status</option>
                            <option value="Single" <?php echo $civil_status === 'Single' ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo $civil_status === 'Married' ? 'selected' : ''; ?>>Married</option>
                            <option value="Divorced" <?php echo $civil_status === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                            <option value="Widowed" <?php echo $civil_status === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                        </select>
                        <?php if (!empty($errors['civil_status'])): ?>
                            <span class="error-message"><?php echo $errors['civil_status']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-address-card"></i> Contact Information</h2>
                
                <div class="form-group">
                    <label for="address">Complete Address <span class="required">*</span></label>
                    <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
                    <?php if (!empty($errors['address'])): ?>
                        <span class="error-message"><?php echo $errors['address']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" required>
                        <?php if (!empty($errors['email'])): ?>
                            <span class="error-message"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number <span class="required">*</span></label>
                        <input type="tel" id="contact_number" name="contact_number" 
                               value="<?php echo htmlspecialchars($contact_number); ?>" required>
                        <?php if (!empty($errors['contact_number'])): ?>
                            <span class="error-message"><?php echo $errors['contact_number']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-id-card"></i> Official Information</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="position">Position <span class="required">*</span></label>
                        <select id="position" name="position" required>
                            <option value="">Select Position</option>
                            <option value="Barangay Captain" <?php echo $position === 'Barangay Captain' ? 'selected' : ''; ?>>Barangay Captain</option>
                            <option value="Barangay Secretary" <?php echo $position === 'Barangay Secretary' ? 'selected' : ''; ?>>Barangay Secretary</option>
                            <option value="Barangay Treasurer" <?php echo $position === 'Barangay Treasurer' ? 'selected' : ''; ?>>Barangay Treasurer</option>
                            <option value="Barangay Councilor" <?php echo $position === 'Barangay Councilor' ? 'selected' : ''; ?>>Barangay Councilor</option>
                            <option value="SK Chairman" <?php echo $position === 'SK Chairman' ? 'selected' : ''; ?>>SK Chairman</option>
                            <option value="Barangay Health Worker" <?php echo $position === 'Barangay Health Worker' ? 'selected' : ''; ?>>Barangay Health Worker</option>
                        </select>
                        <?php if (!empty($errors['position'])): ?>
                            <span class="error-message"><?php echo $errors['position']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="official_id">Official ID Number <span class="required">*</span></label>
                        <input type="text" id="official_id" name="official_id" 
                               value="<?php echo htmlspecialchars($official_id); ?>" required>
                        <?php if (!empty($errors['official_id'])): ?>
                            <span class="error-message"><?php echo $errors['official_id']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_term">Start of Term <span class="required">*</span></label>
                        <input type="date" id="start_term" name="start_term" 
                               value="<?php echo htmlspecialchars($start_term); ?>" required>
                        <?php if (!empty($errors['start_term'])): ?>
                            <span class="error-message"><?php echo $errors['start_term']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_term">End of Term <span class="required">*</span></label>
                        <input type="date" id="end_term" name="end_term" 
                               value="<?php echo htmlspecialchars($end_term); ?>" required>
                        <?php if (!empty($errors['end_term'])): ?>
                            <span class="error-message"><?php echo $errors['end_term']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-lock"></i> Account Security</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        <?php if (!empty($errors['username'])): ?>
                            <span class="error-message"><?php echo $errors['username']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required>
                        <?php if (!empty($errors['password'])): ?>
                            <span class="error-message"><?php echo $errors['password']; ?></span>
                        <?php endif; ?>
                        <small class="hint">Minimum 8 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-submit">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Register as Official
                </button>
                <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</body>
</html>