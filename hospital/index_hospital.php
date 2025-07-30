<?php
$required_role = ['hospital'];
include '../includes/auth.php';
include '../config/db.php';

$institution_id = $_SESSION['institution_id'];

// Fetch hospital coordinates
$stmt = $conn->prepare("SELECT latitude, longitude FROM institutions WHERE institution_id=?");
$stmt->bind_param("i", $institution_id);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();
$hospital_lat = $hospital['latitude'];
$hospital_lng = $hospital['longitude'];

// Stats
$total_events = $conn->query("SELECT COUNT(*) AS total FROM events WHERE institution_id=$institution_id")->fetch_assoc()['total'];
$total_requests = $conn->query("SELECT COUNT(*) AS total FROM emergency_requests WHERE institution_id=$institution_id")->fetch_assoc()['total'];
$todays_requests = $conn->query("SELECT COUNT(*) AS total FROM emergency_requests WHERE institution_id=$institution_id AND DATE(created_at)=CURDATE()")->fetch_assoc()['total'];

// Today’s requests
$today_requests = $conn->query("SELECT blood_group, message, created_at FROM emergency_requests WHERE institution_id=$institution_id AND DATE(created_at)=CURDATE() ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Donor search results
$donors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_donor'])) {
    $blood_group = $_POST['blood_group'];
    $stmt = $conn->prepare("SELECT u.user_id, u.name, u.email, u.phone, u.role, d.blood_group, d.latitude, d.longitude
                            FROM users u
                            JOIN donors d ON u.user_id=d.user_id
                            WHERE d.is_available=1 AND d.blood_group=?
                            ORDER BY (POW(d.latitude-?,2)+POW(d.longitude-?,2)) ASC");
    $stmt->bind_param("sdd", $blood_group, $hospital_lat, $hospital_lng);
    $stmt->execute();
    $donors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Hospital Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <style>
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            color: white;
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: scale(1.03);
        }

        .search-section {
            background: linear-gradient(135deg, #dc3545, #ff6a88);
            border-radius: 15px;
            padding: 25px;
            color: white;
        }
    </style>
</head>

<body>
    <?php include 'hospital_layout_start.php'; ?>

    <div class="container py-4">

        <!-- Search Donors -->
        <div class="search-section shadow-lg mb-4">
            <h4><i class="bi bi-search-heart"></i> Search for Donors Near You</h4>
            <form method="POST" class="row g-3 mt-2">
                <div class="col-md-6">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select" required>
                        <option value="">Select Blood Group</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                            <option value="<?= $bg ?>"><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 align-self-end">
                    <button type="submit" name="search_donor" class="btn btn-light btn-md w-100 rounded-pill">
                        <i class="bi bi-search"></i> Find Donors
                    </button>
                </div>
            </form>

            <!-- Dropdown of Donors -->
            <?php if (!empty($donors)): ?>
                <div class="mt-3">
                    <label class="form-label">Select Donor</label>
                    <select class="form-select" onchange="showDonorModal(this)">
                        <option value="">-- Choose a Donor --</option>
                        <?php foreach ($donors as $index => $d): ?>
                            <option value="<?= $index ?>"><?= htmlspecialchars($d['name']) ?> (<?= $d['blood_group'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div class="alert alert-warning mt-3">No donors found for the selected blood group.</div>
            <?php endif; ?>
        </div>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card shadow bg-danger">
                    <i class="bi bi-calendar-event fs-1"></i>
                    <h3><?= $total_events ?></h3>
                    <p>Events Conducted</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card shadow bg-warning text-dark">
                    <i class="bi bi-exclamation-circle fs-1"></i>
                    <h3><?= $total_requests ?></h3>
                    <p>Total Emergency Requests</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card shadow bg-success">
                    <i class="bi bi-lightning-charge fs-1"></i>
                    <h3><?= $todays_requests ?></h3>
                    <p>Today's Requests</p>
                </div>
            </div>
        </div>

        <!-- Today's Emergency Requests -->
        <div class="card shadow-lg">
            <div class="card-body">
                <h4 class="fw-bold text-danger mb-3">
                    <i class="bi bi-exclamation-triangle"></i> Today's Emergency Requests
                </h4>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Blood Group</th>
                                <th>Message</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($today_requests) > 0): ?>
                                <?php foreach ($today_requests as $index => $req): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><span class="badge bg-danger"><?= htmlspecialchars($req['blood_group']) ?></span></td>
                                        <td><?= htmlspecialchars($req['message']) ?></td>
                                        <td><?= date("H:i", strtotime($req['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-muted">No emergency requests today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- Donor Detail Modal -->
    <div class="modal fade" id="donorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-person-heart"></i> Donor Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="donorDetails">
                    <!-- Filled dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const donors = <?= json_encode($donors) ?>;

        function showDonorModal(select) {
            let index = select.value;
            if (index === "") return;
            let donor = donors[index];
            let details = `
            <p><strong>Name:</strong> ${donor.name}</p>
            <p><strong>Email:</strong> ${donor.email}</p>
            <p><strong>Phone:</strong> ${donor.phone}</p>
            <p><strong>Role:</strong> ${donor.role}</p>
            <p><strong>Blood Group:</strong> ${donor.blood_group}</p>
        `;
            document.getElementById('donorDetails').innerHTML = details;
            new bootstrap.Modal(document.getElementById('donorModal')).show();
        }
    </script>

    <?php include 'hospital_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>
</body>

</html>