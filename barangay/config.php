<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_system');
define('APP_NAME', 'Barangay Demographic Profiling System');

// Connect to MySQL
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4
$conn->set_charset("utf8mb4");

// Fetch system settings from database
$settings = [];
$result = $conn->query("SELECT * FROM system_settings LIMIT 1");
if ($result && $result->num_rows > 0) {
    $settings = $result->fetch_assoc();
} else {
    $settings = ['public_access' => 1, 'allow_registration' => 0];
}

// Session & Role Helpers
function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user']['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit();
    }
}

// Utility Functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($input) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(trim($input)));
}

// Data Retrieval Functions
function getResidentCount() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) FROM residents");
    return $result ? $result->fetch_row()[0] : 0;
}

function getHouseholdCount() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) FROM households");
    return $result ? $result->fetch_row()[0] : 0;
}

function getUpcomingEventsCount() {
    global $conn;
    $result = $conn->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()");
    return $result ? $result->fetch_row()[0] : 0;
}

function getAgeDistribution() {
    global $conn;
    $data = [];
    $result = $conn->query("
        SELECT 
            CASE 
                WHEN age < 18 THEN '0-17'
                WHEN age BETWEEN 18 AND 24 THEN '18-24'
                WHEN age BETWEEN 25 AND 34 THEN '25-34'
                WHEN age BETWEEN 35 AND 44 THEN '35-44'
                WHEN age BETWEEN 45 AND 59 THEN '45-59'
                ELSE '60+'
            END AS age_group,
            COUNT(*) as count
        FROM residents
        GROUP BY age_group
        ORDER BY age_group
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function getGenderDistribution() {
    global $conn;
    $data = [];
    $result = $conn->query("SELECT gender, COUNT(*) as count FROM residents GROUP BY gender");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function getEmploymentStatus() {
    global $conn;
    $data = [];
    $result = $conn->query("SELECT employment_status, COUNT(*) as count FROM residents GROUP BY employment_status");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

function getUpcomingEvents($limit = 5) {
    global $conn;
    $data = [];
    $stmt = $conn->prepare("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// CSRF token setup
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Error handler (optional)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
