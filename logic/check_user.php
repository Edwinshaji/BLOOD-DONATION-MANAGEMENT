<?php
include '../config/db.php';

$email = $_POST['email'] ?? '';
$response = ['status' => 'notfound', 'message' => 'User not found.You can register'];

if ($email) {
    // Check in users
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $response = ['status' => 'found', 'message' => 'User found.You can login'];
    } else {
        // Check in institutions
        $stmt = $conn->prepare("SELECT institution_id FROM institutions WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $response = ['status' => 'found', 'message' => 'Institution found.'];
        }
    }
    $stmt->close();
}
echo json_encode($response);
$conn->close();
?>