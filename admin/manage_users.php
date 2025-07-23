<?php
$required_role = "admin";
include '../includes/auth.php';
include '../config/db.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit;
}

// Initialize variables
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$users = [];

if ($search_query !== '') {
    // Perform search
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE CONCAT('%', ?, '%') ORDER BY user_id DESC");
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $searched = true;
} else {
    // Fetch latest 20 users
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY user_id DESC LIMIT 20");
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $searched = false;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <?php include '../includes/header.php'; ?>
    <style>
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background: #f8f9fa;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .search-bar {
            max-width: 400px;
        }

        .table-wrapper {
            overflow-x: auto;
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: #f1f1f1;
        }

        .action-btn {
            border: none;
            background: none;
            color: #dc3545;
            cursor: pointer;
        }

        .action-btn:hover {
            text-decoration: underline;
        }

        .no-results {
            text-align: center;
            color: #999;
            font-style: italic;
            padding: 1rem;
        }

        @media (max-width: 575.98px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .search-bar {
                width: 100%;
                margin-top: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'admin_layout_start.php'; ?>

    <div class="container-fluid">
        <div class="page-header">
            <h2 class="fw-bold text-secondary mt-5">Manage Users</h2>
            <form class="search-bar mt-5" method="GET" action="">
                <div class="input-group">
                    <input type="text" name="query" class="form-control" placeholder="Search by name..." value="<?= htmlspecialchars($search_query) ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>

        <div class="table-wrapper">
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td><?= htmlspecialchars($user['status']) ?></td>
                                <td>
                                    <a href="?delete=<?= $user['user_id'] ?>"
                                        class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm"
                                        onclick="return confirm('Delete this user?');">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </a>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                <?= $search_query !== '' ? "No users found for '{$search_query}'." : "No users found." ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>
    </div>

    <?php include 'admin_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script>
        // After a successful search, remove the `query` from URL on next reload
        if (window.location.search.includes("query=")) {
            if (performance.getEntriesByType("navigation")[0].type === "reload") {
                window.location.href = window.location.pathname;
            }
        }
    </script>
</body>

</html>