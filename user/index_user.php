<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];

// Check if user has become a donor
$donor_check_stmt = $conn->prepare("SELECT donor_id FROM donors WHERE user_id = ?");
$donor_check_stmt->bind_param("i", $user_id);
$donor_check_stmt->execute();
$donor_check_result = $donor_check_stmt->get_result();
$is_donor = $donor_check_result->num_rows > 0; // TRUE if user is a donor, FALSE otherwise
$donor_check_stmt->close();

// Check user availability
$check_user = $conn->prepare("SELECT is_available FROM donors WHERE user_id=?");
$check_user->bind_param("i", $user_id);
$check_user->execute();
$user_availability = $check_user->get_result()->fetch_assoc();
$is_available = $user_availability['is_available'] ?? 0;


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

    header("Location: index_user.php");
    exit;
}

// ---------------- Emergency Requests ---------------- //
$notifications = [];
$notif_query = "
    SELECT r.request_id, r.blood_group, r.message, r.created_at, r.units_needed, r.units_donated,
           i.name AS hospital_name, i.address
    FROM emergency_requests r
    JOIN institutions i ON r.institution_id = i.institution_id
    WHERE r.status = 'pending'
      AND EXISTS (
          SELECT 1 
          FROM donors d
          WHERE d.user_id = ?
            AND d.blood_group = r.blood_group
      )
      AND r.created_at >= DATE_SUB(NOW(), INTERVAL 5 DAY)   --  Only last 5 days
    ORDER BY r.created_at DESC
    LIMIT 5
";

$stmt = $conn->prepare($notif_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---------------- User Stats ---------------- //
$total_donations = 0;
$days_remaining = "N/A";
$next_possible_date = "N/A";
$donor_status = "Not Registered";

// Total donations
$stmt = $conn->prepare("SELECT COUNT(*) FROM donations d JOIN donors dn ON d.donor_id = dn.donor_id WHERE dn.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($total_donations);
$stmt->fetch();
$stmt->close();

// Last donated
$stmt = $conn->prepare("SELECT last_donated FROM donors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($last_donated);

if ($stmt->fetch() && $last_donated) {
    $next_date = date('Y-m-d', strtotime($last_donated . ' +90 days'));
    $today = date('Y-m-d');

    // Difference in days (integer only)
    $diff = floor((strtotime($next_date) - strtotime($today)) / (60 * 60 * 24));

    $days_remaining = $diff > 0 ? $diff . " days" : "Eligible Now";
    $next_possible_date = $next_date;
    $donor_status = "Active Donor";
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Home</title>
    <?php include '../includes/header.php'; ?>
    <style>
        body {
            background: #f9f9fb;
        }

        .section-title {
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .notif-card {
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            transition: transform .2s;
        }

        .notif-card:hover {
            transform: scale(1.02);
        }

        .stats-card {
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            color: #fff;
        }

        /* Search bar */
        .search-container {
            max-width: 100%;
            margin: 20px auto;
            position: relative;
        }

        .search-input {
            border-radius: 30px;
            padding-left: 20px;
            font-size: 1rem;
        }

        .dropdown-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
            max-height: 250px;
            overflow-y: auto;
        }

        .dropdown-results table {
            margin: 0;
            font-size: 0.9rem;
        }

        .dropdown-results td,
        .dropdown-results th {
            padding: 8px;
        }

        /* Emergency Request Cards */
        .emergency-card {
            border-radius: 16px;
            border: 2px solid #dc3545;
            background: #fff;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease-in-out;
            position: relative;
            text-align: center;
            max-width: 300px;
        }

        .emergency-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.12);
        }

        .emergency-card h5 {
            font-weight: 700;
            margin-bottom: 12px;
            color: #dc3545;
        }

        .emergency-info p {
            margin-bottom: 6px;
            font-size: 0.95rem;
        }

        .emergency-info i {
            color: #dc3545;
            margin-right: 6px;
        }

        .status-ribbon {
            position: absolute;
            top: 15px;
            right: 15px;
        }

        /* Donor alert box styling */
        .donor-alert {
            background: linear-gradient(90deg, #ff4b5c, #ff6b81);
            color: #fff;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            animation: pulse 2s infinite;
        }

        /* Button styling */
        .donor-alert .btn {
            border-radius: 50px;
            font-weight: 600;
            padding: 10px 25px;
            transition: transform 0.2s;
        }

        .donor-alert .btn:hover {
            transform: scale(1.05);
        }

        /* Subtle pulse effect */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 10px rgba(255, 75, 92, 0.4);
            }

            50% {
                box-shadow: 0 0 25px rgba(255, 75, 92, 0.6);
            }

            100% {
                box-shadow: 0 0 10px rgba(255, 75, 92, 0.4);
            }
        }

        /* Marquee styling */
        .donor-alert marquee {
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <?php include 'user_layout_start.php'; ?>
    <div class="container my-4">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success flash-msg">
                <?= $_SESSION['success']; ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger flash-msg">
                <?= $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!--  Search Bar -->
        <div class="search-container">
            <input type="text" id="searchDonor" class="form-control search-input" placeholder="Search donors by blood group...">
            <div id="searchResults" class="dropdown-results d-none"></div>
        </div>

        <!--  Emergency Requests -->
        <div class="card shadow-sm my-5 text-center">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Emergency Requests</h5>
            </div>
            <div class="card-body">
                <?php if (count($notifications) > 0): ?>
                    <div class="row g-4 justify-content-center">
                        <?php foreach ($notifications as $notif): ?>
                            <?php
                            $resp_stmt = $conn->prepare("SELECT status FROM request_responses WHERE request_id=? AND user_id=?");
                            $resp_stmt->bind_param("ii", $notif['request_id'], $user_id);
                            $resp_stmt->execute();
                            $response = $resp_stmt->get_result()->fetch_assoc();
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="emergency-card">
                                    <!-- Status Ribbon -->
                                    <div class="status-ribbon">
                                        <?php if ($response): ?>
                                            <?php if ($response['status'] === 'accepted'): ?>
                                                <span class="badge bg-success">Accepted</span>
                                            <?php elseif ($response['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger">Rejected</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </div>

                                    <h5><i class="bi bi-hospital"></i> <?= htmlspecialchars($notif['hospital_name']) ?></h5>
                                    <div class="emergency-info">
                                        <p><i class="bi bi-droplet-fill"></i><strong>Blood Group:</strong> <?= $notif['blood_group'] ?></p>
                                        <p><i class="bi bi-people-fill"></i><strong>Units:</strong> <?= $notif['units_donated'] ?>/<?= $notif['units_needed'] ?></p>
                                        <p><i class="bi bi-chat-left-text"></i><strong>Message:</strong> <?= htmlspecialchars($notif['message']) ?></p>
                                        <p class="small text-muted"><i class="bi bi-clock-history"></i> <?= date("M d, Y H:i", strtotime($notif['created_at'])) ?></p>
                                    </div>

                                    <div class="d-flex justify-content-between mt-3">
                                        <?php if (!$is_available): ?>
                                            <button class="btn btn-secondary btn-sm w-100" disabled>
                                                <i class="bi bi-person-x"></i> Not Available
                                            </button>
                                        <?php else: ?>
                                            <?php if ($response): ?>
                                                <?php if ($response['status'] === 'accepted'): ?>
                                                    <a href="?action=reject&request_id=<?= $notif['request_id'] ?>" class="btn btn-danger btn-sm w-100">Reject</a>
                                                <?php elseif ($response['status'] === 'rejected'): ?>
                                                    <a href="?action=accept&request_id=<?= $notif['request_id'] ?>" class="btn btn-success btn-sm w-100">Accept</a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="?action=accept&request_id=<?= $notif['request_id'] ?>" class="btn btn-success btn-sm">Accept</a>
                                                <a href="?action=reject&request_id=<?= $notif['request_id'] ?>" class="btn btn-danger btn-sm">Reject</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <p class="text-center text-muted">No emergency requests available.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$is_donor): ?>
            <div class="donor-alert my-3 p-3 rounded text-center">
                <marquee behavior="scroll" direction="left" scrollamount="6">
                    <strong>Become a lifesaver! Register as a blood donor today and help save lives.</strong>
                </marquee>
                <a href="account_user.php" class="btn btn-danger mt-2 btn-lg">Become a Donor</a>
            </div>
        <?php endif; ?>

        <!--  User Stats -->
        <div class="row g-3">
            <div class="col-md-4 col-12">
                <div class="stats-card bg-danger">
                    <h4>Total Donations</h4>
                    <hr>
                    <h5><?= $total_donations ?></h5>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="stats-card bg-danger">
                    <h4>Next Eligible</h4>
                    <hr>
                    <h5><?= $days_remaining ?></h5>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="stats-card bg-danger">
                    <h4>Donor Status</h4>
                    <hr>
                    <h5><?= $donor_status ?></h5>
                </div>
            </div>
        </div>

    </div>
    <?php include 'user_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#searchDonor").on("input", function() {
                let query = $(this).val().trim();
                if (query.length === 0) {
                    $("#searchResults").addClass("d-none").empty();
                    return;
                }
                $.ajax({
                    url: "search_donor.php",
                    type: "POST",
                    data: {
                        blood_group: query
                    },
                    success: function(data) {
                        let results = JSON.parse(data);
                        let table = "";
                        if (results.length > 0) {
                            table = `<table class="table table-sm mb-0 table-hover">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Blood Group</th>
                <th>Mobile</th>
              </tr>
            </thead><tbody>`;
                            results.forEach(donor => {
                                table += `<tr style="cursor:pointer" onclick="window.location.href='donor_details.php?id=${donor.user_id}'">
                <td>${donor.name}</td>
                <td>${donor.blood_group}</td>
                <td>${donor.phone}</td>
              </tr>`;
                            });
                            table += "</tbody></table>";
                        } else {
                            table = `<div class="p-2 text-muted">No donors found</div>`;
                        }
                        $("#searchResults").removeClass("d-none").html(table);
                    }

                });
            });
        });
    </script>

</body>

</html>