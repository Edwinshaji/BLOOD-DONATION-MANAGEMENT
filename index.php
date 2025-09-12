<?php
session_start();
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index_admin.php");
        exit;
    } elseif ($_SESSION['role'] === 'user' || $_SESSION['role'] === 'student') {
        header("Location: user/index_user.php");
        exit;
    } elseif ($_SESSION['role'] === 'hospital') {
        header("Location: hospital/index_hospital.php");
        exit;
    } elseif ($_SESSION['role'] === 'college') {
        header("Location: college/index_college.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation Management</title>
    <?php include './includes/header.php' ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            padding: 2rem;
            font-family: 'Montserrat', Arial, sans-serif;
            background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 100%);
            position: relative;
            overflow: hidden;
        }

        /* Decorative floating circles */
        .circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.18;
            z-index: 0;
            animation: float 8s ease-in-out infinite alternate;
        }

        .circle1 {
            width: 420px;
            height: 420px;
            background: #ffb3b3;
            top: -120px;
            left: -120px;
        }

        .circle2 {
            width: 260px;
            height: 260px;
            background: #ffcccc;
            bottom: 60px;
            right: 40px;
            animation-delay: 2s;
        }

        .circle3 {
            width: 160px;
            height: 160px;
            background: #fff0f0;
            top: 80px;
            right: 120px;
            animation-delay: 4s;
        }

        .circle4 {
            width: 100px;
            height: 100px;
            background: #ffd6d6;
            bottom: 180px;
            left: 100px;
            animation-delay: 6s;
        }

        @keyframes float {
            from {
                transform: translateY(0px);
            }

            to {
                transform: translateY(30px);
            }
        }

        .main-card {
            border-radius: 2rem;
            box-shadow: 0 8px 32px 0 rgba(255, 99, 132, 0.18);
            background: rgba(255, 255, 255, 0.98);
            padding: 3rem 2rem;
            max-width: 650px;
            width: 100%;
            z-index: 1;
            text-align: center;
            animation: fadeInUp 1.2s ease;
        }

        .title {
            color: #e53935;
            font-weight: 800;
            margin-bottom: 1rem;
            font-size: 2.8rem;
            animation: pulse 2s infinite;
        }

        .tagline {
            font-size: 1.15rem;
            color: #555;
            margin-bottom: 2rem;
            animation: fadeIn 2s ease 0.5s both;
        }

        .btn-red {
            background: linear-gradient(90deg, #ffb3b3 0%, #e57373 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: 1.2rem;
            border-radius: 0.8rem;
            padding: 0.9rem 2rem;
            letter-spacing: 1px;
            transition: all 0.4s ease;
            box-shadow: 0 4px 16px 0 rgba(255, 99, 132, 0.20);
            margin: 0.5rem;
        }

        .btn-red:hover {
            background: linear-gradient(90deg, #e57373 0%, #ffb3b3 100%);
            box-shadow: 0 8px 28px rgba(229, 83, 83, 0.45);
            transform: translateY(-3px) scale(1.05);
        }
    </style>
</head>

<body>
    <!-- Decorative background -->
    <div class="circle circle1"></div>
    <div class="circle circle2"></div>
    <div class="circle circle3"></div>
    <div class="circle circle4"></div>

    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="main-card shadow-lg animate__animated animate__fadeInUp">
            <h1 class="title animate__animated animate__bounceIn">Blood Donation</h1>
            <p class="tagline">Donate blood, save lives. Join us in making a difference.</p>

            <div class="d-flex justify-content-center">
                <a href="login.php" class="btn btn-red animate__animated animate__fadeInLeft">Login</a>
                <a href="register.php" class="btn btn-red animate__animated animate__fadeInRight">Register</a>
            </div>
        </div>
    </div>

    <?php include './includes/footer.php' ?>
</body>

</html>