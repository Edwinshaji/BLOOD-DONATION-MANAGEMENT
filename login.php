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
    <title>Blood Donation Management - Login</title>
    <?php include './includes/header.php' ?>
    <style>
        body {
            min-height: 100vh;
            font-family: 'Montserrat', Arial, sans-serif;
            background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 100%);
            position: relative;
            overflow: hidden;
        }
        /* Decorative elements */
        .circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.18;
            z-index: 0;
        }
        .circle1 {
            width: 420px; height: 420px;
            background: #ffb3b3;
            top: -120px; left: -120px;
        }
        .circle2 {
            width: 260px; height: 260px;
            background: #ffcccc;
            bottom: 60px; right: 40px;
        }
        .circle3 {
            width: 160px; height: 160px;
            background: #fff0f0;
            top: 80px; right: 120px;
        }
        .circle4 {
            width: 100px; height: 100px;
            background: #ffd6d6;
            bottom: 180px; left: 100px;
        }
        .blood-drop {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -70%);
            z-index: 0;
            opacity: 0.12;
        }
        .login-card {
            border-radius: 2rem;
            box-shadow: 0 8px 32px 0 rgba(255, 99, 132, 0.18);
            background: rgba(255,255,255,0.98);
            padding: 4rem 3rem 3rem 3rem;
            max-width: 540px;
            width: 100%;
            z-index: 1;
        }
        .login-title {
            color: #e57373;
            font-weight: 700;
            margin-bottom: .5rem;
            text-align: center;
            letter-spacing: 1px;
            font-size: 2.5rem;
        }
        .form-label {
            color: #e57373;
            font-weight: 600;
        }
        .form-control {
            font-size: 1.15rem;
            border-radius: 0.8rem;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #e57373;
            box-shadow: 0 0 0 0.2rem rgba(229,115,115,.15);
        }
        .btn-red {
            background: linear-gradient(90deg, #ffb3b3 0%, #e57373 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: 1.2rem;
            border-radius: 0.8rem;
            letter-spacing: 1px;
            transition: background 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 16px 0 rgba(255, 99, 132, 0.10);
        }
        .btn-red:hover {
            background: linear-gradient(90deg, #e57373 0%, #ffb3b3 100%);
            box-shadow: 0 6px 24px 0 rgba(255, 99, 132, 0.18);
        }
        .signup-link {
            color: #e57373;
            text-decoration: none;
            font-weight: 600;
        }
        .signup-link:hover {
            text-decoration: underline;
            color: #ffb3b3;
        }
        .card-header {
            background: none;
            border-bottom: none;
        }
        @media (max-width: 576px) {
            .login-card {
                padding: 1rem .5rem .5rem 1rem;
                max-width: 98vw;
            }
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Decorative background elements -->
    <div class="circle circle1"></div>
    <div class="circle circle2"></div>
    <div class="circle circle3"></div>
    <div class="circle circle4"></div>
    
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="login-card shadow-lg">
            <div class="card-header text-center mb-4">
                <h2 class="login-title">Login</h2>
            </div>
            <form method="POST" action="logic/login_process.php" autocomplete="on">
                <div class="mb-4">
                    <label for="role" class="form-label fw-semibold" style="color:#e57373;">Select Role</label>
                    <select id="role" 
                        class="form-select form-select-lg shadow-sm rounded-3"
                        name="role"
                        style="height: calc(2.875rem + 2px); max-width:100%; min-width:0; padding: 0.75rem 1rem; font-size:1.15rem; border-radius:0.8rem; border:1px solid #ced4da;">
                        <option value="user" selected>Users/Students</option>
                        <option value="hospital">Hospitals</option>
                        <option value="college">Colleges</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control shadow-sm" id="email" name="email" placeholder="Enter email" required>
                    <div id="email-status" class="form-text mt-1"></div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control shadow-sm" id="password" name="password" placeholder="Enter password" required>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-red btn-lg">Login</button>
                </div>
            </form>
            <div class="text-center mt-2">
                <span>Don't have an account? </span>
                <a href="register.php" class="signup-link">Sign up</a>
            </div>
        </div>
    </div>
    <?php include './includes/footer.php' ?>
    <script>
    $('#email').on('blur', function() {
        var email = $(this).val();
        if(email.length > 0) {
            $.ajax({
                url: 'logic/check_user.php',
                type: 'POST',
                data: {email: email},
                success: function(response) {
                    // Expecting response: { status: "found"|"notfound", message: "..." }
                    try {
                        var res = JSON.parse(response);
                        if(res.status === "found") {
                            $('#email-status').html('<span style="color:green;">' + res.message + '</span>');
                        } else {
                            $('#email-status').html('<span style="color:red;">' + res.message + '</span>');
                        }
                    } catch(e) {
                        // fallback for plain text
                        $('#email-status').html(response);
                    }
                }
            });
        } else {
            $('#email-status').html('');
        }
    });
    </script>
</body>
</html>