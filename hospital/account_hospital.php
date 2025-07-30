<?php
$required_role = "hospital";
include '../includes/auth.php';
include '../config/db.php';

$hospital_id = $_SESSION['institution_id']; // stored during login
// Fetch hospital data
$stmt = $conn->prepare("SELECT * FROM institutions WHERE institution_id = ?");
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();

// Handle Edit Profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_name = trim($_POST['name']);
        $new_address = trim($_POST['address']);

        $stmt = $conn->prepare("UPDATE institutions SET name = ?, address = ? WHERE institution_id = ?");
        $stmt->bind_param("ssi", $new_name, $new_address, $hospital_id);
        $stmt->execute();
        $_SESSION['hospital_name'] = $new_name;
        header("Location: account_hospital.php");
        exit;
    }

    // Handle Change Password
    if (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];

        $stmt = $conn->prepare("SELECT password FROM institutions WHERE institution_id = ?");
        $stmt->bind_param("i", $hospital_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (password_verify($old_pass, $row['password'])) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE institutions SET password = ? WHERE institution_id = ?");
            $stmt->bind_param("si", $hashed, $hospital_id);
            $stmt->execute();
            echo '<script>alert("Password changed successfully!")</script>';
            $password_message = "Password changed successfully!";
        } else {
            echo '<script>alert("Old password is incorrect.")</script>';
            $password_error = "Old password is incorrect.";
        }
    }

    // Handle Location Update
    if (isset($_POST['update_location'])) {
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        $stmt = $conn->prepare("UPDATE institutions SET latitude = ?, longitude = ? WHERE institution_id = ?");
        $stmt->bind_param("ddi", $latitude, $longitude, $hospital_id);
        if ($stmt->execute()) {
            echo '<script>alert("Location updated successfully!")</script>';
            $location_message = "Location updated successfully!";
        } else {
            echo '<script>alert("Failed to update location.")</script>';
            $location_error = "Failed to update location.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Account - Hospital Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <style>
        .profile-card {
            max-width: 650px;
            margin: 3rem auto;
            background: #fff;
            border-radius: 1.2rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            padding: 3rem;
            text-align: center;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            background: #e57373;
            color: #fff;
            border-radius: 50%;
            font-size: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .profile-label {
            color: #e57373;
            font-weight: 600;
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        #map {
            height: 350px;
        }
    </style>
</head>

<body>
    <?php include 'hospital_layout_start.php'; ?>

    <div class="profile-card text-center">
        <div class="profile-avatar mb-2">
            <i class="bi bi-hospital"></i>
        </div>
        <h4 class="mb-1"><?= htmlspecialchars($hospital['name']) ?></h4>
        <div class="mb-3 text-muted">Hospital Account</div>
        <div class="mb-2"><span class="profile-label">Email:</span> <?= htmlspecialchars($hospital['email']) ?></div>
        <div class="mb-2"><span class="profile-label">Address:</span> <?= htmlspecialchars($hospital['address']) ?></div>
        <div class="btn-group mt-3">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">Edit Profile</button>
            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#passwordModal">Change Password</button>
            <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#locationModal">Update Location</button>
            <a href="../logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Hospital Name</label>
                        <input name="name" type="text" class="form-control" value="<?= htmlspecialchars($hospital['name']) ?>" required>
                    </div>
                    <div class="mb-3"><label class="form-label">Address</label>
                        <input name="address" type="text" class="form-control" value="<?= htmlspecialchars($hospital['address']) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="update_profile" type="submit" class="btn btn-success">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($password_error)): ?><div class="alert alert-danger"><?= $password_error ?></div><?php endif; ?>
                    <?php if (isset($password_message)): ?><div class="alert alert-success"><?= $password_message ?></div><?php endif; ?>
                    <div class="mb-3"><label class="form-label">Old Password</label>
                        <input name="old_password" type="password" class="form-control" required>
                    </div>
                    <div class="mb-3"><label class="form-label">New Password</label>
                        <input name="new_password" type="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="change_password" type="submit" class="btn btn-warning">Change Password</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Location Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Update Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($location_error)): ?><div class="alert alert-danger"><?= $location_error ?></div><?php endif; ?>
                    <?php if (isset($location_message)): ?><div class="alert alert-success"><?= $location_message ?></div><?php endif; ?>
                    <div id="map"></div>
                    <input type="hidden" name="latitude" id="latitude" value="<?= $hospital['latitude'] ?>">
                    <input type="hidden" name="longitude" id="longitude" value="<?= $hospital['longitude'] ?>">
                </div>
                <div class="modal-footer">
                    <button name="update_location" type="submit" class="btn btn-success">Save Location</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'hospital_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <!-- Leaflet Map with Search -->
    <script>
        let map = L.map('map').setView([<?= $hospital['latitude'] ?? 10.0 ?>, <?= $hospital['longitude'] ?? 76.0 ?>], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker = L.marker([<?= $hospital['latitude'] ?? 10.0 ?>, <?= $hospital['longitude'] ?? 76.0 ?>], {
            draggable: true
        }).addTo(map);

        function updateLatLng(e) {
            const latlng = e.latlng || marker.getLatLng();
            document.getElementById('latitude').value = latlng.lat;
            document.getElementById('longitude').value = latlng.lng;
        }
        map.on('click', e => {
            marker.setLatLng(e.latlng);
            updateLatLng(e);
        });
        marker.on('dragend', updateLatLng);

        L.Control.geocoder({
            defaultMarkGeocode: false
        }).on('markgeocode', function(e) {
            const latlng = e.geocode.center;
            map.setView(latlng, 15);
            marker.setLatLng(latlng);
            document.getElementById('latitude').value = latlng.lat;
            document.getElementById('longitude').value = latlng.lng;
        }).addTo(map);

        document.getElementById('locationModal').addEventListener('shown.bs.modal', () => {
            map.invalidateSize();
        });
    </script>
</body>

</html>