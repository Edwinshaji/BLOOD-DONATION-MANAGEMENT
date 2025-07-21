<?php
$required_role = "admin";
include '../includes/auth.php';
include '../config/db.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $institution_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM institutions WHERE institution_id = ?");
    $stmt->bind_param("i", $institution_id);
    $stmt->execute();
    header("Location: manage_hospitals.php");
    exit;
}

// Handle Approve / Deactivate
if (isset($_GET['action']) && isset($_GET['id'])) {
    $institution_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE institutions SET status = 'approved' WHERE institution_id = ?");
    } elseif ($action === 'deactivate') {
        $stmt = $conn->prepare("UPDATE institutions SET status = 'pending' WHERE institution_id = ?");
    }

    if (isset($stmt)) {
        $stmt->bind_param("i", $institution_id);
        $stmt->execute();
    }

    header("Location: manage_hospitals.php");
    exit;
}


// Initialize variables
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$hospitals = [];

if ($search_query !== '') {
    // Perform search
    $stmt = $conn->prepare("SELECT * FROM institutions WHERE name LIKE CONCAT('%', ?, '%') AND type = 'hospital' ORDER BY institution_id DESC");
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
    $hospitals = $result->fetch_all(MYSQLI_ASSOC);
    $searched = true;
} else {
    // Fetch latest 20 users
    $stmt = $conn->prepare("SELECT * FROM institutions WHERE type = 'hospital' ORDER BY institution_id DESC LIMIT 20");
    $stmt->execute();
    $result = $stmt->get_result();
    $hospitals = $result->fetch_all(MYSQLI_ASSOC);
    $searched = false;
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Hospitals</title>
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
            max-width: 300px;
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
                align-items: center;
            }

            .search-bar {
                width: 100%;
                margin-top: .5rem;
            }
        }

        .modal-body table th {
            width: 30%;
            white-space: nowrap;
        }

        .modal-body table td {
            word-break: break-word;
        }

        .modal-content {
            border-radius: 1rem;
        }

        .modal-header {
            border-radius: 1rem;
        }

        .modal-footer .btn {
            min-width: 120px;
        }

        @media (max-width: 576px) {
            .modal-footer {
                flex-direction: column;
                gap: 0.5rem;
            }

            .modal-footer .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include 'admin_layout_start.php'; ?>

    <div class="container-fluid">
        <div class="page-header">
            <h2 class="fw-bold text-secondary mt-5">Manage Hospitals</h2>
            <form class="search-bar mt-5" method="GET" action="">
                <div class="input-group">
                    <input type="text" name="query" class="form-control" placeholder="Search by name..." value="<?= htmlspecialchars($search_query) ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>

        <div class="table-responsive table-wrapper">
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($hospitals) > 0): ?>
                        <?php foreach ($hospitals as $index => $hospital): ?>
                            <tr>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($hospital['name']) ?></td>
                                <td><?= htmlspecialchars($hospital['email']) ?></td>
                                <td><?= htmlspecialchars($hospital['type']) ?></td>
                                <td><?= htmlspecialchars($hospital['status']) ?></td>
                                <td class="text-nowrap">

                                    <!-- View Button -->
                                    <button type="button"
                                        class="btn btn-sm btn-primary me-1 d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewModal<?= $hospital['institution_id'] ?>">
                                        <i class="bi bi-eye-fill"></i> View
                                    </button>

                                    <!-- Approve / Deactivate -->
                                    <?php if ($hospital['status'] === 'pending'): ?>
                                        <a href="?action=approve&id=<?= $hospital['institution_id'] ?>"
                                            class="btn btn-sm btn-success me-1 d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm"
                                            onclick="return confirm('Approve this hospital?');">
                                            <i class="bi bi-check-circle-fill"></i> Approve
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=deactivate&id=<?= $hospital['institution_id'] ?>"
                                            class="btn btn-sm btn-warning me-1 d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm"
                                            onclick="return confirm('Deactivate this hospital?');">
                                            <i class="bi bi-x-circle-fill"></i> Deactivate
                                        </a>
                                    <?php endif; ?>

                                    <!-- Delete -->
                                    <a href="?delete=<?= $hospital['institution_id'] ?>"
                                        class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm"
                                        onclick="return confirm('Delete this hospital?');">
                                        <i class="bi bi-trash-fill"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-results">
                                <?= $search_query !== '' ? "No hospitals found for '{$search_query}'." : "No hospitals found." ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>


            <?php foreach ($hospitals as $hospital): ?>
                <div class="modal fade" id="viewModal<?= $hospital['institution_id'] ?>" tabindex="-1"
                    aria-labelledby="viewModalLabel<?= $hospital['institution_id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <!-- Modal Header -->
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="viewModalLabel<?= $hospital['institution_id'] ?>">hospital Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <!-- Modal Body -->
                            <div class="modal-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Name</th>
                                        <td><?= htmlspecialchars($hospital['name']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?= htmlspecialchars($hospital['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Type</th>
                                        <td><?= htmlspecialchars($hospital['type']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td><?= htmlspecialchars($hospital['status']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Address</th>
                                        <td><?= htmlspecialchars($hospital['address']) ?></td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Modal Footer with Action Buttons -->
                            <div class="modal-footer">
                                <?php if ($hospital['status'] === 'pending'): ?>
                                    <!-- Approve Button -->
                                    <a href="?action=approve&id=<?= $hospital['institution_id'] ?>"
                                        class="btn btn-success"
                                        onclick="return confirm('Approve this hospital?');">
                                        <i class="bi bi-check-circle-fill me-1"></i> Approve
                                    </a>
                                <?php else: ?>
                                    <!-- Deactivate Button -->
                                    <a href="?action=deactivate&id=<?= $hospital['institution_id'] ?>"
                                        class="btn btn-warning"
                                        onclick="return confirm('Deactivate this hospital?');">
                                        <i class="bi bi-x-circle-fill me-1"></i> Deactivate
                                    </a>
                                <?php endif; ?>

                                <!-- Delete Button -->
                                <a href="?delete=<?= $hospital['institution_id'] ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Delete this hospital?');">
                                    <i class="bi bi-trash-fill me-1"></i> Delete
                                </a>

                                <!-- Close Modal -->
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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