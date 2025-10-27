<?php
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

// Approve or reject action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        // Update registration status
        $conn->prepare("UPDATE barangay_registration SET status='approved' WHERE id=?")->execute([$id]);

        // Create 5 census accounts automatically
        for ($i = 1; $i <= 5; $i++) {
            $username = "census_user_" . $id . "_" . $i;
            $password = password_hash("123456", PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO census_users (official_id, username, password, role) VALUES (?, ?, ?, 'census_user')");
            $stmt->execute([$id, $username, $password]);
        }

        echo "<script>alert('Registration approved and 5 census accounts created.');window.location='superadmin.php';</script>";
        exit;
    } elseif ($action === 'reject') {
        $conn->prepare("UPDATE barangay_registration SET status='rejected' WHERE id=?")->execute([$id]);
        echo "<script>alert('Registration rejected.');window.location='superadmin.php';</script>";
        exit;
    }
}

// Fetch all registrations
$stmt = $conn->query("SELECT * FROM barangay_registration ORDER BY created_at DESC");
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #333;
            margin: 0;
            padding: 0;
        }
        h1 {
            background: #1d3b71;
            color: white;
            padding: 15px;
            text-align: center;
            margin: 0;
        }
        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #1d3b71; color: white; }
        tr:hover { background: #f2f5fc; }
        .actions a {
            padding: 6px 12px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            font-size: 0.9em;
            margin-right: 5px;
        }
        .approve { background: #28a745; }
        .reject { background: #dc3545; }
        .status {
            font-weight: bold;
            text-transform: capitalize;
        }
        .status.pending { color: #ff9800; }
        .status.approved { color: #28a745; }
        .status.rejected { color: #dc3545; }
    </style>
</head>
<body>
    <h1>Super Admin Dashboard</h1>
    <table>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Barangay</th>
            <th>Position</th>
            <th>Status</th>
            <th>Official ID</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($registrations as $reg): ?>
        <tr>
            <td><?= htmlspecialchars($reg['id']) ?></td>
            <td><?= htmlspecialchars($reg['full_name']) ?></td>
            <td><?= htmlspecialchars($reg['barangay_name']) ?></td>
            <td><?= htmlspecialchars($reg['position']) ?></td>
            <td class="status <?= htmlspecialchars($reg['status']) ?>"><?= htmlspecialchars($reg['status']) ?></td>
            <td>
                <?php if (!empty($reg['id_path'])): ?>
                    <a href="<?= htmlspecialchars($reg['id_path']) ?>" target="_blank">View ID</a>
                <?php else: ?>No ID<?php endif; ?>
            </td>
            <td class="actions">
                <?php if ($reg['status'] == 'pending'): ?>
                    <a href="?action=approve&id=<?= $reg['id'] ?>" class="approve"><i class="fas fa-check"></i> Approve</a>
                    <a href="?action=reject&id=<?= $reg['id'] ?>" class="reject"><i class="fas fa-times"></i> Reject</a>
                <?php else: ?>
                    <span><?= ucfirst($reg['status']) ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
