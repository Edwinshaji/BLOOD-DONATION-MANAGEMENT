<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Styles -->
<style>
    :root {
        --sidebar-bg-start: #ffb3b3;
        --sidebar-bg-end: #e57373;
        --sidebar-text: #fff;
        --sidebar-active-bg: #fff0f0;
        --sidebar-active-text: #e57373;
    }

    .sidebar {
        background: linear-gradient(135deg, var(--sidebar-bg-start) 0%, var(--sidebar-bg-end) 100%);
        color: var(--sidebar-text);
        min-height: 100vh;
        width: 240px;
        padding-top: 2rem;
        z-index: 1040;
        display: flex;
        flex-direction: column;
    }

    .sidebar .nav-link,
    .sidebar .btn-link {
        color: var(--sidebar-text);
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .sidebar .nav-link.active,
    .sidebar .nav-link:focus,
    .sidebar .nav-link:hover,
    .sidebar .btn-link:not(.collapsed) {
        background: var(--sidebar-active-bg);
        color: var(--sidebar-active-text) !important;
        border-radius: 0.5rem;
    }

    .sidebar .btn-link {
        text-align: left;
        width: 100%;
    }

    .sidebar .btn-link.collapsed:hover {
        background: none;
        color: var(--sidebar-text);
    }

    .sidebar .btn-link:not(.collapsed):hover {
        background: var(--sidebar-active-bg);
        color: var(--sidebar-active-text);
    }

    .sidebar .btn-link .bi-chevron-down {
        transition: transform 0.3s ease;
    }

    .sidebar .btn-link:not(.collapsed) .bi-chevron-down {
        transform: rotate(180deg);
    }

    .sidebar .logout-link {
        color: var(--sidebar-text);
        font-weight: 700;
    }

    .sidebar .logout-link:hover {
        color: var(--sidebar-active-text);
        background: var(--sidebar-active-bg);
        border-radius: 0.5rem;
    }

    .sidebar .sidebar-header {
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: 1px;
        text-align: center;
        margin-bottom: 2rem;
    }

    .submenu {
        padding-left: 1.5rem;
    }

    .nav-item.dropdown {
        position: relative;
    }

    .nav-item.dropdown:hover .dropdown-menu {
        display: block;
        position: relative;
    }

    .dropdown-menu {
        display: none;
        background: linear-gradient(135deg, var(--sidebar-bg-start), var(--sidebar-bg-end));
        padding-left: 1.25rem;
        padding-bottom: 0.5rem;
        margin-top: 0.3rem;
        border-left: 2px solid rgba(255, 255, 255, 0.2);
    }

    .dropdown-menu .nav-link {
        color: var(--sidebar-text);
        padding: 0.4rem 1rem;
        transition: background 0.3s, color 0.3s;
    }

    .dropdown-menu .nav-link:hover,
    .dropdown-menu .nav-link.active {
        background: var(--sidebar-active-bg);
        color: var(--sidebar-active-text);
        border-radius: 0.4rem;
    }

    .nav-link.dropdown-toggle {
        display: flex;
        align-items: center;
    }

    .nav-link.dropdown-toggle::after {
        content: '\f282';
        font-family: 'Bootstrap Icons';
        font-size: 0.9rem;
        margin-left: auto;
        transition: transform 0.3s ease;
    }

    .nav-item.dropdown:hover .nav-link.dropdown-toggle::after {
        transform: rotate(90deg);
    }

    /* Mobile Header */
    .mobile-header {
        display: none;
    }

    @media (max-width: 767.98px) {
        .mobile-header {
            display: flex;
            align-items: center;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
        }

        .mobile-header .toggle-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--sidebar-active-text);
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.2);
            z-index: 1039;
            display: none;
        }

        .sidebar-backdrop.show {
            display: block;
        }

        .main-content {
            margin-top: 56px;
            margin-left: 0 !important;
        }

        .sidebar .sidebar-header {
            display: none;
        }

        .sidebar .nav {
            margin-top: 2rem;
        }
    }

    @media (min-width: 768px) {
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
        }

        .main-content {
            margin-left: 240px;
        }
    }
</style>

<!-- Mobile Header -->
<header class="mobile-header d-md-none">
    <button class="toggle-btn" id="sidebarToggleMobile"><i class="bi bi-list"></i></button>
    <span class="fw-bold" style="color: var(--sidebar-active-text);">Admin Panel</span>
</header>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebarMenu">
    <div class="sidebar-header">Admin Panel</div>
    <ul class="nav flex-column mb-auto px-2">
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'index_admin.php') ? 'active' : '' ?>" href="index_admin.php">
                <i class="bi bi-house-door me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($current_page == 'profile.php') ? 'active' : '' ?>" href="profile.php">
                <i class="bi bi-person me-2"></i> Profile
            </a>
        </li>

        <!-- Manage (Dropdown) -->
        <li class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle"><i class="bi bi-gear me-2"></i> Manage</a>
            <div class="dropdown-menu">
                <a class="nav-link <?= ($current_page == 'manage_colleges.php') ? 'active' : '' ?>" href="manage_colleges.php">Colleges</a>
                <a class="nav-link <?= ($current_page == 'manage_hospitals.php') ? 'active' : '' ?>" href="manage_hospitals.php">Hospitals</a>
                <a class="nav-link <?= ($current_page == 'manage_users.php') ? 'active' : '' ?>" href="manage_users.php">Users</a>
            </div>
        </li>

        <!-- Analytics (Dropdown) -->
        <li class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle"><i class="bi bi-bar-chart me-2"></i> Analytics</a>
            <div class="dropdown-menu">
                <a class="nav-link <?= ($current_page == 'analytics.php') ? 'active' : '' ?>" href="analytics.php">System Analytics</a>
                <a class="nav-link <?= ($current_page == 'export_pdf.php') ? 'active' : '' ?>" href="export_pdf.php">Export PDF</a>
            </div>
        </li>
    </ul>

    <!-- Logout at Bottom -->
    <div class="mt-auto px-2 mb-3">
        <a href="../logout.php" class="nav-link logout-link d-block">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</aside>

<!-- Sidebar JS Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebarMenu');
        const toggleBtn = document.getElementById('sidebarToggleMobile');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (toggleBtn && sidebar && backdrop) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });

            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            });

            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 768) {
                        sidebar.classList.remove('show');
                        backdrop.classList.remove('show');
                    }
                });
            });
        }
    });
</script>
