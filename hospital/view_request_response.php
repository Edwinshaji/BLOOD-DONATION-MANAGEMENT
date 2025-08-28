<?php
$required_role = ['hospital'];
include '../includes/auth.php';
include '../config/db.php';

if (!isset($_GET['request_id'])) {
    header("Location: emergency_requests.php");
    exit;
}

$request_id = intval($_GET['request_id']);
$institution_id = $_SESSION['institution_id'];

// Fetch request details
$stmt = $conn->prepare("SELECT * FROM emergency_requests WHERE request_id=? AND institution_id=?");
$stmt->bind_param("ii", $request_id, $institution_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    $_SESSION['error'] = "Request not found or unauthorized access.";
    header("Location: emergency_requests.php");
    exit;
}

/* ✅ Mark donor as donated */
if (isset($_GET['donate_id'])) {
    $response_id = intval($_GET['donate_id']);

    // Fetch request units
    $stmt = $conn->prepare("SELECT units_needed, units_donated FROM emergency_requests WHERE request_id=?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $req_data = $stmt->get_result()->fetch_assoc();

    if ($req_data) {
        $units_needed = $req_data['units_needed'];
        $units_donated = $req_data['units_donated'];

        // Fetch donor details & hospital coords
        $stmt = $conn->prepare("
            SELECT rr.user_id, d.donor_id, i.latitude, i.longitude 
            FROM request_responses rr
            JOIN donors d ON rr.user_id = d.user_id
            JOIN institutions i ON i.institution_id = ?
            WHERE rr.response_id = ?
        ");
        $stmt->bind_param("ii", $institution_id, $response_id);
        $stmt->execute();
        $donor_data = $stmt->get_result()->fetch_assoc();

        if ($donor_data) {
            $donor_id = $donor_data['donor_id'];
            $lat = $donor_data['latitude'];
            $lng = $donor_data['longitude'];

            // 🔹 Reverse geocode hospital lat/lng
            $location_name = "Unknown Location";
            if (!empty($lat) && !empty($lng)) {
                $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=14&addressdetails=1";
                $options = ["http" => ["header" => "User-Agent: BloodDonationApp/1.0\r\n"]];
                $context = stream_context_create($options);
                $response = file_get_contents($url, false, $context);
                if ($response !== false) {
                    $json = json_decode($response, true);
                    if (isset($json['display_name'])) {
                        $location_name = $json['display_name'];
                    }
                }
            }

            // ✅ Insert donation with request_id
            $stmt = $conn->prepare("
                INSERT INTO donations (donor_id, request_id, event_id, date, location, verified_by)
                VALUES (?, ?, NULL, CURDATE(), ?, ?)
            ");
            $stmt->bind_param("iisi", $donor_id, $request_id, $location_name, $institution_id);
            $stmt->execute();

            // Update donor's last_donated
            $stmt = $conn->prepare("UPDATE donors SET last_donated = CURDATE() WHERE donor_id=?");
            $stmt->bind_param("i", $donor_id);
            $stmt->execute();

            // Increase units_donated
            $stmt = $conn->prepare("UPDATE emergency_requests SET units_donated = units_donated + 1 WHERE request_id=?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();

            // Check if request is now fulfilled
            if ($units_donated + 1 >= $units_needed) {
                $stmt = $conn->prepare("UPDATE emergency_requests SET status='fulfilled' WHERE request_id=?");
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
            }
        }
    }

    $_SESSION['success'] = "Donation marked successfully!";
    header("Location: view_request_response.php?request_id=" . $request_id);
    exit;
}

/* ❌ Reject donor → reset donation if exists */
if (isset($_GET['reject_id'])) {
    $response_id = intval($_GET['reject_id']);

    // Find donor_id for this response
    $stmt = $conn->prepare("SELECT d.donor_id 
                            FROM request_responses rr
                            JOIN donors d ON rr.user_id = d.user_id
                            WHERE rr.response_id=?");
    $stmt->bind_param("i", $response_id);
    $stmt->execute();
    $donor = $stmt->get_result()->fetch_assoc();

    if ($donor) {
        $donor_id = $donor['donor_id'];

        // Delete donation record for this request & donor
        $stmt = $conn->prepare("DELETE FROM donations WHERE request_id=? AND donor_id=?");
        $stmt->bind_param("ii", $request_id, $donor_id);
        $stmt->execute();

        // Reduce units_donated
        $stmt = $conn->prepare("UPDATE emergency_requests 
                                SET units_donated = GREATEST(units_donated - 1, 0), 
                                    status = 'pending'
                                WHERE request_id=?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
    }

    $_SESSION['error'] = "Donor rejected, request updated!";
    header("Location: view_request_response.php?request_id=" . $request_id);
    exit;
}

/* 🔹 Fetch all responses */
$stmt = $conn->prepare("SELECT rr.response_id, rr.user_id, rr.status, rr.responded_at, 
    u.name, u.email, u.phone, u.role
    FROM request_responses rr
    JOIN users u ON rr.user_id=u.user_id
    WHERE rr.request_id=?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$responses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* 🔹 Find which responders already donated (by request_id) */
$stmt = $conn->prepare("
    SELECT d.donation_id, d.donor_id, rr.response_id
    FROM donations d
    JOIN donors dn ON d.donor_id = dn.donor_id
    JOIN request_responses rr ON rr.user_id = dn.user_id
    WHERE rr.request_id = ? AND d.request_id = ?
");
$stmt->bind_param("ii", $request_id, $request_id);
$stmt->execute();
$donated_responses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build a lookup array for donated responders
$donated_lookup = [];
foreach ($donated_responses as $dr) {
    $donated_lookup[$dr['response_id']] = true;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Emergency Request - Hospital Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <style>
        .event-card {
            background: linear-gradient(135deg, #fff, #f8f9fa);
        }

        .badge-lg {
            font-size: 1rem;
            padding: 10px 14px;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <?php include 'hospital_layout_start.php'; ?>

    <div class="container py-4">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success shadow-sm"><?= $_SESSION['success'];
                                                        unset($_SESSION['success']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger shadow-sm"><?= $_SESSION['error'];
                                                        unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Emergency Request Details -->
        <div class="event-card shadow-lg border-0 rounded-4 bg-light w-100 p-4">
            <div class="text-center">
                <h2 class="text-danger fw-bold mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Emergency Request
                </h2>
                <hr class="border-danger">

                <div class="row g-4 justify-content-center">
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                            <p class="fw-bold text-muted mb-1">Blood Group</p>
                            <span class="badge bg-danger fs-6"><?= htmlspecialchars($request['blood_group']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                            <p class="fw-bold text-muted mb-1">Units</p>
                            <span class="badge bg-info fs-6">
                                <?= $request['units_donated'] ?> / <?= $request['units_needed'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                            <p class="fw-bold text-muted mb-1">Status</p>
                            <?php if ($request['status'] === 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php elseif ($request['status'] === 'fulfilled'): ?>
                                <span class="badge bg-success">Fulfilled</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Cancelled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                            <p class="fw-bold text-muted mb-1">Posted On</p>
                            <span><?= date("M d, Y H:i", strtotime($request['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                        <p class="fw-bold text-muted mb-1">Message</p>
                        <p><?= nl2br(htmlspecialchars($request['message'])) ?></p>
                    </div>
                </div>

                <a href="emergency_requests.php" class="btn btn-outline-danger rounded-pill px-4 mt-4">
                    ← Back to Requests
                </a>
            </div>
        </div>

        <!-- Responders Table -->
        <div class="table-responsive mt-4">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-danger">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Responded At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($responses) > 0): ?>
                        <?php foreach ($responses as $i => $res): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($res['name']) ?></td>
                                <td>
                                    <i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($res['phone']) ?><br>
                                    <i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($res['email']) ?>
                                </td>
                                <td>
                                    <?php if ($res['status'] === 'accepted'): ?>
                                        <span class="badge bg-success">Accepted</span>
                                    <?php elseif ($res['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $res['responded_at'] ? date("M d, Y H:i", strtotime($res['responded_at'])) : '-' ?></td>
                                <td>
                                    <?php if ($res['status'] === 'accepted'): ?>
                                        <?php if (isset($donated_lookup[$res['response_id']])): ?>
                                            <span class="badge bg-primary">Donated</span>
                                        <?php else: ?>
                                            <a href="?request_id=<?= $request_id ?>&donate_id=<?= $res['response_id'] ?>"
                                                class="btn btn-sm btn-success"
                                                onclick="return confirm('Mark as donated?');">Donate</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-muted">No responders yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'hospital_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>
</body>

</html>