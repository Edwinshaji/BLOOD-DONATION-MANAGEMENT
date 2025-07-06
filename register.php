<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation Management - Register</title>
    <!-- Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Montserrat', Arial, sans-serif;
            background: linear-gradient(135deg, #fff5f5 0%, #ffeaea 100%);
            position: relative;
            overflow: hidden;
        }
        .circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.18;
            z-index: 0;
        }
        .circle1 { width: 420px; height: 420px; background: #ffb3b3; top: -120px; left: -120px; }
        .circle2 { width: 260px; height: 260px; background: #ffcccc; bottom: 60px; right: 40px; }
        .circle3 { width: 160px; height: 160px; background: #fff0f0; top: 80px; right: 120px; }
        .circle4 { width: 100px; height: 100px; background: #ffd6d6; bottom: 180px; left: 100px; }
        .register-card {
            border-radius: 2rem;
            box-shadow: 0 8px 32px 0 rgba(255, 99, 132, 0.18);
            background: rgba(255,255,255,0.98);
            padding: 4rem 3rem 3rem 3rem;
            max-width: 540px;
            width: 100%;
            z-index: 1;
        }
        .register-title {
            color: #e57373;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
            letter-spacing: 1px;
            font-size: 2.2rem;
        }
        .form-label {
            color: #e57373;
            font-weight: 600;
        }
        .form-control, .form-select {
            font-size: 1.15rem;
            border-radius: 0.8rem;
            padding: 0.75rem 1rem;
        }
        .form-control:focus, .form-select:focus {
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
        .btn-red:hover, .btn-toggle.active {
            background: linear-gradient(90deg, #e57373 0%, #ffb3b3 100%);
            color: #fff;
        }
        .btn-toggle {
            background: #fff0f0;
            color: #e57373;
            border: 1.5px solid #e57373;
            font-weight: 600;
            border-radius: 0.8rem;
            margin: 0 0.5rem 1.5rem 0.5rem;
            transition: background 0.3s, color 0.3s;
        }
        .btn-toggle:last-child {
            margin-right: 0;
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
        @media (max-width: 576px) {
            .register-card {
                padding: 2rem 1rem 1.5rem 1rem;
                max-width: 98vw;
            }
            .register-title {
                font-size: 1.5rem;
            }
            .btn-toggle {
                margin-bottom: 1rem;
                width: 100%;
                margin-right: 0;
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
        <div class="register-card shadow-lg">
            <h2 class="register-title">Register</h2>
            <div class="d-flex flex-wrap justify-content-center mb-4">
                <button type="button" class="btn btn-toggle active" id="btn-user" onclick="showForm('user')">User/Student</button>
                <button type="button" class="btn btn-toggle" id="btn-hospital" onclick="showForm('hospital')">Hospital</button>
                <button type="button" class="btn btn-toggle" id="btn-college" onclick="showForm('college')">College</button>
            </div>
            <!-- User/Student Form -->
            <form id="form-user" method="POST" action="logic/register_process.php" autocomplete="on" >
                <input type="hidden" name="role" value="user">
                <div class="mb-4">
                    <label for="user-name" class="form-label">Name</label>
                    <input type="text" class="form-control shadow-sm" id="user-name" name="name" placeholder="Enter your name" required>
                </div>
                <div class="mb-4">
                    <label for="user-email" class="form-label">Email address</label>
                    <input type="email" class="form-control shadow-sm" id="user-email" name="email" placeholder="Enter email" required>
                    <div id="user-email-status" class="form-text mt-1"></div>
                </div>
                <div class="mb-4">
                    <label for="user-password" class="form-label">Password</label>
                    <input type="password" class="form-control shadow-sm" id="user-password" name="password" placeholder="Enter password" required>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-red btn-lg">Register</button>
                </div>
            </form>
            <!-- Hospital Form -->
            <form id="form-hospital" method="POST" action="logic/register_process.php" autocomplete="off" style="display:none;">
                <input type="hidden" name="role" value="hospital">
                <div class="mb-4">
                    <label for="hospital-name" class="form-label">Hospital Name</label>
                    <input type="text" class="form-control shadow-sm" id="hospital-name" name="name" placeholder="Enter hospital name" required>
                </div>
                <div class="mb-4">
                    <label for="hospital-email" class="form-label">Email address</label>
                    <input type="email" class="form-control shadow-sm" id="hospital-email" name="email" placeholder="Enter email" required>
                    <div id="hospital-email-status" class="form-text mt-1"></div>
                </div>
                <div class="mb-4">
                    <label for="hospital-password" class="form-label">Password</label>
                    <input type="password" class="form-control shadow-sm" id="hospital-password" name="password" placeholder="Enter password" required>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-red btn-lg">Register</button>
                </div>
            </form>
            <!-- College Form -->
            <form id="form-college" method="POST" action="logic/register_process.php" autocomplete="off" style="display:none;">
                <input type="hidden" name="role" value="college">
                <div class="mb-4">
                    <label for="college-name" class="form-label">College Name</label>
                    <input type="text" class="form-control shadow-sm" id="college-name" name="name" placeholder="Enter college name" required>
                </div>
                <div class="mb-4">
                    <label for="college-email" class="form-label">Email address</label>
                    <input type="email" class="form-control shadow-sm" id="college-email" name="email" placeholder="Enter email" required>
                    <div id="college-email-status" class="form-text mt-1"></div>
                </div>
                <div class="mb-4">
                    <label for="college-password" class="form-label">Password</label>
                    <input type="password" class="form-control shadow-sm" id="college-password" name="password" placeholder="Enter password" required>
                </div>
                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-red btn-lg">Register</button>
                </div>
            </form>
            <div class="text-center mt-2">
                <span>Already have an account? </span>
                <a href="login.php" class="signup-link">Login</a>
            </div>
        </div>
    </div>
    <!--Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function showForm(role) {
        document.getElementById('btn-user').classList.remove('active');
        document.getElementById('btn-hospital').classList.remove('active');
        document.getElementById('btn-college').classList.remove('active');
        document.getElementById('btn-' + role).classList.add('active');
        document.getElementById('form-user').style.display = (role === 'user') ? 'block' : 'none';
        document.getElementById('form-hospital').style.display = (role === 'hospital') ? 'block' : 'none';
        document.getElementById('form-college').style.display = (role === 'college') ? 'block' : 'none';
    }

    // AJAX for user email
    $('#user-email').on('blur', function() {
        var email = $(this).val();
        if(email.length > 0) {
            $.ajax({
                url: 'logic/check_user.php',
                type: 'POST',
                data: {email: email},
                success: function(response) {
                    try {
                        var res = JSON.parse(response);
                        if(res.status === "found") {
                            $('#user-email-status').html('<span style="color:red;">' + res.message + '</span>');
                        } else {
                            $('#user-email-status').html('<span style="color:green;">' + res.message + '</span>');
                        }
                    } catch(e) {
                        $('#user-email-status').html(response);
                    }
                }
            });
        } else {
            $('#user-email-status').html('');
        }
    });
    // AJAX for hospital email
    $('#hospital-email').on('blur', function() {
        var email = $(this).val();
        if(email.length > 0) {
            $.ajax({
                url: 'logic/check_user.php',
                type: 'POST',
                data: {email: email},
                success: function(response) {
                    try {
                        var res = JSON.parse(response);
                        if(res.status === "found") {
                            $('#hospital-email-status').html('<span style="color:red;">' + res.message + '</span>');
                        } else {
                            $('#hospital-email-status').html('<span style="color:green;">' + res.message + '</span>');
                        }
                    } catch(e) {
                        $('#hospital-email-status').html(response);
                    }
                }
            });
        } else {
            $('#hospital-email-status').html('');
        }
    });
    // AJAX for college email
    $('#college-email').on('blur', function() {
        var email = $(this).val();
        if(email.length > 0) {
            $.ajax({
                url: 'logic/check_user.php',
                type: 'POST',
                data: {email: email},
                success: function(response) {
                    try {
                        var res = JSON.parse(response);
                        if(res.status === "found") {
                            $('#college-email-status').html('<span style="color:red;">' + res.message + '</span>');
                        } else {
                            $('#college-email-status').html('<span style="color:green;">' + res.message + '</span>');
                        }
                    } catch(e) {
                        $('#college-email-status').html(response);
                    }
                }
            });
        } else {
            $('#college-email-status').html('');
        }
    });
    </script>
</body>
</html>