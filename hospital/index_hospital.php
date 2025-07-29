<?php
$required_role = "hospital";
include '../includes/auth.php';
include '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard</title>
    <?php include '../includes/header.php' ?>
    <style>
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            padding: 8px 18px;
            background: #e57373;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #ffb3b3;
            color: #e57373;
        }
    </style>
</head>
<body>
    <?php include 'hospital_layout_start.php' ?>
        <h1>Hello</h1>
    <?php include 'hospital_layout_end.php' ?>
    <?php include '../includes/footer.php' ?>


</body>
</html>