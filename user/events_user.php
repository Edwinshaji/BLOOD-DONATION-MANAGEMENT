<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_college = $_SESSION['institution_id'] ?? null;

$search = $_GET['search'] ?? '';

// ✅ Fetch only upcoming or ongoing events
$query = "
    SELECT e.*, i.name AS organizer
    FROM events e
    JOIN institutions i ON e.institution_id = i.institution_id
    WHERE (e.status IN ('upcoming', 'ongoing'))
      AND (e.institution_id = ? 
        OR ST_Distance_Sphere(
            POINT(i.latitude, i.longitude),
            (SELECT POINT(d.latitude, d.longitude) FROM donors d WHERE d.user_id = ?)
        ) < 30000)
      AND (e.title LIKE CONCAT('%', ?, '%') OR i.name LIKE CONCAT('%', ?, '%'))
    ORDER BY e.date ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiss", $user_college, $user_id, $search, $search);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);

// ✅ Fetch participated events
$part_stmt = $conn->prepare("
    SELECT e.*, i.name AS organizer
    FROM events e
    JOIN event_participation p ON e.event_id = p.event_id
    JOIN institutions i ON e.institution_id = i.institution_id
    WHERE p.user_id = ?
    ORDER BY e.date DESC
");
$part_stmt->bind_param("i", $user_id);
$part_stmt->execute();
$part_result = $part_stmt->get_result();
$participated = $part_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events | User Dashboard</title>
    <?php include '../includes/header.php' ?>
    <style>
        .event-card {
            border-radius: 16px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            overflow: hidden;
        }

        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
        }

        .event-header {
            background: linear-gradient(135deg, #dc3545, #ff6b81);
            color: white;
            padding: 14px;
        }

        .event-body {
            padding: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .btn-view {
            padding: 8px 20px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3);
        }

        .event-location {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .countdown {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-view:hover {
            background-color: #dc3545;
            color: #fff;
        }

        .participated-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            margin-top: 50px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        }

        .participated-header {
            background: linear-gradient(135deg, #198754, #20c997);
            color: white;
            padding: 14px;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .participated-header.bg-danger {
            background: linear-gradient(135deg, #dc3545, #ff6b81);
        }
    </style>
</head>

<body>
    <?php include 'user_layout_start.php' ?>

    <div class="container my-4">

        <!-- 🔍 Search Bar -->
        <form method="GET" class="search-bar mb-5">
            <div class="input-group shadow-sm">
                <input type="text" class="form-control search-input" name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search events by title or organizer...">
                <button class="btn btn-danger btn-search px-4" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>

        <!-- ✅ Upcoming / Ongoing Events Section -->
        <div class="participated-section">
            <div class="participated-header bg-danger">
                <h3 class="mb-0"><i class="bi bi-calendar-event"></i> Upcoming & Ongoing Events</h3>
            </div>
            <div class="row justify-content-center">
                <?php if (count($events) > 0): ?>
                    <?php foreach ($events as $event): ?>
                        <?php if (in_array($event['status'], ['upcoming', 'ongoing'])): ?>
                            <?php
                            $locationParts = explode(',', $event['location']);
                            $short_location = implode(', ', array_slice($locationParts, 0, 2));
                            ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card event-card h-100 text-center">
                                    <div class="event-header bg-danger">
                                        <h5 class="mb-0 text-white"><?= htmlspecialchars($event['title']) ?></h5>
                                    </div>
                                    <div class="event-body">
                                        <p><i class="bi bi-building"></i> <strong>Organizer:</strong> <?= htmlspecialchars($event['organizer']) ?></p>
                                        <p><i class="bi bi-calendar2-week"></i> <strong>Date:</strong> <?= date("M d, Y", strtotime($event['date'])) ?></p>
                                        <p class="event-location"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($short_location) ?></p>
                                        <a href="view_event.php?id=<?= $event['event_id'] ?>"
                                            class="btn btn-outline-danger btn-view rounded-pill mt-2">
                                            <i class="bi bi-eye"></i> View Event
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center">No upcoming or ongoing events available.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ✅ Participated Events Section -->
        <div class="participated-section">
            <div class="participated-header">
                <h3 class="mb-0"><i class="bi bi-check-circle"></i> Your Participated Events</h3>
            </div>
            <div class="row justify-content-center">
                <?php if (count($participated) > 0): ?>
                    <?php foreach ($participated as $event): ?>
                        <?php
                            $locationParts = explode(',', $event['location']);
                            $short_location = implode(', ', array_slice($locationParts, 0, 2));
                            ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card event-card border-success h-100">
                                <div class="event-header bg-success">
                                    <h5 class="mb-0"><?= htmlspecialchars($event['title']) ?></h5>
                                </div>
                                <div class="event-body">
                                    <p><i class="bi bi-building"></i> <strong>Organizer:</strong> <?= htmlspecialchars($event['organizer']) ?></p>
                                    <p><i class="bi bi-calendar2-week"></i> <strong>Date:</strong> <?= date("M d, Y", strtotime($event['date'])) ?></p>
                                    <p class="event-location"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($short_location) ?></p>
                                    <a href="view_event.php?id=<?= $event['event_id'] ?>"
                                        class="btn btn-success btn-view mt-2">
                                        <i class="bi bi-eye"></i> View Event
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted px-3">You haven't participated in any events yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'user_layout_end.php' ?>
    <?php include '../includes/footer.php' ?>
</body>

</html>