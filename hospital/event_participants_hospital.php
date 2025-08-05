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

/* ✅ Handle Mark as Attended */
if (isset($_GET['attend_id'])) {
    $participation_id = intval($_GET['attend_id']);

    // Update participant as attended
    $stmt = $conn->prepare("UPDATE event_participation SET attended = 1 WHERE participation_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $participation_id, $event_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Participant marked as attended successfully!";
    } else {
        $_SESSION['error'] = "Failed to mark participant as attended.";
    }

    header("Location: event_participants_hospital.php?event_id=" . $event_id);
    exit;
}

/* ✅ Handle Mark as Donated */
if (isset($_GET['donate_id'])) {
    $participation_id = intval($_GET['donate_id']);

    // Ensure participant attended
    $stmt = $conn->prepare("SELECT attended, user_id 
                            FROM event_participation 
                            WHERE participation_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $participation_id, $event_id);
    $stmt->execute();
    $participant = $stmt->get_result()->fetch_assoc();

    if ($participant && $participant['attended'] == 1) {
        // ✅ Get donor_id from donors table
        $stmt = $conn->prepare("SELECT donor_id FROM donors WHERE user_id = ?");
        $stmt->bind_param("i", $participant['user_id']);
        $stmt->execute();
        $donor_row = $stmt->get_result()->fetch_assoc();

        if ($donor_row) {
            $donor_id = $donor_row['donor_id'];

            // ✅ Update as donated in participation
            $stmt = $conn->prepare("UPDATE event_participation SET donated = 1 WHERE participation_id = ?");
            $stmt->bind_param("i", $participation_id);
            $stmt->execute();

            // ✅ Fetch event details
            $event_query = $conn->prepare("SELECT location FROM events WHERE event_id = ?");
            $event_query->bind_param("i", $event_id);
            $event_query->execute();
            $event_data = $event_query->get_result()->fetch_assoc();

            if ($event_data) {
                $location    = $event_data['location'];
                $verified_by = $institution_id; // ✅ institution_id for foreign key

                // ✅ Insert donation record
                $stmt = $conn->prepare("
                    INSERT INTO donations (donor_id, event_id, date, location, verified_by)
                    VALUES (?, ?, NOW(), ?, ?)
                ");
                $stmt->bind_param("iisi", $donor_id, $event_id, $location, $verified_by);

                if ($stmt->execute()) {
                    // ✅ Update donor last_donated date
                    $stmt = $conn->prepare("UPDATE donors SET last_donated = CURDATE() WHERE donor_id = ?");
                    $stmt->bind_param("i", $donor_id);
                    $stmt->execute();

                    $_SESSION['success'] = "Participant marked as donated and donation recorded!";
                } else {
                    $_SESSION['error'] = "Failed to insert donation: " . $stmt->error;
                }
            }
        } else {
            $_SESSION['error'] = "User is not a registered donor.";
        }
    } else {
        $_SESSION['error'] = "Participant must be marked as attended before donation!";
    }

    header("Location: event_participants_hospital.php?event_id=" . $event_id);
    exit;
}


// Fetch participants
$stmt = $conn->prepare("SELECT u.name, u.email, u.phone, p.participation_id, p.attended, p.donated 
                        FROM event_participation p
                        JOIN users u ON p.user_id=u.user_id
                        WHERE p.event_id=?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle event status updates
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if (in_array($action, ['upcoming', 'ongoing', 'completed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE events SET status=? WHERE event_id=? AND institution_id=?");
        $stmt->bind_param("sii", $action, $event_id, $institution_id);
        $stmt->execute();
        $_SESSION['success'] = "Event status updated to " . ucfirst($action) . "!";
        header("Location: event_participants_hospital.php?event_id=" . $event_id);
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Events - Hospital Dashboard</title>
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
        <div class="event-card shadow-lg border-0 rounded-4 bg-light w-100 p-4">
            <div class="text-center">
                <h2 class="text-danger fw-bold mb-3">
                    <i class="bi bi-calendar-heart me-2"></i> <?= htmlspecialchars($event['title']) ?>
                </h2>
                <hr class="border-danger">

                <div class="row g-4 justify-content-center">
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                            <i class="bi bi-calendar-event text-danger fs-4"></i>
                            <p class="mb-1 fw-bold text-muted">Event Date</p>
                            <span class="text-dark"><?= date("M d, Y", strtotime($event['date'])) ?></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                            <i class="bi bi-geo-alt text-danger fs-4"></i>
                            <p class="mb-1 fw-bold text-muted">Location</p>
                            <span class="text-dark">
                                <?php
                                $short_location = explode(',', $event['location'])[0];
                                echo htmlspecialchars($short_location);
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                            <i class="bi bi-flag text-danger fs-4"></i>
                            <p class="mb-1 fw-bold text-muted">Status</p>
                            <?php if ($event['status'] === 'upcoming'): ?>
                                <span class="badge bg-info fs-6">Upcoming</span>
                            <?php elseif ($event['status'] === 'ongoing'): ?>
                                <span class="badge bg-warning fs-6">Ongoing</span>
                            <?php elseif ($event['status'] === 'completed'): ?>
                                <span class="badge bg-success fs-6">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-secondary fs-6">Cancelled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                        <i class="bi bi-card-text text-danger fs-4"></i>
                        <p class="mb-1 fw-bold text-muted">Description</p>
                        <p class="text-dark"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-4 d-flex flex-wrap justify-content-center gap-3">
                    <a href="?event_id=<?= $event_id ?>&action=ongoing"
                        class="btn btn-warning rounded-pill shadow-sm px-4"
                        onclick="return confirm('Mark this event as Ongoing?');">
                        <i class="bi bi-play-fill"></i> Mark Ongoing
                    </a>

                    <a href="?event_id=<?= $event_id ?>&action=completed"
                        class="btn btn-success rounded-pill shadow-sm px-4"
                        onclick="return confirm('Mark this event as Completed?');">
                        <i class="bi bi-check-circle-fill"></i> Complete Event
                    </a>

                    <a href="?event_id=<?= $event_id ?>&action=cancelled"
                        class="btn btn-danger rounded-pill shadow-sm px-4"
                        onclick="return confirm('Are you sure you want to Cancel this event?')">
                        <i class="bi bi-x-circle-fill"></i> Cancel Event
                    </a>

                    <a href="events_hospital.php"
                        class="btn btn-outline-danger rounded-pill shadow-sm px-4">
                        ← Back to Events
                    </a>
                </div>
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
                                <td>
                                    <?php if ($row['attended']): ?>
                                        <span class="badge bg-success">Attended</span>
                                    <?php else: ?>
                                        <a href="event_participants_hospital.php?event_id=<?= $event_id ?>&attend_id=<?= $row['participation_id'] ?>"
                                            class="btn btn-sm btn-primary rounded-pill shadow-sm"
                                            onclick="return confirm('Mark this participant as attended?');">
                                            Mark as Attended
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($row['donated']): ?>
                                        <span class="badge bg-danger">Donated</span>
                                    <?php elseif ($row['attended']): ?>
                                        <a href="event_participants_hospital.php?event_id=<?= $event_id ?>&donate_id=<?= $row['participation_id'] ?>"
                                            class="btn btn-sm btn-danger rounded-pill shadow-sm"
                                            onclick="return confirm('Mark this participant as donated?');">
                                            Mark as Donated
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Attend first</span>
                                    <?php endif; ?>
                                </td>

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