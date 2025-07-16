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
    <?php include '../includes/header.php' ?>
    <style>
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background: #fff5f5;
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
    <?php include 'admin_layout_start.php'; ?>
    <!-- Main Content -->
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
    <?php include 'admin_layout_end.php' ?>
    <?php include '../includes/footer.php'; ?>
</body>

</html>