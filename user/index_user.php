<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];

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

    header("Location: index_user.php"); // redirect to avoid form resubmission
    exit;
}

// ---------------- Donor Search ---------------- //
$donors = [];
$search_mode = false;

if (isset($_GET['search']) && !empty($_GET['blood_group'])) {
    $search_mode = true;
    $blood_group = $_GET['blood_group'];
    $latitude = $_GET['latitude'] ?? null;
    $longitude = $_GET['longitude'] ?? null;

    if (!empty($latitude) && !empty($longitude)) {
        $query = "
            SELECT u.user_id, u.name, u.phone, d.blood_group, i.address AS location,
                   ST_Distance_Sphere(POINT(i.longitude, i.latitude), POINT(?, ?)) AS distance
            FROM donors d
            JOIN users u ON d.user_id = u.user_id
            JOIN institutions i ON u.institution_id = i.institution_id
            WHERE d.is_available = 1
              AND d.blood_group = ?
            HAVING distance < 30000
            ORDER BY distance ASC
            LIMIT 10
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("dds", $longitude, $latitude, $blood_group);
        $stmt->execute();
        $donors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    if (count($donors) === 0) {
        $fallback_query = "
            SELECT u.user_id, u.name, u.phone, d.blood_group, i.address AS location
            FROM donors d
            JOIN users u ON d.user_id = u.user_id
            JOIN institutions i ON u.institution_id = i.institution_id
            WHERE d.is_available = 1
              AND d.blood_group = ?
            LIMIT 10
        ";
        $stmt = $conn->prepare($fallback_query);
        $stmt->bind_param("s", $blood_group);
        $stmt->execute();
        $donors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// ---------------- Emergency Requests ---------------- //
$notifications = [];
$notif_query = "
    SELECT r.request_id, r.blood_group, r.message, r.created_at, 
           i.name AS hospital_name, i.address
    FROM emergency_requests r
    JOIN institutions i ON r.institution_id = i.institution_id
    WHERE r.status = 'pending'
      AND r.created_at >= NOW() - INTERVAL 1 DAY
      AND EXISTS (
          SELECT 1 
          FROM donors d
          WHERE d.user_id = ?
            AND d.blood_group = r.blood_group
            AND d.is_available = 1
      )
    ORDER BY r.created_at DESC
    LIMIT 5
";
$stmt = $conn->prepare($notif_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---------------- Upcoming Events ---------------- //
$today = date("Y-m-d");
$events_stmt = $conn->prepare("
    SELECT e.*, i.name AS organizer 
    FROM events e 
    JOIN institutions i ON e.institution_id = i.institution_id
    WHERE e.date >= ? 
    ORDER BY e.date ASC 
    LIMIT 3
");
$events_stmt->bind_param("s", $today);
$events_stmt->execute();
$events = $events_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

        .search-bar {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .dashboard-card {
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
            background: #fff;
            padding: 20px;
        }

        .donor-card,
        .event-card,
        .notif-card {
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            transition: transform .2s;
        }

        .donor-card:hover,
        .event-card:hover,
        .notif-card:hover {
            transform: scale(1.03);
        }

        .section-title {
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>

<body>
    <?php include 'user_layout_start.php'; ?>
    <div class="container my-4">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- 🔍 Search Bar -->
        <div class="search-bar">
            <form method="GET" class="row g-3" onsubmit="return setLocation();">
                <div class="col-md-6 mx-auto">
                    <select name="blood_group" class="form-select form-select-lg" required>
                        <option value="">Select Blood Group</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($_GET['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                </div>
                <div class="col-md-4 mx-auto">
                    <button type="submit" name="search" value="1" class="btn btn-danger btn-lg w-100">Search Donors</button>
                </div>
            </form>
        </div>

        <!-- Donor Search Results -->
        <?php if ($search_mode): ?>
            <h3 class="section-title"><i class="bi bi-people-fill"></i> Donor Results</h3>
            <?php if (count($donors) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($donors as $donor): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card donor-card p-3">
                                <h5><?= htmlspecialchars($donor['name']) ?></h5>
                                <p><strong>Blood Group:</strong> <?= $donor['blood_group'] ?></p>
                                <p><strong>Location:</strong> <?= htmlspecialchars($donor['location']) ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="tel:<?= $donor['phone'] ?>" class="btn btn-primary btn-sm">Call</a>
                                    <a href="https://wa.me/91<?= $donor['phone'] ?>" target="_blank" class="btn btn-success btn-sm">WhatsApp</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No donors found for <?= htmlspecialchars($_GET['blood_group']) ?>.</p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- 🚨 Emergency Requests -->
        <?php if (count($notifications) > 0): ?>
            <div class="dashboard-card my-5 border-danger border-2">
                <h3 class="section-title"><i class="bi bi-bell-fill"></i> Emergency Requests</h3>
                <div class="row g-4">
                    <?php foreach ($notifications as $notif): ?>
                        <?php
                        // Check if user already responded
                        $resp_stmt = $conn->prepare("SELECT status FROM request_responses WHERE request_id=? AND user_id=?");
                        $resp_stmt->bind_param("ii", $notif['request_id'], $user_id);
                        $resp_stmt->execute();
                        $response = $resp_stmt->get_result()->fetch_assoc();
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card notif-card p-3 border-danger">
                                <h5 class="text-danger"><i class="bi bi-hospital"></i> <?= htmlspecialchars($notif['hospital_name']) ?></h5>
                                <p><strong>Blood Group:</strong> <?= $notif['blood_group'] ?></p>
                                <p><strong>Message:</strong> <?= htmlspecialchars($notif['message']) ?></p>
                                <p class="small text-muted"><i class="bi bi-clock"></i> <?= date("M d, Y H:i", strtotime($notif['created_at'])) ?></p>
                                <div class="d-flex justify-content-between">
                                    <?php if ($response): ?>
                                        <?php if ($response['status'] === 'accepted'): ?>
                                            <span class="badge bg-success">You have accepted this request</span>
                                            <a href="?action=reject&request_id=<?= $notif['request_id'] ?>"
                                                class="btn btn-outline-danger btn-sm">Reject</a>
                                        <?php elseif ($response['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger">You have rejected this request</span>
                                            <a href="?action=accept&request_id=<?= $notif['request_id'] ?>"
                                                class="btn btn-success btn-sm">Accept</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="?action=accept&request_id=<?= $notif['request_id'] ?>" class="btn btn-success btn-sm">Accept</a>
                                        <a href="?action=reject&request_id=<?= $notif['request_id'] ?>" class="btn btn-outline-danger btn-sm">Reject</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 📅 Upcoming Events -->
        <div class="dashboard-card mt-4">
            <h3 class="section-title"><i class="bi bi-calendar-event"></i> Latest Upcoming Events</h3>
            <div class="row g-4">
                <?php if (count($events) > 0): ?>
                    <?php foreach ($events as $event): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card event-card p-3">
                                <h5><?= htmlspecialchars($event['title']) ?></h5>
                                <p><strong>Organizer:</strong> <?= htmlspecialchars($event['organizer']) ?></p>
                                <p><strong>Date:</strong> <?= date("M d, Y", strtotime($event['date'])) ?></p>
                                <a href="view_event.php?id=<?= $event['event_id'] ?>" class="btn btn-outline-danger btn-sm">View Event</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No upcoming events available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'user_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script>
        let submitting = false;

        function setLocation() {
            if (submitting) return true;
            if (navigator.geolocation) {
                submitting = true;
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    document.forms[0].submit();
                }, function() {
                    document.forms[0].submit(); // fallback
                });
                return false;
            }
            return true;
        }
    </script>
</body>

</html>