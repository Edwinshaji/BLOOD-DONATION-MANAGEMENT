<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];

// Get upcoming events (latest 3)
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

// Donor search logic (fallback to wider range if none nearby)
$donors = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $blood_group = $_GET['blood_group'];
    $latitude = $_GET['latitude'] ?? null;
    $longitude = $_GET['longitude'] ?? null;

    if ($latitude && $longitude) {
        // Nearby search (30 km)
        $query = "
            SELECT u.name, u.phone, d.blood_group, i.address AS location,
                   ST_Distance_Sphere(POINT(i.latitude, i.longitude), POINT(?, ?)) AS distance
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

        // If no donors nearby, fetch any available
        if (count($donors) === 0) {
            $fallback_query = "
                SELECT u.name, u.phone, d.blood_group, i.address AS location
                FROM donors d
                JOIN users u ON d.user_id = u.user_id
                JOIN institutions i ON u.institution_id = i.institution_id
                WHERE d.is_available = 1
                  AND d.blood_group = ?
                LIMIT 10
            ";
            $fallback_stmt = $conn->prepare($fallback_query);
            $fallback_stmt->bind_param("s", $blood_group);
            $fallback_stmt->execute();
            $donors = $fallback_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Dashboard - Home</title>
    <?php include '../includes/header.php'; ?>
    <style>
        .donor-card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform .2s;
        }

        .donor-card:hover {
            transform: scale(1.02);
        }

        .event-card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .donor-results {
            margin-top: 20px;
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
        <?php endif; ?>

        <!-- Donor Search -->
        <div class="card p-4 shadow mb-5">
            <h3 class="mb-4 text-danger text-center">Find Nearby Donors</h3>
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
                    <button type="submit" name="search" class="btn btn-danger btn-lg w-100">Search</button>
                </div>
            </form>

            <!-- Donor Results -->
            <?php if (isset($_GET['search'])): ?>
                <div class="donor-results mt-4">
                    <h5 class="mb-3 text-center">Search Results</h5>
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
                        <p class="text-center text-muted">No donors found.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Upcoming Events -->
        <h3 class="text-center text-danger mb-4">Latest Upcoming Events</h3>
        <div class="row g-4">
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card event-card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                                <p><strong>Organizer:</strong> <?= htmlspecialchars($event['organizer']) ?></p>
                                <p><strong>Date:</strong> <?= date("M d, Y", strtotime($event['date'])) ?></p>
                                <a href="view_event.php?id=<?= $event['event_id'] ?>" class="btn btn-outline-primary btn-sm">View Event</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-muted">No upcoming events available.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'user_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script>
        // Auto-fill location using browser geolocation
        function setLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                    document.forms[0].submit();
                }, function() {
                    alert("Location access denied. Please allow location to find nearby donors.");
                });
                return false; // prevent immediate submit, wait for coords
            } else {
                alert("Geolocation is not supported by your browser.");
                return false;
            }
        }
    </script>

</body>

</html>