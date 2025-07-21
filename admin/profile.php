<?php
$required_role = "admin";
include '../includes/auth.php';

// Get admin data from session (adjust keys as per your session structure)
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : "Admin User";
$admin_email = isset($_SESSION['admin_email']) ? $_SESSION['admin_email'] : "admin@gmail.com.com";
$admin_role = isset($_SESSION['role']) ? $_SESSION['role'] : "Administrator";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <?php include '../includes/header.php' ?>
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Montserrat', Arial, sans-serif;
        }

        .profile-card {
            max-width: 480px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 16px rgba(229, 115, 115, 0.08);
            padding: 2rem 2.5rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #e57373;
            color: #fff;
            border-radius: 50%;
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
        }

        .profile-label {
            color: #e57373;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <?php include 'admin_layout_start.php'; ?>
    <div class="profile-card text-center">
        <div class="profile-avatar mb-2">
            <i class="bi bi-person"></i>
        </div>
        <h4 class="mb-1"><?php echo htmlspecialchars($admin_name); ?></h4>
        <div class="mb-3 text-muted"><?php echo htmlspecialchars($admin_role); ?></div>
        <div class="mb-2">
            <span class="profile-label">Email:</span>
            <span><?php echo htmlspecialchars($admin_email); ?></span>
        </div>
        <div class="mb-2">
            <span class="profile-label">Role:</span>
            <span><?php echo htmlspecialchars($admin_role); ?></span>
        </div>
        <a href="../logout.php" class="btn btn-outline-danger mt-3">Logout</a>
    </div>

    <?php include 'admin_layout_end.php' ?>
    <?php include '../includes/footer.php' ?>
</body>

</html>