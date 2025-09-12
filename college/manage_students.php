<?php
$required_role = ['college'];
include '../includes/auth.php';
include '../config/db.php';

$institution_id = $_SESSION['institution_id'];

// Handle Actions
if (isset($_GET['action']) && isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    $action = $_GET['action'];

    if ($action === 'delete') {
        // 1. Delete from request_responses
        $stmt = $conn->prepare("DELETE FROM request_responses WHERE user_id=?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // 2. Delete from donations (if exists, already handled earlier if not done)
        $stmt = $conn->prepare("DELETE FROM donations WHERE donor_id IN (SELECT donor_id FROM donors WHERE user_id=?)");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // 3. Delete from event_participation
        $stmt = $conn->prepare("DELETE FROM event_participation WHERE user_id=?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // 4. Delete from donors
        $stmt = $conn->prepare("DELETE FROM donors WHERE user_id=?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();

        // 5. Finally delete student from users
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=? AND institution_id=? AND role='student'");
        $stmt->bind_param("ii", $student_id, $institution_id);

        $_SESSION['success'] = $stmt->execute()
            ? "Student deleted successfully!"
            : "Error deleting student.";
    } elseif ($action === 'deactivate') {
        $stmt = $conn->prepare("UPDATE users SET status='inactive' WHERE user_id=? AND institution_id=? AND role='student'");
        $stmt->bind_param("ii", $student_id, $institution_id);
        $_SESSION['success'] = $stmt->execute() ? "Student deactivated successfully!" : "Error updating status.";
    } elseif ($action === 'activate') {
        $stmt = $conn->prepare("UPDATE users SET status='active' WHERE user_id=? AND institution_id=? AND role='student'");
        $stmt->bind_param("ii", $student_id, $institution_id);
        $_SESSION['success'] = $stmt->execute() ? "Student activated successfully!" : "Error updating status.";
    }

    header("Location: manage_students.php");
    exit;
}

// Search filter
$search = $_GET['search'] ?? '';
$search_query = "%" . $search . "%";

// Fetch Students (with blood group if available)
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT u.user_id, u.name, u.email, u.phone, u.status, d.blood_group
                            FROM users u
                            LEFT JOIN donors d ON u.user_id = d.user_id
                            WHERE u.institution_id=? AND u.role='student'
                            AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR d.blood_group LIKE ?)
                            ORDER BY u.name ASC");
    $stmt->bind_param("issss", $institution_id, $search_query, $search_query, $search_query, $search_query);
} else {
    $stmt = $conn->prepare("SELECT u.user_id, u.name, u.email, u.phone, u.status, d.blood_group
                            FROM users u
                            LEFT JOIN donors d ON u.user_id = d.user_id
                            WHERE u.institution_id=? AND u.role='student'
                            ORDER BY u.name ASC");
    $stmt->bind_param("i", $institution_id);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Students - College Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <style>
        .action-btn {
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.85rem;
        }

        .status-active {
            color: green;
            font-weight: bold;
        }

        .status-inactive {
            color: red;
            font-weight: bold;
        }

        .fade-out {
            transition: opacity 1s ease;
        }
    </style>
</head>

<body>
    <?php include 'college_layout_start.php'; ?>

    <div class="container py-4">

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="flashMessage" class="alert alert-success text-center fade-out">
                <?= $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div id="flashMessage" class="alert alert-danger text-center fade-out">
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <form method="GET" class="d-flex mb-4">
            <input type="text" name="search" class="form-control me-1"
                placeholder="Search by name, email, phone or blood group ..."
                value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-danger" type="submit"><i class="bi bi-search"></i> Search</button>
        </form>

        <div class="table-responsive table-wrapper">
            <table class="table table-bordered table-hover align-middle text-center">
                <thead class="table-danger">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Blood Group</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['name']) ?></td>
                                <td><?= htmlspecialchars($student['email']) ?></td>
                                <td><?= htmlspecialchars($student['phone']) ?></td>
                                <td><?= $student['blood_group'] ? htmlspecialchars($student['blood_group']) : '<span class="text-muted">N/A</span>' ?></td>
                                <td>
                                    <?php if ($student['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-nowrap justify-content-center overflow-auto" style="gap:0.5rem; max-width: 320px;">
                                        <?php if ($student['status'] === 'active'): ?>
                                            <a href="?action=deactivate&student_id=<?= $student['user_id'] ?>"
                                                class="btn btn-warning btn-sm action-btn"
                                                onclick="return confirm('Deactivate this student?')">
                                                <i class="bi bi-person-dash"></i> Deactivate
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=activate&student_id=<?= $student['user_id'] ?>"
                                                class="btn btn-success btn-sm action-btn"
                                                onclick="return confirm('Activate this student?')">
                                                <i class="bi bi-person-check"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&student_id=<?= $student['user_id'] ?>"
                                            class="btn btn-danger btn-sm action-btn"
                                            onclick="return confirm('Delete this student permanently?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-muted">No students found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'college_layout_end.php'; ?>
    <?php include '../includes/footer.php'; ?>

    <script>
        // Auto-dismiss flash messages after 3 seconds
        setTimeout(() => {
            const flash = document.getElementById('flashMessage');
            if (flash) {
                flash.style.opacity = '0';
                setTimeout(() => flash.remove(), 1000);
            }
        }, 3000);
    </script>
</body>

</html>