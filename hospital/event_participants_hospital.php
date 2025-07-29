<?php
$required_role = ['hospital'];
include '../includes/auth.php';
include '../config/db.php';

if (!isset($_GET['event_id'])) {
    header("Location: events_hospital.php");
    exit;
}

$event_id = intval($_GET['event_id']);
$institution_id = $_SESSION['institution_id'];

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM events WHERE event_id=? AND institution_id=?");
$stmt->bind_param("ii", $event_id, $institution_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    $_SESSION['error'] = "Event not found or unauthorized access.";
    header("Location: events_hospital.php");
    exit;
}

// Fetch participants
$stmt = $conn->prepare("SELECT u.name, u.email, u.phone, p.attended, p.donated 
                        FROM event_participation p
                        JOIN users u ON p.user_id=u.user_id
                        WHERE p.event_id=?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Event Participants</title>
    <?php include '../includes/header.php'; ?>
    <style>
        .event-card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'hospital_layout_start.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-center">
            <div class="event-card bg-white shadow-lg p-4 text-center" style="max-width: 650px; width: 100%;">
                <h3 class="text-danger fw-bold mb-3">
                    <i class="bi bi-calendar-heart"></i> <?= htmlspecialchars($event['title']) ?>
                </h3>
                <hr>
                <p class="mb-2">
                    <i class="bi bi-calendar-event text-danger"></i>
                    <strong>Date:</strong> <?= date("M d, Y", strtotime($event['date'])) ?>
                </p>
                <p class="mb-2">
                    <i class="bi bi-geo-alt text-danger"></i>
                    <strong>Location:</strong>
                    <?php
                    $short_location = explode(',', $event['location'])[0];
                    echo htmlspecialchars($short_location);
                    ?>
                </p>
                <p class="mb-3">
                    <i class="bi bi-card-text text-danger"></i>
                    <?= nl2br(htmlspecialchars($event['description'])) ?>
                </p>
                <a href="events_hospital.php" class="btn btn-outline-danger rounded-pill px-4">
                    ← Back to Events
                </a>
            </div>
        </div>


        <h4 class="mt-4">Participants</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Attended</th>
                        <th>Donated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($participants) > 0): ?>
                        <?php foreach ($participants as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                <td><?= $row['attended'] ? 'Yes' : 'No' ?></td>
                                <td><?= $row['donated'] ? 'Yes' : 'No' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-muted">No participants yet.</td>
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