<?php
$required_role = ['hospital'];
include '../includes/auth.php';
include '../config/db.php';

$institution_id = $_SESSION['institution_id'];
$today = date("Y-m-d");

// Delete Event
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id=? AND institution_id=?");
    $stmt->bind_param("ii", $delete_id, $institution_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Event deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting event.";
    }
    header("Location: events_hospital.php");
    exit;
}

// Add or Update Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $location = $_POST['location'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $event_id = $_POST['event_id'] ?? null;

    if ($event_id) {
        // Update
        $stmt = $conn->prepare("UPDATE events 
    SET title=?, description=?, date=?, location=?, latitude=?, longitude=? 
    WHERE event_id=? AND institution_id=?");
        $stmt->bind_param("ssssddii", $title, $description, $date, $location, $latitude, $longitude, $event_id, $institution_id);

        $msg = "updated";
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO events (institution_id, title, description, date, location, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssdd", $institution_id, $title, $description, $date, $location, $latitude, $longitude);
        $msg = "added";
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Event $msg successfully!";
    } else {
        $_SESSION['error'] = "Error saving event.";
    }
    header("Location: events_hospital.php");
    exit;
}

// Fetch hospital's events
$own_events = $conn->prepare("SELECT * FROM events WHERE institution_id=? ORDER BY date DESC");
$own_events->bind_param("i", $institution_id);
$own_events->execute();
$own_events_res = $own_events->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch live participants grouped by event
$live_events = $conn->prepare("SELECT e.event_id, e.title, u.name, u.email, u.phone, p.attended, p.donated
    FROM event_participation p
    JOIN users u ON p.user_id = u.user_id
    JOIN events e ON p.event_id = e.event_id
    WHERE e.date = ? AND e.institution_id = ?");
$live_events->bind_param("si", $today, $institution_id);
$live_events->execute();
$live_participants_res = $live_events->get_result()->fetch_all(MYSQLI_ASSOC);

// Group participants
$participants_by_event = [];
foreach ($live_participants_res as $row) {
    $participants_by_event[$row['event_id']]['title'] = $row['title'];
    $participants_by_event[$row['event_id']]['participants'][] = $row;
}

// Completed events (based on status column)
$completed_events = $conn->prepare("
    SELECT e.*, 
           COUNT(p.participation_id) AS total_participants, 
           SUM(p.donated) AS total_donations 
    FROM events e
    LEFT JOIN event_participation p ON e.event_id = p.event_id
    WHERE e.institution_id = ? AND e.status = 'completed'
    GROUP BY e.event_id 
    ORDER BY e.date DESC
");
$completed_events->bind_param("i", $institution_id);
$completed_events->execute();
$completed_events_res = $completed_events->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Events - Hospital Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <style>
        .card-hover {
            border-radius: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-7px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            font-size: 1.2rem;
        }

        .btn-sm {
            border-radius: 20px;
            font-weight: 500;
        }

        .btn-full {
            width: 100%;
            font-size: 1.1rem;
            padding: 12px;
        }

        .card-custom {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .card-custom:hover {
            transform: translateY(-5px);
        }

        #map {
            height: 300px;
            border-radius: 10px;
        }

        h4.section-title {
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #343a40;
        }
    </style>
</head>

<body>
    <?php include 'hospital_layout_start.php'; ?>

    <div class="container py-4">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Add Event Button -->
        <button class="btn btn-danger btn-full mb-4" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="resetForm()">
            Add New Event
        </button>

        <!-- Add/Update Event Modal -->
        <div class="modal fade" id="eventModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="eventModalLabel">Add Event</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="event_id" id="event_id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Event Title</label>
                                    <input type="text" class="form-control" name="title" id="title" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" class="form-control" name="date" id="date" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Select Event Location</label>
                                    <div id="map"></div>
                                </div>
                                <input type="hidden" id="location" name="location">
                                <input type="hidden" id="latitude" name="latitude">
                                <input type="hidden" id="longitude" name="longitude">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="save_event" class="btn btn-danger">Save Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- All Hospital Events -->
        <div class="row g-4">
            <?php foreach ($own_events_res as $event): ?>
                <div class="col-md-6 col-lg-4 text-center">
                    <div class="card shadow-lg border-0 h-100 card-hover">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-danger fw-bold">
                                <?= htmlspecialchars($event['title']) ?>
                            </h5>

                            <p class="text-muted mb-2">
                                <i class="bi bi-calendar-event"></i>
                                <?= date("M d, Y", strtotime($event['date'])) ?>
                            </p>

                            <!--  Event Status Badge -->
                            <p class="mb-2">
                                <i class="bi bi-flag text-danger"></i>
                                <?php if ($event['status'] === 'upcoming'): ?>
                                    <span class="badge bg-info">Upcoming</span>
                                <?php elseif ($event['status'] === 'ongoing'): ?>
                                    <span class="badge bg-warning text-dark">Ongoing</span>
                                <?php elseif ($event['status'] === 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Cancelled</span>
                                <?php endif; ?>
                            </p>

                            <p class="card-text flex-grow-1">
                                <?= nl2br(htmlspecialchars($event['description'])) ?>
                            </p>

                            <p class="text-muted mb-2">
                                <i class="bi bi-geo-alt"></i>
                                <?php
                                $short_location = explode(',', $event['location'])[0];
                                echo htmlspecialchars($short_location);
                                ?>
                            </p>

                            <div class="d-flex justify-content-between mt-3">
                                <button class="btn btn-outline-primary btn-sm px-3"
                                    onclick="editEvent(<?= $event['event_id'] ?>,
                                '<?= htmlspecialchars($event['title'], ENT_QUOTES) ?>',
                                '<?= $event['date'] ?>',
                                '<?= htmlspecialchars($event['description'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($event['location'], ENT_QUOTES) ?>',
                                <?= $event['latitude'] ?>,
                                <?= $event['longitude'] ?>)">
                                    <i class="bi bi-pencil-square"></i> Update
                                </button>

                                <a href="event_participants_hospital.php?event_id=<?= $event['event_id'] ?>"
                                    class="btn btn-outline-success btn-sm px-3">
                                    <i class="bi bi-people"></i> View
                                </a>

                                <a href="?delete_id=<?= $event['event_id'] ?>"
                                    class="btn btn-outline-danger btn-sm px-3"
                                    onclick="return confirm('Are you sure you want to delete this event?')">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


        <!-- Completed Events -->
        <h4 class="section-title">Completed Events</h4>
        <div class="table-responsive">
            <table class="table table-bordered text-center align-middle">
                <thead class="table-danger">
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Total Participants</th>
                        <th>Total Donations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($completed_events_res) > 0): ?>
                        <?php foreach ($completed_events_res as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['title']) ?></td>
                                <td><?= date("M d, Y", strtotime($event['date'])) ?></td>
                                <td><?= $event['total_participants'] ?></td>
                                <td><?= $event['total_donations'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-muted">No completed events yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <?php include 'hospital_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <script>
        var map = L.map('map').setView([10.0, 76.0], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var marker;
        L.Control.geocoder({
                defaultMarkGeocode: false
            })
            .on('markgeocode', function(e) {
                var center = e.geocode.center;
                if (marker) map.removeLayer(marker);
                marker = L.marker(center).addTo(map);
                map.setView(center, 14);
                document.getElementById('latitude').value = center.lat;
                document.getElementById('longitude').value = center.lng;
                document.getElementById('location').value = e.geocode.name;
            }).addTo(map);

        map.on('click', function(e) {
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
            document.getElementById('latitude').value = e.latlng.lat;
            document.getElementById('longitude').value = e.latlng.lng;

            // Reverse geocoding
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${e.latlng.lat}&lon=${e.latlng.lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        // Best: full formatted address
                        document.getElementById('location').value = data.display_name;
                    } else if (data && data.address) {
                        // Fallback: build from parts
                        let city = data.address.city || data.address.town || data.address.village || data.address.hamlet || "";
                        let district = data.address.county || data.address.state_district || "";
                        let state = data.address.state || "";
                        let fallbackAddress = [city, district, state].filter(Boolean).join(", ");

                        if (fallbackAddress) {
                            document.getElementById('location').value = fallbackAddress;
                        } else {
                            // Still nothing useful → use raw coordinates
                            document.getElementById('location').value = `Lat: ${e.latlng.lat}, Lng: ${e.latlng.lng}`;
                        }
                    } else {
                        // No response data → use raw coordinates
                        document.getElementById('location').value = `Lat: ${e.latlng.lat}, Lng: ${e.latlng.lng}`;
                    }
                })
                .catch(() => {
                    // Error in fetch → fallback to coordinates
                    document.getElementById('location').value = `Lat: ${e.latlng.lat}, Lng: ${e.latlng.lng}`;
                });
        });

        var modal = document.getElementById('eventModal');
        modal.addEventListener('shown.bs.modal', function() {
            setTimeout(function() {
                map.invalidateSize();
            }, 200);
        });

        function editEvent(id, title, date, description, location, lat, lng) {
            document.getElementById('event_id').value = id;
            document.getElementById('title').value = title;
            document.getElementById('date').value = date;
            document.getElementById('description').value = description;
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            if (marker) map.removeLayer(marker);
            marker = L.marker([lat, lng]).addTo(map);
            map.setView([lat, lng], 14);

            // 🔥 Always reverse-geocode for fresh location name
            fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.display_name) {
                        document.getElementById('location').value = data.display_name;
                    } else if (data && data.address) {
                        let city = data.address.city || data.address.town || data.address.village || data.address.hamlet || "";
                        let district = data.address.county || data.address.state_district || "";
                        let state = data.address.state || "";
                        let fallbackAddress = [city, district, state].filter(Boolean).join(", ");

                        document.getElementById('location').value = fallbackAddress || `Lat: ${lat}, Lng: ${lng}`;
                    } else {
                        document.getElementById('location').value = `Lat: ${lat}, Lng: ${lng}`;
                    }
                })
                .catch(() => {
                    document.getElementById('location').value = `Lat: ${lat}, Lng: ${lng}`;
                });

            document.getElementById('eventModalLabel').innerText = 'Update Event';
            var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
            eventModal.show();
        }

        function resetForm() {
            document.getElementById('event_id').value = '';
            document.getElementById('title').value = '';
            document.getElementById('date').value = '';
            document.getElementById('description').value = '';
            document.getElementById('location').value = '';
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            if (marker) map.removeLayer(marker);
            map.setView([10.0, 76.0], 7);
            document.getElementById('eventModalLabel').innerText = 'Add Event';
        }
    </script>


</body>

</html>