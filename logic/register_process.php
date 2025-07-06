<?php
// Database connection
include '../config/db.php';

// Get POST data and sanitize
$role     = isset($_POST['role']) ? $_POST['role'] : '';
$name     = isset($_POST['name']) ? $_POST['name'] : '';
$email    = isset($_POST['email']) ? $_POST['email'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$role || !$name || !$email || !$password) {
    echo "<script>alert('All fields are required.'); window.location.href='../register.php';</script>";
    exit;
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

if ($role === 'user' || $role === 'student') {
    // Insert into users table
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conn->close();
        echo "<script>alert('Email already registered.'); window.location.href='../register.php';</script>";
        exit;
    }
    $stmt->close();

    $user_role = $role === 'student' ? 'student' : 'user';
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $user_role);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo "<script>alert('Registration successful. Please login.'); window.location.href='../login.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error: " . $stmt->error . "'); window.location.href='../register.php';</script>";
        $stmt->close();
        $conn->close();
        exit;
    }

} elseif ($role === 'hospital' || $role === 'college') {
    // Insert into institutions table
    $type = $role; // 'hospital' or 'college'
    $stmt = $conn->prepare("SELECT institution_id FROM institutions WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conn->close();
        echo "<script>alert('Email already registered.'); window.location.href='../register.php';</script>";
        exit;
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO institutions (name, type, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $type, $email, $hashed_password);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo "<script>alert('Registration successful. Please login.'); window.location.href='../login.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error: " . $stmt->error . "'); window.location.href='../register.php';</script>";
        $stmt->close();
        $conn->close();
        exit;
    }

} else {
    echo "<script>alert('Invalid role.'); window.location.href='../register.php';</script>";
    exit;
}

$conn->close();
?>