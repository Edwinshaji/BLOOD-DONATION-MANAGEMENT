<?php
$required_role = "admin";
include '../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background: #fff5f5;
        }

        .sidebar {
            background: linear-gradient(135deg, #ffb3b3 0%, #e57373 100%);
            color: #fff;
            min-height: 100vh;
            padding-top: 2rem;
        }

        .sidebar .nav-link,
        .sidebar .btn-link {
            color: #fff;
            font-weight: 600;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:focus,
        .sidebar .nav-link:hover {
            background: #fff0f0;
            color: #e57373 !important;
            border-radius: 0.5rem;
        }

        .sidebar .btn-link {
            text-align: left;
            width: 100%;
            color: #fff;
        }

        .sidebar .btn-link:focus,
        .sidebar .btn-link:hover {
            color: #e57373;
            background: #fff0f0;
        }

        .sidebar .logout-link {
            color: #fff;
            font-weight: 700;
            margin-top: 2rem;
        }

        .sidebar .logout-link:hover {
            color: #e57373;
            background: #fff0f0;
            border-radius: 0.5rem;
        }

        .sidebar .sidebar-header {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 2rem;
            color: #fff;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                min-height: auto;
                padding-top: 1rem;
            }
        }

        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                z-index: 1040;
                top: 0;
                left: 0;
                width: 240px;
                height: 100vh;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.2);
                z-index: 1039;
            }

            .sidebar-backdrop.show {
                display: block;
            }
        }

        .main-content {
            background: #fff;
            min-height: 100vh;
            padding: 2.5rem 2.5rem 2.5rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        @media (max-width: 991.98px) {
            .main-content {
                padding: 1.2rem;
            }
        }

        /* Sidebar mobile overlay */
        @media (max-width: 767.98px) {
            #sidebarMenu {
                transform: translateX(-100%);
                width: 240px !important;
                background: linear-gradient(135deg, #ffb3b3 0%, #e57373 100%);
                position: fixed !important;
                top: 0;
                left: 0;
                height: 100vh !important;
                z-index: 1051;
            }

            #sidebarMenu.show {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                display: none;
            }

            .sidebar-backdrop.show {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0, 0, 0, 0.2);
                z-index: 1050;
            }

            .main-content {
                margin-left: 0 !important;
                padding-top: 1.2rem !important;
            }

            .row.g-3.mb-3>[class^="col-"] {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        @media (min-width: 768px) {
            #sidebarMenu {
                transform: none !important;
                position: fixed !important;
                left: 0;
                top: 0;
                height: 100vh !important;
                z-index: 1040;
            }

            .main-content {
                margin-left: 16.7%;
            }
        }

        .toggle-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1050;
            background: #e57373;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 1.3rem;
        }

        @media (max-width: 767.98px) {
            .toggle-btn {
                display: block;
            }
        }

        /* Main dashboard button color (different from sidebar) */
        .btn-main {
            background: linear-gradient(90deg, #6a82fb 0%, #fc5c7d 100%);
            color: #fff;
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 0.8rem;
            letter-spacing: 1px;
            transition: background 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 16px 0 rgba(108, 99, 255, 0.10);
        }

        .btn-main:hover,
        .btn-main:focus {
            background: linear-gradient(90deg, #fc5c7d 0%, #6a82fb 100%);
            color: #fff;
        }

        .dashboard-card {
            min-height: 270px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .dashboard-card .card-title {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .dashboard-card .card-text,
        .dashboard-card ul {
            font-size: 1.05rem;
        }
    </style>
</head>

<body>
    <!-- Header for mobile -->
    <header class="d-block d-md-none bg-white shadow-sm py-2 px-3 position-sticky top-0" style="z-index:1050;">
        <button class="btn btn-outline-danger" id="sidebarToggleMobile" type="button">
            <i class="bi bi-list" style="font-size:1.5rem;"></i>
        </button>
        <span class="fw-bold ms-2" style="color:#e57373;">Admin Panel</span>
    </header>

    <!-- Sidebar Toggle Button for Mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="container-fluid">
        <div class="row flex-nowrap">
            <!-- Sidebar -->
            <nav class="col-12 col-md-3 col-lg-2 px-0 sidebar d-flex flex-column position-fixed top-0 start-0 h-100" id="sidebarMenu" style="z-index:1040;">
                <div class="sidebar-header text-center mb-4">
                    <span>Admin Panel</span>
                </div>
                <ul class="nav flex-column mb-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index_admin.php">
                            <i class="bi bi-house-door"></i> Dashboard Home
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li>
                        <button class="btn btn-link nav-link" data-bs-toggle="collapse" data-bs-target="#manageCollapse" aria-expanded="false">
                            <i class="bi bi-gear"></i> Manage <span class="float-end"><i class="bi bi-chevron-down"></i></span>
                        </button>
                        <div class="collapse" id="manageCollapse">
                            <ul class="nav flex-column ms-3">
                                <li><a class="nav-link" href="manage_colleges.php" target="mainFrame">Colleges</a></li>
                                <li><a class="nav-link" href="manage_hospitals.php" target="mainFrame">Hospitals</a></li>
                                <li><a class="nav-link" href="manage_users.php" target="mainFrame">Users</a></li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <button class="btn btn-link nav-link" data-bs-toggle="collapse" data-bs-target="#analyticsCollapse" aria-expanded="false">
                            <i class="bi bi-bar-chart"></i> Analytics & Reports <span class="float-end"><i class="bi bi-chevron-down"></i></span>
                        </button>
                        <div class="collapse" id="analyticsCollapse">
                            <ul class="nav flex-column ms-3">
                                <li><a class="nav-link" href="analytics.php" target="mainFrame">System Analytics</a></li>
                                <li><a class="nav-link" href="export_pdf.php" target="mainFrame">Export PDF</a></li>
                                <li><a class="nav-link" href="export_excel.php" target="mainFrame">Export Excel</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
                <a href="../logout.php" class="nav-link logout-link mt-auto mb-3"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </nav>
            <!-- Main Content -->
            <main class="col main-content" style="padding-top:2.2rem; margin-left: 16.7%; min-height:100vh;">
                <div class="container-fluid px-0">
                    <!-- Summary Cards Row -->
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
                            <div class="card shadow-sm border-0 text-center" style="background: #f8f9fa;">
                                <div class="card-body">
                                    <div class="mb-2" style="font-size:2rem; color:#e57373;">
                                        <i class="bi bi-droplet-fill"></i>
                                    </div>
                                    <div class="fs-4 fw-bold mb-1" style="color:#198754;">15</div>
                                    <div class="fw-semibold text-muted">Donations Today</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
                            <div class="card shadow-sm border-0 text-center" style="background: #f8f9fa;">
                                <div class="card-body">
                                    <div class="mb-2" style="font-size:2rem; color:#0d6efd;">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div class="fs-4 fw-bold mb-1" style="color:#198754;">120</div>
                                    <div class="fw-semibold text-muted">Total Donors</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
                            <div class="card shadow-sm border-0 text-center" style="background: #f8f9fa;">
                                <div class="card-body">
                                    <div class="mb-2" style="font-size:2rem; color:#fd7e14;">
                                        <i class="bi bi-bell-fill"></i>
                                    </div>
                                    <div class="fs-4 fw-bold mb-1" style="color:#198754;">4</div>
                                    <div class="fw-semibold text-muted">Today's Requests</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
                            <div class="card shadow-sm border-0 text-center" style="background: #f8f9fa;">
                                <div class="card-body">
                                    <div class="mb-2" style="font-size:2rem; color:#6610f2;">
                                        <i class="bi bi-hospital-fill"></i>
                                    </div>
                                    <div class="fs-4 fw-bold mb-1" style="color:#198754;">8</div>
                                    <div class="fw-semibold text-muted">Total Hospitals</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
                            <div class="card shadow-sm border-0 text-center" style="background: #f8f9fa;">
                                <div class="card-body">
                                    <div class="mb-2" style="font-size:2rem; color:#ffc107;">
                                        <i class="bi bi-mortarboard-fill"></i>
                                    </div>
                                    <div class="fs-4 fw-bold mb-1" style="color:#198754;">6</div>
                                    <div class="fw-semibold text-muted">Total Colleges</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
                            <div class="card shadow-sm border-0 text-center" style="background: #f8f9fa;">
                                <div class="card-body">
                                    <div class="mb-2" style="font-size:2rem; color:#0dcaf0;">
                                        <i class="bi bi-calendar-event-fill"></i>
                                    </div>
                                    <div class="fs-4 fw-bold mb-1" style="color:#198754;">2</div>
                                    <div class="fw-semibold text-muted">Upcoming Events</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
                            <div class="card shadow-sm border-0 text-center" style="background: #f8f9fa;">
                                <div class="card-body">
                                    <div class="mb-2" style="font-size:2rem; color:#20c997;">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                    <div class="fs-4 fw-bold mb-1" style="color:#198754;">3</div>
                                    <div class="fw-semibold text-muted">Pending Approvals</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-sm-6 col-md-4 col-xl-3">
                            <div class="card shadow-sm border-0 text-center" style="background: #f8f9fa;">
                                <div class="card-body">
                                    <div class="mb-2" style="font-size:2rem; color:#6f42c1;">
                                        <i class="bi bi-person-badge-fill"></i>
                                    </div>
                                    <div class="fs-4 fw-bold mb-1" style="color:#198754;">75</div>
                                    <div class="fw-semibold text-muted">Total Students</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Tables Section: stacked on mobile -->
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card shadow-sm border-0 mb-4" style="background: #f8f9fa;">
                                <div class="card-header bg-white fw-bold" style="color:#e57373;">New Donor Confirmations</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Blood Group</th>
                                                    <th>User Type</th>
                                                    <th>Institution</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>John Doe</td>
                                                    <td><span class="badge bg-danger">B+</span></td>
                                                    <td>Student</td>
                                                    <td>ABC College</td>
                                                    <td><a href="manage_users.php" class="btn btn-outline-danger btn-sm">View</a></td>
                                                </tr>
                                                <tr>
                                                    <td>Jane Smith</td>
                                                    <td><span class="badge bg-danger">O+</span></td>
                                                    <td>User</td>
                                                    <td>-</td>
                                                    <td><a href="manage_users.php" class="btn btn-outline-danger btn-sm">View</a></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card shadow-sm border-0 mb-4" style="background: #f8f9fa;">
                                <div class="card-header bg-white fw-bold" style="color:#e57373;">Pending Institutions</div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Date Applied</th>
                                                    <th>Status</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>XYZ College</td>
                                                    <td>College</td>
                                                    <td>2025-07-08</td>
                                                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                                                    <td><a href="manage_colleges.php" class="btn btn-outline-danger btn-sm">Review</a></td>
                                                </tr>
                                                <tr>
                                                    <td>ABC Hospital</td>
                                                    <td>Hospital</td>
                                                    <td>2025-07-09</td>
                                                    <td><span class="badge bg-warning text-dark">Pending</span></td>
                                                    <td><a href="manage_hospitals.php" class="btn btn-outline-danger btn-sm">Review</a></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Iframe for loading main features -->
                <iframe name="mainFrame" id="mainFrame" style="width:100%;border:none;min-height:600px;display:none;"></iframe>
            </main>
        </div>
    </div>
    <!-- Bootstrap JS Bundle (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        // Mobile sidebar toggle
        const sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
        const sidebarMenu = document.getElementById('sidebarMenu');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        if (sidebarToggleMobile && sidebarMenu && sidebarBackdrop) {
            sidebarToggleMobile.addEventListener('click', function() {
                sidebarMenu.classList.toggle('show');
                sidebarBackdrop.classList.toggle('show');
            });
            sidebarBackdrop.addEventListener('click', function() {
                sidebarMenu.classList.remove('show');
                sidebarBackdrop.classList.remove('show');
            });
            document.querySelectorAll('.sidebar .nav-link').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 768) {
                        sidebarMenu.classList.remove('show');
                        sidebarBackdrop.classList.remove('show');
                    }
                });
            });
        }
        // Sidebar fixed on scroll for desktop
        if (window.innerWidth >= 768) {
            document.querySelector('.sidebar').style.height = '100vh';
            document.querySelector('.sidebar').style.overflowY = 'auto';
        }
        // Load features in iframe
        document.querySelectorAll('a[target="mainFrame"]').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('mainFrame').style.display = 'block';
                document.querySelector('.container-fluid').style.display = 'none';
                document.getElementById('mainFrame').src = this.href;
            });
        });
    </script>
</body>

</html>