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

// Delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM emergency_requests WHERE request_id=? AND institution_id=?");
    $stmt->bind_param("ii", $delete_id, $institution_id);
    $stmt->execute();
    $_SESSION['success'] = "Emergency request deleted.";
    header("Location: emergency_requests.php");
    exit;
}

// Cancel request
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);
    $stmt = $conn->prepare("UPDATE emergency_requests SET status='cancelled' WHERE request_id=? AND institution_id=?");
    $stmt->bind_param("ii", $cancel_id, $institution_id);
    $stmt->execute();
    $_SESSION['success'] = "Emergency request cancelled.";
    header("Location: emergency_requests.php");
    exit;
}

// Publish request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_request'])) {
    $blood_group = $_POST['blood_group'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO emergency_requests 
        (institution_id, blood_group, message, latitude, longitude, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("issdd", $institution_id, $blood_group, $message, $hospital_lat, $hospital_lng);
    $stmt->execute();

    $_SESSION['success'] = "Emergency request published!";
    header("Location: emergency_requests.php");
    exit;
}

// Fetch hospital requests (all statuses)
$all_requests = $conn->prepare("SELECT er.request_id, er.blood_group, er.message, er.status, er.created_at,
    (SELECT COUNT(*) FROM request_responses rr WHERE rr.request_id = er.request_id) AS total_responses
    FROM emergency_requests er
    WHERE er.institution_id=?
    ORDER BY er.created_at DESC");
$all_requests->bind_param("i", $institution_id);
$all_requests->execute();
$active_res = $all_requests->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Emergency Requests - Hospital Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <style>
        .gradient-card {
            background: linear-gradient(135deg, #ff4b5c, #ff6a88);
            color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        .btn-rounded { border-radius: 25px; font-weight: 500; }
        .badge-lg { font-size: 0.9rem; padding: 8px 12px; border-radius: 12px; }
    </style>
</head>
<body>
<?php include 'hospital_layout_start.php'; ?>

<div class="container py-4">

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success shadow-sm"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- Emergency Request Form -->
    <div class="gradient-card mb-4">
        <h4><i class="bi bi-exclamation-triangle-fill"></i> Publish Emergency Request</h4>
        <form method="POST" class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">Blood Group</label>
                <select name="blood_group" class="form-select" required>
                    <option value="">Select</option>
                    <?php foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $bg): ?>
                        <option value="<?= $bg ?>"><?= $bg ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Message</label>
                <input type="text" class="form-control" name="message"
                       placeholder="Eg: Urgent O+ blood needed for surgery" required>
            </div>
            <div class="col-12 text-end">
                <button type="submit" name="publish_request" class="btn btn-light btn-rounded px-4">
                    <i class="bi bi-plus-circle"></i> Publish Request
                </button>
            </div>
        </form>
    </div>

    <!-- All Emergency Requests -->
    <div class="card shadow-lg mb-4">
        <div class="card-body">
            <h4 class="text-danger fw-bold mb-3">
                 Emergency Requests
            </h4>
            <div class="table-responsive table-wrapper">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-danger">
                        <tr>
                            <th>#</th>
                            <th>Blood Group</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Posted On</th>
                            <th>Responses</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($active_res) > 0): ?>
                            <?php foreach ($active_res as $index => $req): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><span class="badge bg-danger"><?= htmlspecialchars($req['blood_group']) ?></span></td>
                                    <td class="text-truncate" style="max-width:220px;" title="<?= htmlspecialchars($req['message']) ?>">
                                        <?= htmlspecialchars($req['message']) ?>
                                    </td>
                                    <td>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($req['status'] === 'fulfilled'): ?>
                                            <span class="badge bg-success">Fulfilled</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date("M d, Y H:i", strtotime($req['created_at'])) ?></td>
                                    <td>
                                        <?= $req['total_responses'] > 0
                                            ? "<span class='badge bg-success'>{$req['total_responses']} Accepted</span>"
                                            : "<span class='badge bg-secondary'>0</span>"; ?>
                                    </td>
                                    <td class="text-nowrap">
                                        <a href="view_request_response.php?request_id=<?= $req['request_id'] ?>"
                                           class="btn btn-sm btn-primary me-1 d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm">
                                           <i class="bi bi-eye-fill"></i> View
                                        </a>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <a href="?cancel_id=<?= $req['request_id'] ?>"
                                               class="btn btn-sm btn-warning me-1 d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm"
                                               onclick="return confirm('Cancel this request?');">
                                               <i class="bi bi-x-circle-fill"></i> Cancel
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete_id=<?= $req['request_id'] ?>"
                                           class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm"
                                           onclick="return confirm('Delete this request?');">
                                           <i class="bi bi-trash-fill"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-muted text-center">No requests yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
<?php include 'hospital_layout_end.php'; ?>
<?php include '../includes/footer.php'; ?>
</body>
</html>
