<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];
$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    header("Location: events.php");
    exit;
}

// Check if user is available
$user_check = $conn->prepare("SELECT is_available FROM donors WHERE user_id = ?");
$user_check->bind_param("i", $user_id);
$user_check->execute();
$user_result = $user_check->get_result()->fetch_assoc();
$is_available = $user_result['is_available'] ?? 0;


// Fetch event details
$stmt = $conn->prepare("
    SELECT e.*, i.name AS organizer
    FROM events e
    JOIN institutions i ON e.institution_id = i.institution_id
    WHERE e.event_id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();

if (!$event) {
    echo "<div class='alert alert-danger'>Event not found.</div>";
    exit;
}

// Check if user already registered
$check_stmt = $conn->prepare("SELECT * FROM event_participation WHERE user_id = ? AND event_id = ?");
$check_stmt->bind_param("ii", $user_id, $event_id);
$check_stmt->execute();
$already_registered = $check_stmt->get_result()->num_rows > 0;

// Handle registration only if available
if (isset($_POST['register']) && !$already_registered && $is_available) {
    $insert = $conn->prepare("INSERT INTO event_participation (event_id, user_id, attended, donated) VALUES (?, ?, 0, 0)");
    $insert->bind_param("ii", $event_id, $user_id);
    if ($insert->execute()) {
        header("Location: view_event.php?id=$event_id&registered=1");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> | Event Details</title>
    <?php include '../includes/header.php'; ?>
    <style>
        .main-card {
            max-width: 800px;
            margin: auto;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            background: #fff;
        }

        .main-header {
            background: linear-gradient(135deg, #dc3545, #ff6b81);
            color: #fff;
            text-align: center;
            padding: 30px 20px;
        }

        .main-header h2 {
            font-weight: bold;
            font-size: 1.8rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            padding: 25px;
        }

        .info-card {
            background: #fdfdfd;
            border-radius: 14px;
            padding: 18px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .info-card i {
            font-size: 1.6rem;
            color: #dc3545;
            margin-bottom: 8px;
        }

        .info-card p {
            margin: 0;
            font-size: 0.95rem;
            color: #6c757d;
        }

        .info-card span {
            display: block;
            font-weight: bold;
            font-size: 1.05rem;
            color: #212529;
        }

        .description {
            padding: 25px;
            text-align: center;
        }

        .description h5 {
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 15px;
        }

        .btn-register {
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 30px;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);
        }

        .btn-register:disabled {
            opacity: 0.7;
        }

        @media (max-width: 576px) {
            .main-header h2 {
                font-size: 1.4rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'user_layout_start.php'; ?>

    <div class="container my-4">
        <div class="main-card">
            <!-- Event Header -->
            <div class="main-header">
                <h2><?= htmlspecialchars($event['title']) ?></h2>
                <p><i class="bi bi-building"></i> Organized by <?= htmlspecialchars($event['organizer']) ?></p>
            </div>

            <!-- Event Info -->
            <div class="info-grid">
                <div class="info-card">
                    <i class="bi bi-calendar-event"></i>
                    <p>Date</p>
                    <span><?= date("M d, Y", strtotime($event['date'])) ?></span>
                </div>
                <div class="info-card">
                    <i class="bi bi-geo-alt"></i>
                    <p>Location</p>
                    <span>
                        <?php
                        $locationParts = explode(',', $event['location']);
                        echo htmlspecialchars(implode(', ', array_slice($locationParts, 0, 2)));
                        ?>
                    </span>
                </div>
                <div class="info-card">
                    <i class="bi bi-flag"></i>
                    <p>Status</p>
                    <span>
                        <?php if ($event['status'] === 'upcoming'): ?>
                            <span class="badge bg-warning text-dark">Upcoming</span>
                        <?php elseif ($event['status'] === 'ongoing'): ?>
                            <span class="badge bg-success">Ongoing</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Completed</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Description -->
            <div class="description">
                <h5><i class="bi bi-card-text"></i> About the Event</h5>
                <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
            </div>

            <!-- Action Buttons -->
            <div class="text-center pb-4">
                <form method="POST">
                    <?php if ($already_registered): ?>
                        <button class="btn btn-success btn-register" disabled>
                            <i class="bi bi-check-circle"></i> Already Registered
                        </button>
                    <?php elseif ($event['status'] === 'completed'): ?>
                        <button class="btn btn-secondary btn-register" disabled>
                            <i class="bi bi-x-circle"></i> Event Completed
                        </button>
                    <?php elseif (!$is_available): ?>
                        <button class="btn btn-secondary btn-register" disabled>
                            <i class="bi bi-person-x"></i> Not Available
                        </button>
                        <p class="text-danger mt-2">⚠ You must be available to register for events.</p>
                    <?php else: ?>
                        <button type="submit" name="register" class="btn btn-danger btn-register">
                            <i class="bi bi-pencil-square"></i> Register for Event
                        </button>
                    <?php endif; ?>

                </form>
                <a href="events_user.php" class="btn btn-outline-secondary rounded-pill px-4 mt-3">
                    ← Back to Events
                </a>
            </div>
        </div>
    </div>

    <?php include 'user_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>
</body>

</html>