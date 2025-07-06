<?php
session_start();
include '../config/db.php';

// Get and sanitize POST data
$role     = isset($_POST['role']) ? $conn->real_escape_string($_POST['role']) : '';
$email    = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$email || !$password) {
    die("All fields are required.");
}

// 1. Check if email is main admin
$stmt = $conn->prepare("SELECT admin_id, email, password FROM main_admin WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 1) {
    $stmt->bind_result($admin_id, $admin_email, $hashed_password);
    $stmt->fetch();
    if (password_verify($password, $hashed_password)) {
        $_SESSION['admin_id'] = $admin_id;
        $_SESSION['admin_email'] = $admin_email;
        $_SESSION['role'] = 'admin';
        header("Location: ../admin/index_admin.php");
        exit;
    } else {
        $stmt->close();
        echo "<script>alert('Invalid admin credentials');window.location.href='../login.php';</script>";
        exit;
    }
}
$stmt->close();

// 2. User/Student Login
if ($role === 'user' || $role === 'student') {
    $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $name, $user_email, $hashed_password, $user_role);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $user_email;
            $_SESSION['role'] = $user_role;
            header("Location: ../user/index_user.php");
            exit;
        }
    }
    $stmt->close();
    echo "<script>alert('Invalid user credentials');window.location.href='../login.php';</script>";
    exit;
}

// 3. Hospital/College Login
if ($role === 'hospital' || $role === 'college') {
    $stmt = $conn->prepare("SELECT institution_id, name, type, email, password FROM institutions WHERE email = ? AND type = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($institution_id, $name, $type, $inst_email, $hashed_password);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['institution_id'] = $institution_id;
            $_SESSION['institution_name'] = $name;
            $_SESSION['institution_type'] = $type;
            $_SESSION['institution_email'] = $inst_email;
            $_SESSION['role'] = $type;
            if ($type === 'hospital') {
                header("Location: ../hospital/index_hospital.php");
            } else {
                header("Location: ../college/index_college.php");
            }
            exit;
        }
    }
    $stmt->close();
    echo "<script>alert('Invalid credentials');window.location.href='../login.php';</script>";
    exit;
}

echo "<script>alert('Invalid role selected or credentials');window.location.href='../login.php';</script>";
exit;
?>