<?php
session_start();
require_once 'config.php';

// Check if user is logged in (optional, remove if you want public access)
$is_logged_in = isset($_SESSION['user']);

// Get puroks for dropdown
$puroks = [];
if ($conn) {
    $purokQuery = "SELECT * FROM puroks ORDER BY purok_name";
    $result = $conn->query($purokQuery);
    if ($result) {
        $puroks = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Calculate birthdate from age
        $birthdate = date('Y-m-d', strtotime('-' . $_POST['age'] . ' years'));

        // Prepare data for both tables
        $census_data = [
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'age' => $_POST['age'],
            'gender' => $_POST['gender'],
            'civil_status' => $_POST['civil_status'],
            'address' => $_POST['address'],
            'contact_number' => $_POST['contact_number'],
            'education_level' => $_POST['education_level'],
            'employment_status' => $_POST['employment_status'],
            'monthly_income' => ($_POST['employment_status'] == 'Student') ? 0 : $_POST['monthly_income'],
            'household_size' => $_POST['household_size'],
            'purok_id' => $_POST['purok_id'],
            'submitted_at' => date('Y-m-d H:i:s'),
            'sports_interest' => $_POST['sports_interest'] ?? 'No',
            'health_interest' => $_POST['health_interest'] ?? 'No',
            'nutrition_interest' => $_POST['nutrition_interest'] ?? 'No',
            'assistive_interest' => $_POST['assistive_interest'] ?? 'No',
            'livelihood_interest' => $_POST['livelihood_interest'] ?? 'No',
            'aid_interest' => $_POST['aid_interest'] ?? 'No',
            'disaster_interest' => $_POST['disaster_interest'] ?? 'No',
            'road_clearing_interest' => $_POST['road_clearing_interest'] ?? 'No',
            'cleanup_interest' => $_POST['cleanup_interest'] ?? 'No',
            'waste_mgmt_interest' => $_POST['waste_mgmt_interest'] ?? 'No'
        ];

        // Begin transaction
        $conn->beginTransaction();

        try {
            // Save to census_submissions table
            $stmt = $conn->prepare("INSERT INTO census_submissions 
                (first_name, last_name, age, gender, civil_status, address, 
                contact_number, education_level, employment_status, 
                monthly_income, household_size, purok_id, submitted_at,
                sports_interest, health_interest, nutrition_interest, assistive_interest, 
                livelihood_interest, aid_interest, disaster_interest, road_clearing_interest, 
                cleanup_interest, waste_mgmt_interest)
                VALUES (:first_name, :last_name, :age, :gender, :civil_status, 
                :address, :contact_number, :education_level, :employment_status, 
                :monthly_income, :household_size, :purok_id, :submitted_at,
                :sports_interest, :health_interest, :nutrition_interest, :assistive_interest, 
                :livelihood_interest, :aid_interest, :disaster_interest, :road_clearing_interest, 
                :cleanup_interest, :waste_mgmt_interest)");

            $stmt->execute($census_data);

            // Save to residents table
            $stmt = $conn->prepare("INSERT INTO residents 
                (first_name, last_name, birthdate, gender, civil_status, address, 
                contact_number, education_level, employment_status, 
                monthly_income, household_size, purok_id, created_at)
                VALUES (:first_name, :last_name, :birthdate, :gender, :civil_status, 
                :address, :contact_number, :education_level, :employment_status, 
                :monthly_income, :household_size, :purok_id, :created_at)");

            $stmt->execute([
                'first_name' => $census_data['first_name'],
                'last_name' => $census_data['last_name'],
                'birthdate' => $birthdate,
                'gender' => $census_data['gender'],
                'civil_status' => $census_data['civil_status'],
                'address' => $census_data['address'],
                'contact_number' => $census_data['contact_number'],
                'education_level' => $census_data['education_level'],
                'employment_status' => $census_data['employment_status'],
                'monthly_income' => $census_data['monthly_income'],
                'household_size' => $census_data['household_size'],
                'purok_id' => $census_data['purok_id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $conn->commit();

            $_SESSION['census_success'] = "Census form submitted successfully! Resident record created.";
            header("Location: census.php");
            exit();

        } catch(PDOException $e) {
            $conn->rollBack();
            throw $e;
        }

    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Census Form - Barangay System</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .census-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .census-header {
            text-align: center;
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .census-header h1 {
            color: white;
            margin-bottom: 10px;
        }
        
        .census-logo-bg {
            background: #b3d8f6;
            border-radius: 50%;
            padding: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .census-logo {
            height: 80px;
            width: 80px;
            padding: 0;
            object-fit: contain;
            display: block;
            margin: 0;
        }
        
        .census-form {
            background-color: white;
            padding: 25px;
            border-radius: 6px;
            box-shadow: 0 0 5px rgba(0,0,0,0.05);
        }
        
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section h2 {
            color: #1d3b71;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="tel"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 80px;
        }
        
        .form-submit {
            text-align: center;
            margin-top: 30px;
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
        
        .census-footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
        
        .copyright {
            margin-top: 10px;
            font-size: 12px;
            color: #999;
        }
    </style>
    <script>
        function handleEmploymentChange() {
            const employmentStatus = document.getElementById('employment_status').value;
            const incomeField = document.getElementById('monthly_income');
            
            if (employmentStatus === 'Student') {
                incomeField.disabled = true;
                incomeField.value = '0';
            } else {
                incomeField.disabled = false;
            }
        }

        function validateForm() {
            const age = document.getElementById('age').value;
            if (age < 0 || age > 120) {
                alert('Please enter a valid age between 0 and 120');
                return false;
            }
            
            const purok = document.getElementById('purok_id').value;
            if (!purok) {
                alert('Please select a purok');
                return false;
            }
            
            return true;
        }
    </script>
</head>
<body>
    <div class="census-container">
        <div class="census-header">
            <div class="census-logo-bg">
                <img src="logo.png" alt="Barangay Logo" class="census-logo">
            </div>
            <h1>Barangay Census Form</h1>
            <p>Help us improve our community services by completing this form</p>
        </div>

        <?php if (isset($_SESSION['census_success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['census_success']; unset($_SESSION['census_success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="census-form" onsubmit="return validateForm()">
            <div class="form-section">
                <h2>Personal Information</h2>
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" min="0" max="120" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="civil_status">Civil Status</label>
                    <select id="civil_status" name="civil_status" required>
                        <option value="">Select Status</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Divorced">Divorced</option>
                        <option value="Widowed">Widowed</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="purok_id">Purok</label>
                    <select id="purok_id" name="purok_id" required>
                        <option value="">Select Purok</option>
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <option value="<?php echo $i; ?>">Purok <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h2>Contact Information</h2>
                <div class="form-group">
                    <label for="address">Complete Address</label>
                    <textarea id="address" name="address" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label for="contact_number">Contact Number</label>
                    <input type="tel" id="contact_number" name="contact_number" pattern="[0-9]{11}" title="Please enter a valid 11-digit phone number" required>
                </div>
            </div>

            <div class="form-section">
                <h2>Economic Information</h2>
                <div class="form-group">
                    <label for="education_level">Education Level</label>
                    <select id="education_level" name="education_level" required>
                        <option value="">Select Education Level</option>
                        <option value="Elementary">Elementary</option>
                        <option value="High School">High School</option>
                        <option value="College">College</option>
                        <option value="Postgraduate">Postgraduate</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="employment_status">Employment Status</label>
                    <select id="employment_status" name="employment_status" required onchange="handleEmploymentChange()">
                        <option value="">Select Employment Status</option>
                        <option value="Employed">Employed</option>
                        <option value="Unemployed">Unemployed</option>
                        <option value="Student">Student</option>
                        <option value="Retired">Retired</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="monthly_income">Monthly Income (PHP)</label>
                    <input type="number" id="monthly_income" name="monthly_income" min="0" step="1000" required>
                </div>
                <div class="form-group">
                    <label for="household_size">Household Size</label>
                    <input type="number" id="household_size" name="household_size" min="1" max="20" required>
                </div>
            </div>

            <div class="form-section">
    <h2>Community Event Participation Survey</h2>

    <div class="form-group">
        <label for="sports_interest">Would you join basketball or volleyball tournaments?</label>
        <select name="sports_interest" id="sports_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="health_interest">Would you attend medical check-ups or vaccination drives?</label>
        <select name="health_interest" id="health_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="nutrition_interest">Are you interested in participating in a feeding program?</label>
        <select name="nutrition_interest" id="nutrition_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="assistive_interest">Would you benefit from assistive devices (e.g., for PWDs)?</label>
        <select name="assistive_interest" id="assistive_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="livelihood_interest">Would you attend livelihood or skills training programs?</label>
        <select name="livelihood_interest" id="livelihood_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="aid_interest">Would you apply for financial assistance (e.g., PWD aid)?</label>
        <select name="aid_interest" id="aid_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="disaster_interest">Would you participate in disaster preparedness activities?</label>
        <select name="disaster_interest" id="disaster_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="road_clearing_interest">Would you help in road clearing or clean-up operations?</label>
        <select name="road_clearing_interest" id="road_clearing_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="cleanup_interest">Would you volunteer in community clean-up drives?</label>
        <select name="cleanup_interest" id="cleanup_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>

    <div class="form-group">
        <label for="waste_mgmt_interest">Would you join or support waste management programs?</label>
        <select name="waste_mgmt_interest" id="waste_mgmt_interest" required>
            <option value="">Select answer</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>
    </div>
</div>


            <div class="form-submit">
                <button type="submit" class="submit-btn">Submit Census Form</button>
            </div>
        </form>

        <div class="census-footer">
            <p>This information will be used for community planning and resident registration</p>
            <p class="copyright">&copy; <?php echo date('Y'); ?> Barangay Information System</p>
        </div>
    </div>
</body>
</html>
