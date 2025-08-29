<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];

// ---------------------- Fetch Active Requests ----------------------
$active_sql = "
    SELECT er.request_id, i.name AS institution_name, er.blood_group, er.units_needed, er.status, er.created_at,
           r.status AS response_status
    FROM emergency_requests er
    JOIN institutions i ON er.institution_id = i.institution_id
    LEFT JOIN request_responses r ON er.request_id = r.request_id AND r.user_id = ?
    WHERE er.status NOT IN ('fulfilled', 'canceled')
    ORDER BY er.created_at DESC
";
$active_stmt = $conn->prepare($active_sql);
$active_stmt->bind_param("i", $user_id);
$active_stmt->execute();
$active_requests = $active_stmt->get_result();

// ---------------------- Fetch Fulfilled Requests (User Contributions) ----------------------
$fulfilled_sql = "
    SELECT er.request_id, i.name AS institution_name, er.blood_group, 
           er.units_needed, COUNT(d.donation_id) AS units_donated, 
           MAX(d.date) AS last_donated_on
    FROM donations d
    JOIN emergency_requests er ON d.request_id = er.request_id
    JOIN institutions i ON er.institution_id = i.institution_id
    WHERE d.donor_id = ?
    GROUP BY er.request_id, i.name, er.blood_group, er.units_needed
    ORDER BY last_donated_on DESC
";
$fulfilled_stmt = $conn->prepare($fulfilled_sql);
$fulfilled_stmt->bind_param("i", $user_id);
$fulfilled_stmt->execute();
$fulfilled_requests = $fulfilled_stmt->get_result();

// ---------------- Handle Emergency Request Response ---------------- //
if (isset($_GET['request_id'], $_GET['action']) && in_array($_GET['action'], ['accept', 'reject'])) {
    $request_id = intval($_GET['request_id']);
    $status = $_GET['action'] === 'accept' ? 'accepted' : 'rejected';

    // Check if already responded
    $check = $conn->prepare("SELECT * FROM request_responses WHERE request_id=? AND user_id=?");
    $check->bind_param("ii", $request_id, $user_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();

    if ($exists) {
        $stmt = $conn->prepare("UPDATE request_responses SET status=?, responded_at=NOW() WHERE request_id=? AND user_id=?");
        $stmt->bind_param("sii", $status, $request_id, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO request_responses (request_id, user_id, status, responded_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $request_id, $user_id, $status);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "You have $status the emergency request.";
    } else {
        $_SESSION['error'] = "Error updating your response.";
    }

    header("Location: emergency_requests.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Requests</title>
    <?php include '../includes/header.php' ?>
    <style>
        .stat-card {
            border-radius: 2.5rem;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: scale(1.02);
        }

        table td,
        table th {
            vertical-align: middle !important;
        }
    </style>
</head>

<body>
    <?php include 'user_layout_start.php' ?>

    <div class="container py-4">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <h3 class="mb-4 text-center">Emergency Requests</h3>

        <!-- Active Requests -->
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-dark fw-bold">Active Requests</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Institution</th>
                            <th>Blood Group</th>
                            <th>Units Needed</th>
                            <th>Requested On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($active_requests->num_rows > 0): ?>
                            <?php while ($row = $active_requests->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['institution_name']) ?></td>
                                    <td><?= htmlspecialchars($row['blood_group']) ?></td>
                                    <td><?= htmlspecialchars($row['units_needed']) ?></td>
                                    <td><?= date("d M Y", strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex justify-content-center mt-2">
                                            <?php if ($row['response_status'] === 'accepted'): ?>
                                                <a href="?action=reject&request_id=<?= $row['request_id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                                            <?php elseif ($row['response_status'] === 'rejected'): ?>
                                                <a href="?action=accept&request_id=<?= $row['request_id'] ?>" class="btn btn-success btn-sm">Accept</a>
                                            <?php else: ?>
                                                <a href="?action=accept&request_id=<?= $row['request_id'] ?>" class="btn btn-success btn-sm me-1">Accept</a>
                                                <a href="?action=reject&request_id=<?= $row['request_id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No active requests</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fulfilled Requests (User Contributions) -->
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white fw-bold">Fulfilled Requests (Your Contributions)</div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-hover text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Institution</th>
                            <th>Blood Group</th>
                            <th>Units Donated</th>
                            <th>Units Needed</th>
                            <th>Last Donation Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($fulfilled_requests->num_rows > 0): ?>
                            <?php while ($row = $fulfilled_requests->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['institution_name']) ?></td>
                                    <td><?= htmlspecialchars($row['blood_group']) ?></td>
                                    <td><?= htmlspecialchars($row['units_donated']) ?></td>
                                    <td><?= htmlspecialchars($row['units_needed']) ?></td>
                                    <td><?= $row['last_donated_on'] ? date("d M Y", strtotime($row['last_donated_on'])) : '-' ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No fulfilled requests yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <?php include 'user_layout_end.php' ?>
    <?php include '../includes/footer.php' ?>
</body>

</html>