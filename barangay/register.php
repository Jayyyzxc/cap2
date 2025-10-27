<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barangay_system";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = $_POST["full_name"] ?? '';
    $birthdate = $_POST["birthdate"] ?? '';
    $gender = $_POST["gender"] ?? '';
    $address = $_POST["address"] ?? '';
    $contact_number = $_POST["contact_number"] ?? '';
    $email = $_POST["email"] ?? '';
    $username = $_POST["username"] ?? '';
    $password = $_POST["password"] ?? '';
    $position = $_POST["position"] ?? '';
    $start_term = $_POST["start_term"] ?? '';
    $end_term = $_POST["end_term"] ?? '';
    $barangay_name = $_POST["barangay_name"] ?? '';
    $municipality = $_POST["municipality"] ?? '';
    $province = $_POST["province"] ?? '';
    $barangay_hall = $_POST["barangay_hall"] ?? '';
    $barangay_contact = $_POST["barangay_contact"] ?? '';
    $barangay_email = $_POST["barangay_email"] ?? '';
    $barangay_website = $_POST["barangay_website"] ?? '';
    $total_population = $_POST["total_population"] ?? '';
    $total_households = $_POST["total_households"] ?? '';
    $purok_count = $_POST["purok_count"] ?? '';
    $purok_names = $_POST["purok_names"] ?? '';
    $flood_prone = $_POST["flood_prone"] ?? '';
    $affected_areas = $_POST["affected_areas"] ?? '';
    $evacuation_center = $_POST["evacuation_center"] ?? '';
    $preparedness = $_POST["preparedness"] ?? '';
    $coordination = $_POST["coordination"] ?? '';
    $annual_budget = $_POST["annual_budget"] ?? '';
    $budget_allocation = $_POST["budget_allocation"] ?? '';
    $spending_priorities = $_POST["spending_priorities"] ?? '';

    // Handle image upload
    $id_path = "";
    if (!empty($_FILES["official_id_upload"]["name"])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["official_id_upload"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["official_id_upload"]["tmp_name"], $targetFilePath)) {
            $id_path = $targetFilePath;
        }
    }

    // Insert into DB
    $sql = "INSERT INTO barangay_registration (
        full_name, birthdate, gender, address, contact_number, email, username, password, position, start_term, end_term,
        barangay_name, municipality, province, barangay_hall, barangay_contact, barangay_email, barangay_website,
        total_population, total_households, purok_count, purok_names, flood_prone, affected_areas, evacuation_center, 
        preparedness, coordination, annual_budget, budget_allocation, spending_priorities, id_path
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        $full_name, $birthdate, $gender, $address, $contact_number, $email, $username, password_hash($password, PASSWORD_DEFAULT),
        $position, $start_term, $end_term, $barangay_name, $municipality, $province, $barangay_hall, $barangay_contact, 
        $barangay_email, $barangay_website, $total_population, $total_households, $purok_count, $purok_names, $flood_prone, 
        $affected_areas, $evacuation_center, $preparedness, $coordination, $annual_budget, $budget_allocation, $spending_priorities, $id_path
    ]);

    echo "<script>alert('Registration successful! Data saved to database.');</script>";
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
        /* ID Upload and Modal */
        .id-upload {
            border: 2px dashed #1d3b71;
            border-radius: 10px;
            padding: 25px;
            background: #f8faff;
            text-align: center;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }
        .id-upload:hover { background: #eaf1fb; transform: scale(1.02); }
        .id-upload i { font-size: 2rem; color: #1d3b71; margin-bottom: 8px; }
        .id-preview {
            display: none; margin-top: 15px; max-width: 100%;
            border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .modal {
            display: none; position: fixed; z-index: 3000;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); justify-content: center; align-items: center;
        }
        .modal-content {
            background: white; padding: 20px; border-radius: 12px;
            text-align: center; position: relative; max-width: 500px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.4);
        }
        .close-btn {
            position: absolute; top: 10px; right: 15px;
            color: #333; font-size: 22px; cursor: pointer;
        }
        .close-btn:hover { color: red; }

        /* Section Titles */
        h3 {
            color: #1d3b71;
            margin-top: 25px;
            margin-bottom: 10px;
            font-size: 1.1rem;
            border-left: 4px solid #1d3b71;
            padding-left: 8px;
        }
        .form-section hr {
            border: none;
            border-top: 1px solid #dfe3eb;
            margin: 25px 0;
        }
    </style>
</head>
<body>

<!-- 🔙 Back Button -->
<button class="back-btn" onclick="window.location.href='index.php'">← Go to Main Page</button>

<div class="registration-container">
    <div class="registration-header">
        <img src="logo.png" alt="Barangay Logo" class="logo">
        <h1>Barangay Saguin Official Registration</h1>
        <p>Please fill out the form to register as a barangay official</p>
    </div>

    <form class="registration-form" method="POST" enctype="multipart/form-data">

        <!-- 🧍 PERSONAL INFORMATION -->
        <div class="form-section">
            <h2><i class="fas fa-user"></i> Personal Information</h2>

            <div class="form-row">
                <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Birthdate</label><input type="date" name="birthdate"></div>
                <div class="form-group"><label>Gender</label>
                    <select name="gender"><option value="">Select</option><option>Male</option><option>Female</option></select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>Address</label><input type="text" name="address"></div>
                <div class="form-group"><label>Contact Number</label><input type="tel" name="contact_number" maxlength="11" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Username</label><input type="text" name="username"></div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>Password</label><input type="password" name="password"></div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>Position</label><input type="text" name="position"></div>
                <div class="form-group"><label>Start Term</label><input type="date" name="start_term"></div>
                <div class="form-group"><label>End Term</label><input type="date" name="end_term"></div>
            </div>

            <div class="form-group">
                <label>Upload Official ID</label>
                <label class="id-upload" for="official_id_upload">
                    <i class="fas fa-id-card"></i>
                    <p>Click here to upload your Barangay ID (JPG or PNG)</p>
                    <input type="file" id="official_id_upload" name="official_id_upload" accept="image/*" onchange="previewID(event)">
                    <img id="idPreview" class="id-preview" alt="ID Preview" onclick="openModal()">
                </label>
            </div>
        </div>

        <!-- 🏘️ BARANGAY INFORMATION -->
        <div class="form-section">
            <h2>Barangay Information</h2>

            <div class="form-row">
                <div class="form-group"><label>Barangay Name</label><input type="text" name="barangay_name"></div>
                <div class="form-group"><label>Municipality/City</label><input type="text" name="municipality"></div>
                <div class="form-group"><label>Province</label><input type="text" name="province"></div>
            </div>

            <h3>Barangay Contact Information</h3>
            <div class="form-row">
                <div class="form-group"><label>Barangay Hall Address</label><input type="text" name="barangay_hall"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Contact Number</label><input type="text" name="barangay_contact"></div>
                <div class="form-group"><label>Email Address</label><input type="email" name="barangay_email"></div>
                <div class="form-group"><label>Website / Facebook Page</label><input type="text" name="barangay_website"></div>
            </div>

            <h3>Barangay Profile</h3>
            <div class="form-row">
                <div class="form-group"><label>Total Population</label><input type="number" name="total_population"></div>
                <div class="form-group"><label>Total Households</label><input type="number" name="total_households"></div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>How Many Puroks?</label>
                    <input type="number" name="purok_count" id="purokCount" min="1" onchange="generatePurokFields()">
                </div>
            </div>

            <div class="form-group" id="purokNamesContainer"></div>

            <h3>Disaster and Risk Management</h3>
            <div class="form-row">
                <div class="form-group"><label>Is your barangay flood-prone?</label>
                    <select name="flood_prone"><option value="">Select</option><option>Yes</option><option>No</option></select>
                </div>
                <div class="form-group"><label>Most affected areas</label><input type="text" name="affected_areas"></div>
            </div>

            <div class="form-group"><label>Designated Evacuation Center</label><input type="text" name="evacuation_center"></div>
            <div class="form-group"><label>Preparedness Plans & Equipment</label><textarea name="preparedness" rows="3"></textarea></div>
            <div class="form-group"><label>Coordination with LGU/MDRRMO</label><textarea name="coordination" rows="3"></textarea></div>

            <h3>Barangay Budget and Finance</h3>
            <div class="form-row">
                <div class="form-group"><label>Annual Budget (₱)</label><input type="number" name="annual_budget"></div>
            </div>
            <div class="form-group">
                <label>Budget Allocations / Sources of Funds</label>
                <textarea name="budget_allocation" rows="3"></textarea>
            </div>
            <div class="form-group"><label>Top Spending Priorities</label><textarea name="spending_priorities" rows="3"></textarea></div>
        </div>

        <div class="form-submit">
            <button type="submit" class="submit-btn"><i class="fas fa-user-plus"></i> Register as Official</button>
            <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </form>
</div>

<script>
function previewID(event) {
    const preview = document.getElementById('idPreview');
    const file = event.target.files[0];
    if (file) {
        const url = URL.createObjectURL(file);
        preview.src = url;
        preview.style.display = 'block';
    }
}
function generatePurokFields() {
    const count = parseInt(document.getElementById('purokCount').value);
    const container = document.getElementById('purokNamesContainer');
    container.innerHTML = '';
    if (count > 0) {
        const label = document.createElement('label');
        label.textContent = "Names of Puroks/Sitios:";
        container.appendChild(label);
        for (let i = 1; i <= count; i++) {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'purok_names';
            input.placeholder = `Enter name for Purok ${i}`;
            container.appendChild(input);
        }
    }
}
</script>
</body>
</html>
