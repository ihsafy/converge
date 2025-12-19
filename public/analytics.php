<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$pdo = getDB();
$role = $user['role'];

// --- 1. DATA FETCHING ---

$chartData = [];

// ADMIN & MANAGER DATA
if (in_array($role, ['super_admin', 'event_manager', 'member_manager'])) {
    
    // A. User Roles Ratio
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $rolesData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // B. User Active Status Ratio (Admin Only)
    $statusData = [];
    if ($role === 'super_admin' || $role === 'member_manager') {
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
        $statusData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    // C. Events Ratio
    $upcoming = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW()")->fetchColumn();
    $past = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date < NOW()")->fetchColumn();
    $eventData = ['Upcoming' => $upcoming, 'Past' => $past];
}

// MEMBER DATA
if ($role === 'member') {
    $myId = $user['id'];
    $totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $myRegs = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = ?");
    $myRegs->execute([$myId]);
    $registeredCount = $myRegs->fetchColumn();
    $notRegistered = $totalEvents - $registeredCount;
    
    $memberData = ['Registered' => $registeredCount, 'Not Registered' => $notRegistered];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Analytics & Reports - CONVERGE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; } /* Wider Container */
        
        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .back-btn { text-decoration: none; color: #64748b; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        
        /* Grid - Made columns wider (minmax 450px) */
        .charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 30px; }
        
        /* Card */
        .chart-card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
        .chart-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin-bottom: 20px; text-align: center; }
        
        /* Canvas Sizing - BIGGER HEIGHT */
        .canvas-container { position: relative; height: 400px; width: 100%; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <a href="<?= ($role == 'member') ? 'member_dashboard.php' : 'admin_dashboard.php' ?>" class="back-btn">&larr; Back to Dashboard</a>
            <h1 style="margin:10px 0 0; color:#0f172a; font-size: 2rem;">Analytics Dashboard</h1>
        </div>
    </div>

    <div class="charts-grid">

        <?php if (!empty($rolesData)): ?>
        <div class="chart-card">
            <div class="chart-title">👥 User Roles Distribution</div>
            <div class="canvas-container">
                <canvas id="roleChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($statusData)): ?>
        <div class="chart-card">
            <div class="chart-title">⚡ User Account Status</div>
            <div class="canvas-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($eventData)): ?>
        <div class="chart-card">
            <div class="chart-title">📅 Event Status (Upcoming vs Past)</div>
            <div class="canvas-container">
                <canvas id="eventChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($memberData)): ?>
        <div class="chart-card">
            <div class="chart-title">🎟️ My Event Participation</div>
            <div class="canvas-container">
                <canvas id="memberChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
    // --- COMMON ANIMATION SETTINGS ---
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 2000, // 2 Seconds Animation
            easing: 'easeOutQuart'
        },
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 14 } } }
        }
    };

    // 1. Role Chart (Pie)
    <?php if (!empty($rolesData)): ?>
    new Chart(document.getElementById('roleChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($rolesData)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($rolesData)) ?>,
                backgroundColor: ['#3b82f6', '#8b5cf6', '#10b981'],
                borderWidth: 2
            }]
        },
        options: commonOptions
    });
    <?php endif; ?>

    // 2. Status Chart (Doughnut)
    <?php if (!empty($statusData)): ?>
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($statusData)) ?>,
            datasets: [{
                data: <?= json_encode(array_values($statusData)) ?>,
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                hoverOffset: 20 // Pops out when hovered
            }]
        },
        options: {
            ...commonOptions,
            cutout: '60%' // Thinner ring
        }
    });
    <?php endif; ?>

    // 3. Event Chart (Bar)
    <?php if (isset($eventData)): ?>
    new Chart(document.getElementById('eventChart'), {
        type: 'bar',
        data: {
            labels: ['Upcoming', 'Past'],
            datasets: [{
                label: 'Events',
                data: [<?= $eventData['Upcoming'] ?>, <?= $eventData['Past'] ?>],
                backgroundColor: ['#3b82f6', '#94a3b8'],
                borderRadius: 8 // Rounded corners
            }]
        },
        options: {
            ...commonOptions,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
    <?php endif; ?>

    // 4. Member Chart (Pie)
    <?php if (isset($memberData)): ?>
    new Chart(document.getElementById('memberChart'), {
        type: 'pie',
        data: {
            labels: ['Registered', 'Not Registered'],
            datasets: [{
                data: [<?= $memberData['Registered'] ?>, <?= $memberData['Not Registered'] ?>],
                backgroundColor: ['#10b981', '#e2e8f0']
            }]
        },
        options: commonOptions
    });
    <?php endif; ?>
</script>

</body>
</html>