<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_college = $_SESSION['institution_id'] ?? null;

$search = $_GET['search'] ?? '';

// Fetch all events from user's college and nearby institutions
$query = "
    SELECT e.*, i.name AS organizer
    FROM events e
    JOIN institutions i ON e.institution_id = i.institution_id
    WHERE (e.institution_id = ? 
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

// Fetch participated events
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        h2,
        h3 {
            font-weight: 600;
        }

        .event-card {
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transition: all 0.3s ease-in-out;
        }

        .event-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .countdown {
            font-size: 14px;
            color: #d63384;
            font-weight: 500;
        }

        .search-bar {
            max-width: 600px;
            margin: 20px auto 40px;
        }

        .search-input {
            border-radius: 30px !important;
            padding: 12px 20px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
        }

        .btn-search {
            border-radius: 30px;
        }
    </style>
</head>

<body>
    <?php include 'user_layout_start.php' ?>

    <div class="container my-4">
        <h2 class="mb-4 text-center">Find & Join Blood Donation Events</h2>

        <!-- Search Bar -->
        <form method="GET" class="search-bar">
            <div class="input-group">
                <input type="text" class="form-control search-input" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search events by title or organizer...">
                <button class="btn btn-primary btn-search px-4" type="submit">Search</button>
            </div>
        </form>

        <!-- Upcoming / Nearby Events -->
        <h4 class="mb-3">Nearby / Upcoming Events <?= $search ? "(Search: <i>" . htmlspecialchars($search) . "</i>)" : "" ?></h4>
        <div class="row">
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card event-card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                                <p class="card-text mb-1"><strong>Organizer:</strong> <?= htmlspecialchars($event['organizer']) ?></p>
                                <p class="card-text mb-1"><strong>Date:</strong> <?= date("M d, Y", strtotime($event['date'])) ?></p>
                                <p class="countdown" data-event-date="<?= $event['date'] ?>"></p>
                                <a href="view_event.php?id=<?= $event['event_id'] ?>" class="btn btn-outline-primary btn-sm mt-2">View Event</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted px-3">No events found for your search.</p>
            <?php endif; ?>
        </div>

        <hr>

        <!-- Participated Events -->
        <h3 class="mt-5">Your Participated Events</h3>
        <div class="row">
            <?php if (count($participated) > 0): ?>
                <?php foreach ($participated as $event): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card event-card border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">✅ <?= htmlspecialchars($event['title']) ?></h5>
                                <p class="card-text"><strong>Date:</strong> <?= date("M d, Y", strtotime($event['date'])) ?></p>
                                <a href="view_event.php?id=<?= $event['event_id'] ?>" class="btn btn-success btn-sm">View Event</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted px-3">You haven't participated in any events yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'user_layout_end.php' ?>
    <?php include '../includes/footer.php' ?>

    <script>
        // Countdown Timer
        const countdowns = document.querySelectorAll(".countdown");
        countdowns.forEach(c => {
            const date = new Date(c.dataset.eventDate);
            const now = new Date();
            const diff = date - now;
            if (diff > 0) {
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                c.textContent = `Starts in ${days} day${days !== 1 ? 's' : ''}`;
            } else if (diff > -86400000) {
                c.textContent = "Ongoing Today";
            } else {
                c.textContent = "Completed";
            }
        });
    </script>
</body>

</html>