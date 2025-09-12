<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
include '../config/db.php';

$donor_id = $_GET['id'] ?? null;
if (!$donor_id) {
    header("Location: index_user.php");
    exit;
}

// Fetch donor details
$stmt = $conn->prepare("SELECT u.name, u.email, u.phone, u.role, d.blood_group, d.latitude, d.longitude, d.last_donated , d.is_available, d.gender
                        FROM donors d 
                        JOIN users u ON d.user_id = u.user_id 
                        WHERE u.user_id = ?");
$stmt->bind_param("i", $donor_id);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();

if (!$donor) {
    echo "<div class='alert alert-danger'>Donor not found.</div>";
    exit;
}
function getPlaceNameFromLatLong($lat, $lng)
{
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lng";

    $opts = [
        "http" => [
            "header" => "User-Agent: BloodBankApp/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);

    $json = file_get_contents($url, false, $context);
    if ($json) {
        $data = json_decode($json, true);

        if (isset($data['address'])) {
            $address = $data['address'];
            // Pick the most relevant location field
            return $address['village'] ?? $address['town'] ?? $address['city'] ?? $address['state'] ?? "Unknown Place";
        }
    }
    return "Unknown Place";
}

$place_name = "Unknown Place";
if (!empty($donor['latitude']) && !empty($donor['longitude'])) {
    $place_name = getPlaceNameFromLatLong($donor['latitude'], $donor['longitude']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Details</title>
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

        .btn-back {
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 30px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include 'user_layout_start.php'; ?>

    <div class="container my-4">
        <div class="main-card">
            <!-- Donor Header -->
            <div class="main-header">
                <h2><?= htmlspecialchars($donor['name']) ?></h2>
                <p><i class="bi bi-envelope"></i> <?= htmlspecialchars($donor['email']) ?></p>
            </div>

            <!-- Donor Info -->
            <div class="info-grid">
                <div class="info-card">
                    <i class="bi bi-droplet-fill"></i>
                    <p>Blood Group</p>
                    <span><?= htmlspecialchars($donor['blood_group']) ?></span>
                </div>
                <div class="info-card">
                    <i class="bi bi-telephone-fill"></i>
                    <p>Phone</p>
                    <span><?= htmlspecialchars($donor['phone']) ?></span>
                </div>
                <div class="info-card">
                    <i class="bi bi-gender-ambiguous"></i>
                    <p>Gender</p>
                    <span><?= htmlspecialchars($donor['gender']) ?></span>
                </div>
                <div class="info-card">
                    <i class="bi bi-check2-circle"></i>
                    <p>Availability</p>
                    <span>
                        <?= $donor['is_available'] ? '<span class="badge bg-success">Available</span>' : '<span class="badge bg-secondary">Not Available</span>' ?>
                    </span>
                </div>
                <div class="info-card">
                    <i class="bi bi-calendar-heart"></i>
                    <p>Last Donated</p>
                    <span><?= $donor['last_donated'] ? date("M d, Y", strtotime($donor['last_donated'])) : "Never" ?></span>
                </div>
                <div class="info-card">
                    <i class="bi bi-geo-alt-fill"></i>
                    <p>Location</p>
                    <span><?= htmlspecialchars($place_name) ?></span>
                </div>

            </div>

            <!-- Back Button -->
            <div class="text-center pb-4">
                <a href="index_user.php" class="btn btn-outline-secondary btn-back">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php include 'user_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

</body>

</html>