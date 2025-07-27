<?php
$required_role = ['user', 'student'];
include '../includes/auth.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <?php include '../includes/header.php' ?>
</head>

<body>
    <?php include 'user_layout_start.php' ?>
    <!-- Page Content -->
    <h1 class="mb-4">Welcome to Your Dashboard</h1>
    <p class="lead">Manage your donations, events, and account here.</p>

    <!-- Optional Custom Logout Button -->
    <a href="../logout.php" class="btn logout-btn position-absolute top-0 end-0 m-3">
        Logout
    </a>
    <?php include 'user_layout_end.php' ?>
    <?php include '../includes/footer.php' ?>
</body>

</html>