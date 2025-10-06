<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$is_logged_in = isLoggedIn();

// Try to fetch PAGASA real data
$pagasa_url = "https://api.pagasa.dost.gov.ph/weather/pampanga";
$weather_data = @json_decode(file_get_contents($pagasa_url), true);

// ===== Fallback simulated data if API not reachable =====
if (!$weather_data || !isset($weather_data['rainfall'])) {
    $weather_data = [
        "rainfall" => [200, 250, 300, 400, 450, 500, 550, 480, 350, 250, 200, 150],
        "temperature" => [30, 31, 33, 34, 35, 36, 35, 34, 33, 32, 31, 30]
    ];
}
$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

// ===== Calculate risks =====
$risk_data = [];
for ($i=0; $i<12; $i++) {
    $month = $months[$i];
    $rain = $weather_data['rainfall'][$i] ?? 0;
    $temp = $weather_data['temperature'][$i] ?? 0;

    $dengue_risk  = min(100, ($rain / 500) * 100);
    $flood_risk   = min(100, ($rain / 550) * 100);
    $heat_risk    = max(0, (($temp - 30) / 10) * 100);
    $drought_risk = max(0, (1 - ($rain / 500)) * 100);
    $overall = round(($dengue_risk + $flood_risk + $heat_risk + $drought_risk) / 4);

    $risk_data[] = [
        'month' => $month,
        'rainfall' => $rain,
        'temperature' => $temp,
        'dengue' => round($dengue_risk, 1),
        'flood' => round($flood_risk, 1),
        'heat' => round($heat_risk, 1),
        'drought' => round($drought_risk, 1),
        'overall' => $overall
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Predictive Forecast - Barangay Profiling System</title>
<link rel="stylesheet" href="predictive.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Center containers */
.graph-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.graph-container h2 {
    text-align: center;
    margin-bottom: 10px;
    color: #1d3b71;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center; align-items: center;
}
.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    width: 400px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    animation: zoomIn 0.3s ease;
}
@keyframes zoomIn {
    from { transform: scale(0.8); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.modal-header { font-size: 20px; font-weight: bold; margin-bottom: 10px; }
.close-btn { float: right; cursor: pointer; color: #666; font-size: 20px; }
.close-btn:hover { color: red; }
.risk-item { margin: 8px 0; }
.risk-item span { font-weight: bold; }
</style>
</head>
<body>
<div class="dashboard-container">
    <!-- === Sidebar (unchanged) === -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="logo-link">
                <img src="logo.png" alt="Admin & Staff Logo" class="header-logo">
            </a>
            <h2>A Web-based Barangay Demographic Profiling System</h2>
            <?php if ($is_logged_in): ?>
                <div class="welcome">
                    <p>Welcome, <?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'User'); ?></p>
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
                <li><a href="analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                <li><a href="predictive.php" class="active"><i class="fas fa-brain"></i> Predictive Models</a></li>
                <li><a href="events.php"><i class="fas fa-calendar-alt"></i> Events</a></li>
                <li><a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                <?php if ($is_logged_in): ?>
                    <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- === Main Content === -->
    <div class="main-content">
        <h1><i class="fas fa-brain"></i> Predictive Models</h1>

        <!-- ===== Weather Risk Forecast Graph ===== -->
        <div class="graph-container">
            <h2>🌦 Weather Risk Forecast</h2>
            <canvas id="riskChart"></canvas>
        </div>

        <!-- Modal for weather risk -->
        <div id="riskModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" id="closeRiskModal">&times;</span>
                <div id="riskModalBody"></div>
            </div>
        </div>

        <!-- ===== Event Suitability Forecast Graph ===== -->
        <div class="graph-container">
            <h2>📅 Event Suitability Forecast</h2>
            <canvas id="eventChart"></canvas>
        </div>

        <!-- Modal for event recommendations -->
        <div id="eventModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" id="closeEventModal">&times;</span>
                <div id="eventModalBody"></div>
            </div>
        </div>
    </div>
</div>

<script>
const months = <?= json_encode(array_column($risk_data, 'month')); ?>;
const dengue = <?= json_encode(array_column($risk_data, 'dengue')); ?>;
const flood = <?= json_encode(array_column($risk_data, 'flood')); ?>;
const heat = <?= json_encode(array_column($risk_data, 'heat')); ?>;
const drought = <?= json_encode(array_column($risk_data, 'drought')); ?>;
const rainfall = <?= json_encode(array_column($risk_data, 'rainfall')); ?>;
const temp = <?= json_encode(array_column($risk_data, 'temperature')); ?>;

// ===== WEATHER RISK CHART =====
const ctx1 = document.getElementById('riskChart').getContext('2d');
const riskChart = new Chart(ctx1, {
    type: 'line',
    data: {
        labels: months,
        datasets: [
            { label: 'Dengue/Flood Risk', data: dengue, borderColor: '#FF6384', backgroundColor: 'rgba(255,99,132,0.2)', tension: 0.4, fill: true },
            { label: 'Heat Risk', data: heat, borderColor: '#FFCE56', backgroundColor: 'rgba(255,206,86,0.2)', tension: 0.4, fill: true },
            { label: 'Drought Risk', data: drought, borderColor: '#4BC0C0', backgroundColor: 'rgba(75,192,192,0.2)', tension: 0.4, fill: true }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' }, title: { display: true, text: 'Weather-Based Monthly Risk Forecast' } },
        scales: { y: { beginAtZero: true, max: 100, title: { display: true, text: 'Risk Level (%)' } } },
        onClick: (e) => {
            const points = riskChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
            if (points.length) showRiskModal(points[0].index);
        }
    }
});

// ===== WEATHER MODAL =====
const riskModal = document.getElementById('riskModal');
const closeRiskModal = document.getElementById('closeRiskModal');
const riskModalBody = document.getElementById('riskModalBody');
closeRiskModal.onclick = () => riskModal.style.display = 'none';
window.onclick = (e) => { if (e.target === riskModal) riskModal.style.display = 'none'; };

function showRiskModal(i) {
    const rain = rainfall[i], t = temp[i];
    let risk = "Stable", rec = "Normal monitoring.";
    if (rain > 450) { risk = "🚨 Flood / Dengue Risk"; rec = "Clean drainage and prepare flood kits."; }
    else if (t > 35) { risk = "🔥 Heat Stroke Risk"; rec = "Advise hydration, avoid long outdoor exposure."; }
    else if (rain < 150 && t > 33) { risk = "🌾 Drought Risk"; rec = "Encourage water conservation and planting."; }
    riskModalBody.innerHTML = `
        <div class="modal-header">${months[i]} Risk Details</div>
        <div class="risk-item"><span>Rainfall:</span> ${rain} mm</div>
        <div class="risk-item"><span>Temperature:</span> ${t} °C</div>
        <div class="risk-item"><span>Main Risk:</span> ${risk}</div>
        <div class="risk-item"><span>Recommendation:</span> ${rec}</div>
    `;
    riskModal.style.display = 'flex';
}

// ===== EVENT SUITABILITY CHART =====
const ctx2 = document.getElementById('eventChart').getContext('2d');
const suitability = months.map((_, i) => {
    const r = rainfall[i], t = temp[i];
    if (r > 400) return 90;
    if (t >= 33 && r < 200) return 80;
    if (r > 300 && t < 33) return 75;
    if (r < 150) return 65;
    return 70;
});
const eventChart = new Chart(ctx2, {
    type: 'line',
    data: { labels: months, datasets: [{ label: 'Event Suitability', data: suitability, borderColor: '#1D3B71', backgroundColor: 'rgba(29,59,113,0.2)', tension: 0.4, fill: true }] },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' }, title: { display: true, text: 'Monthly Event Suitability Forecast' } },
        onClick: (e) => {
            const points = eventChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
            if (points.length) showEventModal(points[0].index);
        }
    }
});

// ===== EVENT MODAL =====
const eventModal = document.getElementById('eventModal');
const closeEventModal = document.getElementById('closeEventModal');
const eventModalBody = document.getElementById('eventModalBody');
closeEventModal.onclick = () => eventModal.style.display = 'none';
window.onclick = (e) => { if (e.target === eventModal) eventModal.style.display = 'none'; };

function showEventModal(i) {
    const month = months[i], rain = rainfall[i], t = temp[i];
    let events = [];
    if (t >= 33 && rain < 200)
        events = ["🏀 Basketball League", "🏐 Volleyball Tournament", "💪 Livelihood Training"];
    else if (rain > 350 && t < 34)
        events = ["💉 Vaccination Drive", "🩺 Medical Check-ups", "🍲 Feeding Program"];
    else if (rain > 400)
        events = ["⚠️ Disaster Preparedness", "🚜 Road Clearing", "🌊 Flood Awareness"];
    else if (rain < 150 && t < 33)
        events = ["🌿 Clean-Up Drive", "🚮 Waste Management", "💰 Financial Aid for PWDs"];
    else
        events = ["🤝 Livelihood & Social Support", "🩺 Health & Wellness Initiative"];
    eventModalBody.innerHTML = `
        <div class="modal-header">${month} Recommended Events</div>
        <ul>${events.map(e => `<li>${e}</li>`).join('')}</ul>
    `;
    eventModal.style.display = 'flex';
}
</script>
</body>
</html>
