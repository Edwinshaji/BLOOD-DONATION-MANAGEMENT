<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$stmt = $conn->prepare("SELECT donor_id, last_donated FROM donors WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();

if ($donor) {
    $_SESSION['donor_id'] = $donor['donor_id'];
    $last_donated = $donor['last_donated'];
}

$user_id = $_SESSION['user_id'];
$donor_id = $_SESSION['donor_id'];

// Handle date update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $today = isset($_POST['donated_today']) ? date("Y-m-d") : ($_POST['last_donated'] ?? null);

    if ($today) {
        $stmt = $conn->prepare("UPDATE donors SET last_donated = ? WHERE donor_id = ?");
        $stmt->bind_param("si", $today, $donor_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Last donated date updated!";
        } else {
            $_SESSION['error'] = "Failed to update donation date.";
        }
        header("Location: donations_user.php");
        exit;
    }
}

// Get total donations
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM donations WHERE donor_id = ?");
$count_stmt->bind_param("i", $donor_id);
$count_stmt->execute();
$count_stmt->bind_result($total_donations);
$count_stmt->fetch();
$count_stmt->close();

// Get all donations
$donations = [];
$donation_stmt = $conn->prepare("SELECT donation_id, event_id, date, location, verified_by FROM donations WHERE donor_id = ? ORDER BY date DESC");
$donation_stmt->bind_param("i", $donor_id);
$donation_stmt->execute();
$result = $donation_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
}
$donation_stmt->close();

// Get user gender
$gender_stmt = $conn->prepare("SELECT gender FROM donors WHERE donor_id = ?");
$gender_stmt->bind_param("i", $donor_id);
$gender_stmt->execute();
$gender_stmt->bind_result($gender);
$gender_stmt->fetch();
$gender_stmt->close();

// Calculate next eligible donation date based on gender
if ($last_donated) {
    if (strtolower($gender) === 'female') {
        $next_eligible_date = date("Y-m-d", strtotime($last_donated . " +120 days"));
    } else {
        $next_eligible_date = date("Y-m-d", strtotime($last_donated . " +90 days"));
    }
} else {
    $next_eligible_date = "N/A";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Donations</title>
    <?php include '../includes/header.php' ?>
    <style>
        .stat-card {
            border-radius: 2.5rem;
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: scale(1.02);
        }

        .btn-export {
            float: right;
        }

        @media (max-width: 576px) {
            .btn-export {
                float: none;
                display: block;
                width: 100%;
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <?php include 'user_layout_start.php' ?>

    <div class="container py-4">

        <!-- Flash messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row text-center mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-danger text-white stat-card shadow rounded-5">
                    <div class="card-body">
                        <h5>Total Donations</h5>
                        <h4><?= $total_donations ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-info text-white stat-card shadow rounded-5">
                    <div class="card-body">
                        <h5>Last Donated</h5>
                        <h4><?= $last_donated ? date("d M Y", strtotime($last_donated)) : 'N/A' ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white stat-card shadow rounded-5">
                    <div class="card-body">
                        <h5>Next Eligible Date</h5>
                        <h4><?= $next_eligible_date !== 'N/A' ? date("d M Y", strtotime($next_eligible_date)) : 'N/A' ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donation History Table -->
        <div class="table-responsive shadow p-3 rounded-4 border">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <h5 class="mb-2">Donation History</h5>
                <button class="btn btn-outline-secondary btn-sm btn-export" disabled>
                    <i class="bi bi-download"></i> Export CSV (Coming Soon)
                </button>
            </div>
            <table class="table table-bordered table-striped table-hover align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Event ID</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Verified By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($donations) > 0): ?>
                        <?php foreach ($donations as $i => $donation): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= $donation['event_id'] ?></td>
                                <td><?= date("d M Y", strtotime($donation['date'])) ?></td>
                                <td><?= htmlspecialchars($donation['location']) ?></td>
                                <td><?= htmlspecialchars($donation['verified_by']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No donation records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Update Last Donated Date -->
        <div class="row mb-4 mt-5 justify-content-center">
            <div class="col-md-6 col-sm-10">
                <form method="POST" class="card shadow p-3 rounded-4 border">
                    <h5 class="mb-3 text-center">Update Last Donated Date</h5>
                    <div class="mb-3">
                        <label for="last_donated" class="form-label">Choose Date</label>
                        <input type="date" class="form-control" id="last_donated" name="last_donated">
                    </div>
                    <div class="d-flex justify-content-between flex-wrap">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="submit" name="donated_today" class="btn btn-success">Donated Today</button>
                    </div>
                </form>
            </div>
        </div>


    </div>

    <?php include 'user_layout_end.php' ?>
    <?php include '../includes/footer.php' ?>
</body>

</html>