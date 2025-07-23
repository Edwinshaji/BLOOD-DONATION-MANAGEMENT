<?php
$required_role = "admin";
include '../includes/auth.php';
include '../config/db.php';

// Blood donations over last 7 days
$donation_data = [];
$labels = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT COUNT(*) FROM donations WHERE DATE(date) = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $labels[] = $date;
    $donation_data[] = $count;
}

// Donor roles distribution
$role_stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roles_data = [];
$roles_labels = [];
while ($row = $role_stmt->fetch_assoc()) {
    $roles_labels[] = ucfirst($row['role']);
    $roles_data[] = $row['count'];
}

// Institution status breakdown
$status_stmt = $conn->query("SELECT status, COUNT(*) as count FROM institutions GROUP BY status");
$status_data = [];
$status_labels = [];
while ($row = $status_stmt->fetch_assoc()) {
    $status_labels[] = ucfirst($row['status']);
    $status_data[] = $row['count'];
}

// Top 5 donating institutions (colleges & hospitals)
$top_institutions = $conn->query("
    SELECT i.name, COUNT(d.donation_id) AS total_donations
    FROM donations d
    JOIN donors dn ON d.donor_id = dn.donor_id
    JOIN users u ON dn.user_id = u.user_id
    JOIN institutions i ON u.institution_id = i.institution_id
    GROUP BY i.institution_id
    ORDER BY total_donations DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);


// Weekly and Monthly Donations
$weekly_trend = ['labels' => [], 'data' => []];
$monthly_trend = ['labels' => [], 'data' => []];

// This week (last 7 days)
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $conn->prepare("SELECT COUNT(*) FROM donations WHERE DATE(date) = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $weekly_trend['labels'][] = $date;
    $weekly_trend['data'][] = $count;
}

// This month (day-wise)
for ($d = 1; $d <= date('t'); $d++) {
    $date = date('Y-m-d', strtotime(date('Y-m-') . str_pad($d, 2, '0', STR_PAD_LEFT)));
    $stmt = $conn->prepare("SELECT COUNT(*) FROM donations WHERE DATE(date) = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $monthly_trend['labels'][] = $date;
    $monthly_trend['data'][] = $count;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>System Analytics</title>
    <?php include '../includes/header.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Montserrat', Arial, sans-serif;
        }

        .analytics-section {
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .chart-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #e57373;
        }

        @media (max-width: 768px) {
            canvas {
                max-width: 100% !important;
            }
        }
    </style>
</head>

<body>
    <?php include 'admin_layout_start.php'; ?>

    <div class="container-fluid analytics-section">
        <h2 class="fw-bold text-secondary mb-4">System Analytics</h2>

        <div class="row">
            <!-- Blood Donations (Last 7 Days) -->
            <div class="col-12 col-lg-6">
                <div class="chart-card">
                    <div class="chart-title">Blood Donations (Last 7 Days)</div>
                    <canvas id="donationChart"></canvas>
                </div>
            </div>

            <!-- Institution Status -->
            <div class="col-12 col-lg-6">
                <div class="chart-card">
                    <div class="chart-title">Institution Status</div>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Top Donating Institutions -->
            <div class="col-12 col-lg-6">
                <div class="chart-card">
                    <div class="chart-title">Top Donating Colleges</div>
                    <canvas id="topInstitutionsChart"></canvas>
                </div>
            </div>

            <!-- Weekly vs Monthly Donation Trends -->
            <div class="col-12 col-lg-6">
                <div class="chart-card">
                    <div class="chart-title">Weekly vs Monthly Donation Trends</div>
                    <canvas id="weeklyMonthlyChart"></canvas>
                </div>
            </div>

            <!-- Donor Role Distribution -->
            <div class="col-12 col-lg-6">
                <div class="chart-card">
                    <div class="chart-title">Donor Role Distribution</div>
                    <canvas id="roleChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <?php include 'admin_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script>
        // Last 7 Days
        new Chart(document.getElementById('donationChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Donations',
                    data: <?= json_encode($donation_data) ?>,
                    backgroundColor: 'rgba(229,115,115,0.2)',
                    borderColor: '#e57373',
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Role Distribution
        new Chart(document.getElementById('roleChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($roles_labels) ?>,
                datasets: [{
                    data: <?= json_encode($roles_data) ?>,
                    backgroundColor: ['#e57373', '#64b5f6', '#81c784']
                }]
            },
            options: {
                responsive: true
            }
        });

        // Institution Status
        new Chart(document.getElementById('statusChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($status_labels) ?>,
                datasets: [{
                    label: 'Institutions',
                    data: <?= json_encode($status_data) ?>,
                    backgroundColor: '#ba68c8'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Top Donating Institutions
        new Chart(document.getElementById('topInstitutionsChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($top_institutions, 'name')) ?>,
                datasets: [{
                    label: 'Total Donations',
                    data: <?= json_encode(array_column($top_institutions, 'total_donations')) ?>,
                    backgroundColor: 'rgba(108, 99, 255, 0.7)'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Weekly vs Monthly Trend
        new Chart(document.getElementById('weeklyMonthlyChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($weekly_trend['labels']) ?>,
                datasets: [{
                        label: 'Weekly Donations',
                        data: <?= json_encode($weekly_trend['data']) ?>,
                        borderColor: '#6a82fb',
                        backgroundColor: 'rgba(106, 130, 251, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Monthly Avg',
                        data: <?= json_encode(array_slice($monthly_trend['data'], -7)) ?>,
                        borderColor: '#fc5c7d',
                        backgroundColor: 'rgba(252, 92, 125, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>