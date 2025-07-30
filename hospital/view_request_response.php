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

// Mark as donated
if (isset($_GET['donate_id'])) {
    $response_id = intval($_GET['donate_id']);
    $stmt = $conn->prepare("UPDATE request_responses SET status='donated', responded_at=NOW() WHERE response_id=?");
    $stmt->bind_param("i", $response_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE emergency_requests SET status='fulfilled' WHERE request_id=?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();

    $_SESSION['success'] = "Donation marked successfully!";
    header("Location: view_request_response.php?request_id=" . $request_id);
    exit;
}

// Fetch responses
$stmt = $conn->prepare("SELECT rr.response_id, rr.status, rr.responded_at, 
    u.name, u.email, u.phone, u.role
    FROM request_responses rr
    JOIN users u ON rr.user_id=u.user_id
    WHERE rr.request_id=?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$responses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Emergency Request - Hospital Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <style>
        .event-card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
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
        <div class="d-flex justify-content-center">
            <div class="event-card text-center" style="max-width: 650px; width: 100%;">
                <h3 class="text-danger fw-bold mb-3">
                    <i class="bi bi-exclamation-triangle-fill"></i> Emergency Request
                </h3>
                <hr>
                <p class="mb-2">
                    <i class="bi bi-droplet-fill text-danger"></i>
                    <strong>Blood Group:</strong>
                    <span class="badge bg-danger badge-lg"><?= htmlspecialchars($request['blood_group']) ?></span>
                </p>
                <p class="mb-2">
                    <i class="bi bi-card-text text-danger"></i>
                    <strong>Message:</strong> <?= nl2br(htmlspecialchars($request['message'])) ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-calendar-event text-danger"></i>
                    <strong>Posted On:</strong> <?= date("M d, Y H:i", strtotime($request['created_at'])) ?>
                </p>
                <p class="mb-3">
                    <i class="bi bi-flag text-danger"></i>
                    <strong>Status:</strong>
                    <?php if ($request['status'] === 'pending'): ?>
                        <span class="badge bg-warning">Pending</span>
                    <?php elseif ($request['status'] === 'fulfilled'): ?>
                        <span class="badge bg-success">Fulfilled</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Cancelled</span>
                    <?php endif; ?>
                </p>
                <a href="emergency_requests.php" class="btn btn-outline-danger rounded-pill px-4">
                    ← Back to Requests
                </a>
            </div>
        </div>

        <!-- Responders Table -->
        <h4 class="mt-4">Responders</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-danger">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Responded At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($responses) > 0): ?>
                        <?php foreach ($responses as $index => $res): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($res['name']) ?></td>
                                <td>
                                    <i class="bi bi-telephone-fill text-primary"></i> <?= htmlspecialchars($res['phone']) ?><br>
                                    <i class="bi bi-envelope-fill text-danger"></i> <?= htmlspecialchars($res['email']) ?>
                                </td>
                                <td><?= ucfirst($res['role']) ?></td>
                                <td>
                                    <?php if ($res['status'] === 'accepted'): ?>
                                        <span class="badge bg-success">Accepted</span>
                                    <?php elseif ($res['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php elseif ($res['status'] === 'donated'): ?>
                                        <span class="badge bg-primary">Donated</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $res['responded_at'] ? date("M d, Y H:i", strtotime($res['responded_at'])) : '-' ?></td>
                                <td>
                                    <?php if ($res['status'] === 'accepted'): ?>
                                        <a href="?request_id=<?= $request_id ?>&donate_id=<?= $res['response_id'] ?>"
                                            class="btn btn-sm btn-success px-3 py-1 rounded-pill shadow-sm"
                                            onclick="return confirm('Mark this donor as donated?');">
                                            <i class="bi bi-check-circle-fill"></i> Donate
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-muted">No responders yet.</td>
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