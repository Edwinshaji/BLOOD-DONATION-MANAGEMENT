<?php
$current_page = basename($_SERVER['PHP_SELF']);
$userName = $_SESSION['user_name'] ?? 'User';
?>

<!-- User Dashboard Header -->
<style>
    .user-header {
        background: linear-gradient(135deg, #ffb3b3, #e57373);
        color: white;
        font-weight: 600;
        padding: 0.75rem 1rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        z-index: 1050;
    }

    .user-header .navbar-brand {
        font-size: 1.25rem;
        font-weight: 700;
        color: white;
        margin-right: auto;
    }

    .user-header .nav-link {
        color: white;
        margin-right: 1rem;
        transition: 0.3s;
    }

    .user-header .nav-link:hover,
    .user-header .nav-link.active {
        background: #fff;
        color: #e57373 !important;
        border-radius: 0.2rem;
        padding: 0.4rem 0.75rem;
    }

    .navbar-toggler {
        border: none;
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 10px;
        border-radius: 4px;
    }

    .navbar-toggler-icon {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 30 30'%3E%3Cpath stroke='white' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
    }

    @media (max-width: 767.98px) {
        .user-header .navbar-collapse {
            background: linear-gradient(135deg, #ffb3b3, #e57373);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
        }
    }
</style>

<nav class="navbar navbar-expand-md user-header sticky-top">
    <!-- User Name on Left -->
    <a class="navbar-brand" href="#">
        <?php echo htmlspecialchars($userName); ?>
    </a>

    <!-- Toggle Button for Mobile -->
    <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar" aria-controls="userNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navigation Links -->
    <div class="collapse navbar-collapse" id="userNavbar">
        <ul class="navbar-nav ms-auto mb-2 mb-md-0">
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'index_user.php' || $current_page == 'donor_details.php') ? 'active' : '' ?>" href="index_user.php">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'events_user.php' || $current_page == 'view_event.php') ? 'active' : '' ?>" href="events_user.php">Events</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'emergency_requests.php') ? 'active' : '' ?>" href="emergency_requests.php">Emergency</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'donations_user.php') ? 'active' : '' ?>" href="donations_user.php">Donations</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($current_page == 'account_user.php') ? 'active' : '' ?>" href="account_user.php">Account</a>
            </li>
       </ul>
    </div>
</nav>
