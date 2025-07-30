<?php
$required_role = ['college'];
include '../includes/auth.php';
include '../config/db.php';

$institution_id = $_SESSION['institution_id'];

// Stats
$total_students = $conn->query("SELECT COUNT(*) AS total FROM users WHERE institution_id=$institution_id AND role='student'")->fetch_assoc()['total'];
$total_events = $conn->query("SELECT COUNT(*) AS total FROM events WHERE institution_id=$institution_id")->fetch_assoc()['total'];
$upcoming_events = $conn->query("SELECT COUNT(*) AS total FROM events WHERE institution_id=$institution_id AND date >= CURDATE()")->fetch_assoc()['total'];

// Fetch upcoming events list (next 5)
$events = $conn->query("SELECT title, description, date, location 
                        FROM events 
                        WHERE institution_id=$institution_id AND date >= CURDATE()
                        ORDER BY date ASC 
                        LIMIT 5")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>College Dashboard</title>
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
        .events-section {
            background: linear-gradient(135deg, #dc3545, #ff6a88);
            border-radius: 15px;
            padding: 25px;
            color: white;
        }
    </style>
</head>

<body>
<?php include 'college_layout_start.php'; ?>

<div class="container py-4">

    <!-- Stats Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card shadow bg-danger">
                <i class="bi bi-people-fill fs-1"></i>
                <h3><?= $total_students ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card shadow bg-primary">
                <i class="bi bi-calendar-event fs-1"></i>
                <h3><?= $total_events ?></h3>
                <p>Events Conducted</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card shadow bg-success">
                <i class="bi bi-calendar-check fs-1"></i>
                <h3><?= $upcoming_events ?></h3>
                <p>Upcoming Events</p>
            </div>
        </div>
    </div>

    <!-- Upcoming Events -->
    <div class="card shadow-lg mb-4">
        <div class="card-body">
            <h4 class="fw-bold text-danger mb-3">
                <i class="bi bi-calendar3"></i> Upcoming Events
            </h4>
            <div class="table-responsive">
                <table class="table table-hover table-bordered text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($events) > 0): ?>
                            <?php foreach ($events as $index => $event): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><span class="fw-bold"><?= htmlspecialchars($event['title']) ?></span></td>
                                    <td><?= date("M d, Y", strtotime($event['date'])) ?></td>
                                    <td><?= htmlspecialchars($event['location']) ?></td>
                                    <td><?= htmlspecialchars($event['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-muted">No upcoming events scheduled.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php include 'college_layout_end.php'; ?>
<?php include '../includes/footer.php'; ?>
</body>
</html>
