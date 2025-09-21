<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Import PHPMailer manually (GitHub version)
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

                // NOTE: columns count (15 placeholders) matches $values array length below
                $sql = "INSERT INTO barangay_officials 
                    (first_name, last_name, middle_name, username, email, password, birthdate, 
                    gender, civil_status, address, contact_number, position, 
                    official_id, start_term, end_term, date_registered, is_verified) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)";
                $values = [
                    $first_name, $last_name, $middle_name, $username, $email, $hashed_password,
                    $birthdate, $gender, $civil_status, $address, $contact_number,
                    $position, $official_id, $start_term, $end_term
                ];

                $stmt = $conn->prepare($sql);
                if ($stmt->execute($values)) {
                    // ✅ Generate OTP and save
                    $otp = rand(100000, 999999);
                    $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                    $officialId = $conn->lastInsertId();

                    $updateOtp = $conn->prepare("UPDATE barangay_officials SET otp=?, otp_expiry=? WHERE id=?");
                    $updateOtp->execute([$otp, $expiry, $officialId]);

                    // ✅ Send OTP via PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = "smtp.gmail.com";
                        $mail->SMTPAuth = true;
                        $mail->Username = "yourgmail@gmail.com";   // replace with your Gmail
                        $mail->Password = "your_app_password";     // replace with Gmail App Password
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                        $mail->Port = 587;

                        $mail->setFrom("yourgmail@gmail.com", "Barangay System");
                        $mail->addAddress($email, $first_name . " " . $last_name);
                        $mail->isHTML(false); // plain text
                        $mail->Subject = "Barangay System OTP Verification";
                        $mail->Body = "Hello $first_name,\n\nYour OTP code is: $otp\nThis code will expire in 5 minutes.";

                        $mail->send();

                        $_SESSION['pending_verification'] = $email;
                        header("Location: verify_otp.php");
                        exit();
                    } catch (Exception $e) {
                        $errors['database'] = "Registration saved, but OTP email failed: {$mail->ErrorInfo}";
                    }
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
        /* keep your original CSS or paste the same styles here */
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            <img src="logo.png" alt="Barangay Logo" class="logo">
            <h1>Barangay Saguin Official Registration</h1>
            <p>Please fill out all required fields to register as a barangay official</p>
        </div>

        <?php if (!empty($errors['database'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($errors['database']); ?>
            </div>
        <?php endif; ?>

      <form method="POST" class="registration-form">
            <!-- (Place all your form fields here; I'm using your original layout) -->
            <!-- Example for a few fields — include all the rest similar to original and use the same names -->
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
                <!-- continue the rest of the form sections exactly as in your original file,
                     making sure to echo old values with htmlspecialchars($var) and show $errors -->
            </div>

            <!-- Account security & submit -->
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
