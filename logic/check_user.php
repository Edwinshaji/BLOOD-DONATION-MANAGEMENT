<?php
include '../config/db.php';

$email = $_POST['email'] ?? '';
$response = ['status' => 'notfound', 'message' => 'Not found. You can register'];

if ($email) {
    // 1. Check main admin
    $stmt = $conn->prepare("SELECT * FROM main_admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $response = ['status' => 'found', 'message' => 'Admin found. You can login'];
    } else {
        // 2. Check users
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $response = ['status' => 'found', 'message' => 'User found. You can login'];
        } else {
            // 3. Check institutions (college/hospital)
            $stmt = $conn->prepare("SELECT institution_id, type FROM institutions WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $role = $row['type']; // 'college' or 'hospital'
                $response = ['status' => 'found', 'message' => ucfirst($role) . " found. You can login"];
            }
        }
    }
    $stmt->close();
}

echo json_encode($response);
$conn->close();
