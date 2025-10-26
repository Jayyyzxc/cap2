<?php
// FRONT-END ONLY (UI PREVIEW)
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

    <form class="registration-form">

        <!-- 🧍 PERSONAL INFORMATION -->
        <div class="form-section">
            <h2><i class="fas fa-user"></i> Personal Information</h2>

            <div class="form-row">
                <div class="form-group"><label>Full Name</label><input type="text" placeholder="Enter full name"></div>
                <div class="form-group"><label>Birthdate</label><input type="date"></div>
                <div class="form-group"><label>Gender</label>
                    <select><option value="">Select</option><option>Male</option><option>Female</option></select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>Address</label><input type="text" placeholder="Enter address"></div>
                <div class="form-group"><label>Contact Number</label><input type="tel" maxlength="11" placeholder="09XXXXXXXXX" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>Email</label><input type="email" placeholder="Enter email address"></div>
                <div class="form-group"><label>Username</label><input type="text" placeholder="Enter username"></div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>Password</label><input type="password" placeholder="Enter password"></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" placeholder="Re-enter password"></div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>Position</label><input type="text" placeholder="Enter your position"></div>
                <div class="form-group"><label>Start Term</label><input type="date"></div>
                <div class="form-group"><label>End Term</label><input type="date"></div>
            </div>

            <!-- 🪪 OFFICIAL ID UPLOAD -->
            <div class="form-group">
                <label>Upload Official ID</label>
                <label class="id-upload" for="official_id_upload">
                    <i class="fas fa-id-card"></i>
                    <p>Click here to upload your Barangay ID (JPG or PNG)</p>
                    <input type="file" id="official_id_upload" accept="image/*" onchange="previewID(event)">
                    <img id="idPreview" class="id-preview" alt="ID Preview" onclick="openModal()">
                </label>
            </div>
        </div>

        <!-- 🏘️ BARANGAY INFORMATION -->
        <div class="form-section">
            <h2>Barangay Information</h2>

            <div class="form-row">
                <div class="form-group"><label>Barangay Name</label><input type="text" placeholder="Enter Barangay Name"></div>
                <div class="form-group"><label>Municipality/City</label><input type="text" placeholder="Enter Municipality or City"></div>
                <div class="form-group"><label>Province</label><input type="text" placeholder="Enter Province"></div>
            </div>

            <!-- 📞 Contact Info -->
            <h3>Barangay Contact Information</h3>
            <div class="form-row">
                <div class="form-group"><label>Barangay Hall Address</label><input type="text" placeholder="Enter Barangay Hall Address"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Contact Number</label><input type="tel" maxlength="11" placeholder="09XXXXXXXXX" oninput="this.value=this.value.replace(/[^0-9]/g,'')"></div>
                <div class="form-group"><label>Email Address</label><input type="email" placeholder="Enter official barangay email"></div>
                <div class="form-group"><label>Website / Facebook Page</label><input type="text" placeholder="Enter link or page name"></div>
            </div>

            <hr>

            <!-- 🏡 Barangay Profile -->
            <h3>Barangay Profile</h3>
            <div class="form-row">
                <div class="form-group"><label>Total Population</label><input type="number" placeholder="Enter total population"></div>
                <div class="form-group"><label>Total Households</label><input type="number" placeholder="Enter total households"></div>
            </div>

            <div class="form-row">
                <div class="form-group"><label>How Many Puroks in the Barangay?</label>
                    <input type="number" id="purokCount" placeholder="Enter number of Puroks" min="1" onchange="generatePurokFields()">
                </div>
            </div>

            <div class="form-group" id="purokNamesContainer"></div>

            <hr>

            <!-- 🌊 Disaster and Risk Management -->
            <h3>Disaster and Risk Management</h3>
            <div class="form-row">
                <div class="form-group"><label>Is your barangay flood-prone?</label>
                    <select><option value="">Select</option><option>Yes</option><option>No</option></select>
                </div>
                <div class="form-group"><label>Most affected areas</label><input type="text" placeholder="Enter affected areas"></div>
            </div>

            <div class="form-group"><label>Designated Evacuation Center</label><input type="text" placeholder="Enter evacuation center"></div>
            <div class="form-group"><label>Preparedness Plans & Equipment</label><textarea rows="3" placeholder="Enter preparedness details"></textarea></div>
            <div class="form-group"><label>Coordination with LGU/MDRRMO</label><textarea rows="3" placeholder="Enter coordination details"></textarea></div>

            <hr>

            <!-- 💰 Barangay Budget and Finance -->
            <h3>Barangay Budget and Finance</h3>
            <div class="form-row">
                <div class="form-group"><label>Annual Budget (₱)</label><input type="number" placeholder="Enter annual budget"></div>
            </div>
            <div class="form-group">
                <label>Budget Allocations / Sources of Funds</label>
                <textarea rows="3" placeholder="Describe where the budget is allocated (e.g., health, infrastructure, education, disaster funds)"></textarea>
            </div>
            <div class="form-group"><label>Top Spending Priorities</label><textarea rows="3" placeholder="Enter spending priorities"></textarea></div>
        </div>

        <!-- SUBMIT BUTTON -->
        <div class="form-submit">
            <button type="button" class="submit-btn"><i class="fas fa-user-plus"></i> Register as Official</button>
            <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </form>
</div>

<!-- 🖼 Modal for ID Preview -->
<div id="idModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>ID Preview</h3>
        <img id="modalImage" src="" alt="Uploaded ID">
    </div>
</div>

<script>
let previewURL = '';

function previewID(event) {
    const preview = document.getElementById('idPreview');
    const file = event.target.files[0];
    if (file) {
        previewURL = URL.createObjectURL(file);
        preview.src = previewURL;
        preview.style.display = 'block';
    }
}

function openModal() {
    if (!previewURL) return;
    document.getElementById('modalImage').src = previewURL;
    document.getElementById('idModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('idModal').style.display = 'none';
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
            input.placeholder = `Enter name for Purok ${i}`;
            input.style.marginTop = "8px";
            container.appendChild(input);
        }
    }
}
</script>

</body>
</html>

