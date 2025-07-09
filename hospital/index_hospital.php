<?php
$required_role = "hospital";
include '../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard</title>
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
    <a href="../logout.php" class="logout-btn">Logout</a>
    <h1>Hospital Dashboard</h1>

   <script>
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>
</html>