<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? "User";
$user_email = $_SESSION['user_email'] ?? "user@example.com";
$user_role = $_SESSION['role'] ?? "user";

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$is_donor = false;
$donor_data = null;
$check_donor = $conn->prepare("SELECT * FROM donors WHERE user_id = ?");
$check_donor->bind_param("i", $user_id);
$check_donor->execute();
$donor_result = $check_donor->get_result();
if ($donor_result->num_rows > 0) {
    $is_donor = true;
    $donor_data = $donor_result->fetch_assoc();
}


// Fetch institutions
$colleges = [];
$stmt = $conn->prepare("SELECT institution_id, name FROM institutions WHERE status = 'approved'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $colleges[] = $row;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_name = trim($_POST['name']);
        $new_phone = trim($_POST['phone_number']);
        $new_role = trim($_POST['role']);
        $new_institution = ($new_role === 'student') ? intval($_POST['institution']) : null;

        $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, role = ?, institution_id = ? WHERE user_id = ?");
        $stmt->bind_param("sssii", $new_name, $new_phone, $new_role, $new_institution, $user_id);

        if ($stmt->execute()) {
            $_SESSION['user_name'] = $new_name;
            $_SESSION['user_phone'] = $new_phone;
            $_SESSION['role'] = $new_role;
            header("Location: account_user.php");
            exit;
        } else {
            $update_error = "Failed to update profile.";
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (password_verify($old_pass, $row['password'])) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $password_message = "Password changed successfully!";
            echo "<script>alert($password_message)</script>";
        } else {
            $password_error = "Old password is incorrect.";
            echo "<script>alert($password_error)</script>";
        }
    }
    if (isset($_POST['update_location']) && $is_donor) {
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];

        $stmt = $conn->prepare("UPDATE donors SET latitude = ?, longitude = ? WHERE user_id = ?");
        $stmt->bind_param("ddi", $latitude, $longitude, $user_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Location updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update location.";
        }

        header("Location: account_user.php");
        exit;
    }
}
?>
<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $gender = $_POST['gender'];
    $blood_group = $_POST['blood_group'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $agree = isset($_POST['agree']) ? 1 : 0;

    if (!$agree) {
        $_SESSION['error'] = "You must agree to donate blood.";
        header("Location: account.php");
        exit;
    }

    // Check if donor already exists
    $check = $conn->prepare("SELECT donor_id FROM donors WHERE user_id = ?");
    $check->bind_param("i", $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Update existing donor
        $stmt = $conn->prepare("UPDATE donors SET gender = ?, blood_group = ?, is_available = 1, is_confirmed = 0, latitude = ?, longitude = ? WHERE user_id = ?");
        $stmt->bind_param("ssddi", $gender, $blood_group, $latitude, $longitude, $user_id);
    } else {
        // Insert new donor
        $stmt = $conn->prepare("INSERT INTO donors (user_id, gender, blood_group, is_available, is_confirmed, latitude, longitude) VALUES (?, ?, ?, 1, 0, ?, ?)");
        $stmt->bind_param("sssdd", $user_id, $gender,  $blood_group, $latitude, $longitude);
    }

    if ($stmt->execute()) {

        $_SESSION['success'] = "Your willingness has been submitted!";
    } else {
        $_SESSION['error'] = "Something went wrong. Please try again.";
    }

    header("Location: account_user.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Leaflet Geocoder Plugin -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <style>
        body {
            background: #f0f2f5;
        }

        .profile-card {
            max-width: 500px;
            margin: 2rem auto;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.07);
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s ease-in-out;
        }

        .profile-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(0, 0, 0, 0.12);
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: white;
            border-radius: 50%;
            font-size: 2.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.3);
        }

        .profile-card h4 {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }

        .profile-card p {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .profile-card strong {
            color: #212529;
        }

        .btn-group {
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-outline-primary,
        .btn-outline-warning,
        .btn-outline-danger {
            min-width: 120px;
        }
    </style>

</head>

<body>
    <?php include 'user_layout_start.php'; ?>

    <div class="profile-card text-center">
        <div class="profile-avatar"><i class="bi bi-person"></i></div>
        <h4 class="mb-1"><?= htmlspecialchars($user['name']) ?></h4>
        <p class="text-muted mb-2"><?= htmlspecialchars($user['role']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
        <p><strong>Institution:</strong> <?= $user['institution_id'] ? htmlspecialchars(getInstitutionName($user['institution_id'], $colleges)) : 'N/A' ?></p>

        <div class="btn-group mt-3">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal">Edit Profile</button>
            <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#passwordModal">Change Password</button>
            <a href="../logout.php" class="btn btn-outline-danger">Logout</a>
            <!-- Trigger button -->
            <?php if (!$is_donor): ?>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#donorModal">
                    Become a Blood Donor
                </button>
            <?php else: ?>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#locationModal">
                    Update Donor Location
                </button>
            <?php endif; ?>


        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($update_error)): ?>
                        <div class="alert alert-danger"><?= $update_error ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input name="phone_number" type="tel" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" id="role" onchange="toggleInstitutionDropdown()">
                            <option value="user" <?= ($user['role'] === 'user') ? 'selected' : '' ?>>User</option>
                            <option value="student" <?= ($user['role'] === 'student') ? 'selected' : '' ?>>Student</option>
                        </select>
                    </div>
                    <div class="mb-3 <?= ($user['role'] === 'student') ? '' : 'd-none' ?>" id="institution-group">
                        <label class="form-label">Select Institution</label>
                        <select class="form-select" name="institution" id="institution">
                            <option value="">-- Select Institution --</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['institution_id'] ?>" <?= ($user['institution_id'] == $college['institution_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($college['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="update_profile" type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($password_error)): ?>
                        <div class="alert alert-danger"><?= $password_error ?></div>
                    <?php elseif (isset($password_message)): ?>
                        <div class="alert alert-success"><?= $password_message ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Old Password</label>
                        <input name="old_password" type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input name="new_password" type="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="change_password" type="submit" class="btn btn-warning">Update Password</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal to collect donor willingness -->
    <div class="modal fade" id="donorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Become a Donor</h5>
                    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-select" required>
                            <option value="">-- Select --</option>
                            <?php
                            $blood_groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
                            foreach ($blood_groups as $group) {
                                echo "<option value='$group'>$group</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select" required>
                            <option value="">-- Select --</option>
                            <?php
                            $genders = ['male', 'female', 'other'];
                            foreach ($genders as $gender) {
                                echo "<option value='$gender'>$gender</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Your Location</label>
                        <div id="map" style="height: 350px;"></div>
                        <input type="hidden" name="latitude" id="latitude" />
                        <input type="hidden" name="longitude" id="longitude" />
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="agree" id="agree" required>
                        <label class="form-check-label" for="agree">
                            I agree to donate blood when needed and confirm my willingness.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="submit_donor" type="submit" class="btn btn-success">Submit</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Donor Location Modal -->
    <div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Update Location</h5>
                    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Update Your Location</label>
                        <div id="map2" style="height: 350px;"></div>
                        <input type="hidden" name="latitude" id="latitude2" />
                        <input type="hidden" name="longitude" id="longitude2" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button name="update_location" type="submit" class="btn btn-success">Update Location</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'user_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <!-- Initialize Leaflet Map with Geocoder -->
    <script>
        let map = L.map('map').setView([10.0, 76.0], 7); // Default to Kerala region

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Add Geocoder control
        L.Control.geocoder({
                defaultMarkGeocode: false
            })
            .on('markgeocode', function(e) {
                const latlng = e.geocode.center;
                map.setView(latlng, 15);
                marker.setLatLng(latlng);
                document.getElementById('latitude').value = latlng.lat;
                document.getElementById('longitude').value = latlng.lng;
            })
            .addTo(map);

        // Marker that updates on click
        let marker = L.marker([10.0, 76.0], {
            draggable: true
        }).addTo(map);

        function updateLatLng(e) {
            const latlng = e.latlng || marker.getLatLng();
            document.getElementById('latitude').value = latlng.lat;
            document.getElementById('longitude').value = latlng.lng;
        }

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            updateLatLng(e);
        });

        marker.on('dragend', updateLatLng);

        // Update fields on modal show
        document.getElementById('donorModal').addEventListener('shown.bs.modal', () => {
            map.invalidateSize();
        });
    </script>

    <script>
        let map2 = L.map('map2').setView([<?= $donor_data['latitude'] ?? 10.0 ?>, <?= $donor_data['longitude'] ?? 76.0 ?>], 7);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map2);

        let marker2 = L.marker([<?= $donor_data['latitude'] ?? 10.0 ?>, <?= $donor_data['longitude'] ?? 76.0 ?>], {
            draggable: true
        }).addTo(map2);

        // Add geocoder/search control
        L.Control.geocoder({
                defaultMarkGeocode: false
            })
            .on('markgeocode', function(e) {
                const latlng = e.geocode.center;
                map2.setView(latlng, 15);
                marker2.setLatLng(latlng);
                document.getElementById('latitude2').value = latlng.lat;
                document.getElementById('longitude2').value = latlng.lng;
            })
            .addTo(map2);

        function updateLatLng2(e) {
            const latlng = e.latlng || marker2.getLatLng();
            document.getElementById('latitude2').value = latlng.lat;
            document.getElementById('longitude2').value = latlng.lng;
        }

        map2.on('click', function(e) {
            marker2.setLatLng(e.latlng);
            updateLatLng2(e);
        });

        marker2.on('dragend', updateLatLng2);

        document.getElementById('locationModal').addEventListener('shown.bs.modal', () => {
            map2.invalidateSize();
        });
    </script>



    <script>
        function toggleInstitutionDropdown() {
            const role = document.getElementById("role").value;
            const institutionGroup = document.getElementById("institution-group");
            if (role === "student") {
                institutionGroup.classList.remove("d-none");
            } else {
                institutionGroup.classList.add("d-none");
                document.getElementById("institution").value = "";
            }
        }
    </script>

    <?php
    // Helper to get institution name from ID
    function getInstitutionName($id, $colleges)
    {
        foreach ($colleges as $college) {
            if ($college['institution_id'] == $id) return $college['name'];
        }
        return '';
    }
    ?>
</body>

</html>